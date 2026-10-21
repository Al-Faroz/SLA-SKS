<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\BabKontenService;
use App\Services\ExcelBabService;
use App\Services\PaketBabService;
use CodeIgniter\Test\CIUnitTestCase;
use DomainException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;

final class K3BK4BAlurTerpaduTest extends CIUnitTestCase
{
    public function testDelapanSheetResmiTanpaSheetTambahan():void
    {
        $this->assertSame([
            'PETUNJUK','IDENTITAS_BAB','TP','MATERI',
            'TUGAS_HOTS','SOAL_DIAGNOSTIK','SOAL_SUMATIF','_META'
        ],ExcelBabService::SHEETS);
        $this->assertSame('BAB-V23-1',ExcelBabService::VERSI);
    }

    public function testKolomTpDanUuidExcelTidakMengubahIdentitasCp():void
    {
        $this->assertSame('cp_utama_id',ExcelBabService::KOLOM['TP'][5]);
        $this->assertSame('row_uuid',ExcelBabService::KOLOM['MATERI'][0]);
        $this->assertSame('row_uuid',ExcelBabService::KOLOM['SOAL_DIAGNOSTIK'][0]);
        $this->assertSame('row_uuid',ExcelBabService::KOLOM['SOAL_SUMATIF'][0]);
    }

    public function testParameterTpTepatSeratusPersenDanBatasMinimum():void
    {
        $p=PaketBabService::parameterTp([]);
        $this->assertSame(75,$p['kkm']);
        $this->assertSame(100,$p['bobot_resume']+$p['bobot_tugas']+$p['bobot_sumatif']);
        $this->assertSame(10,$p['diag_tayang']);
        $this->assertSame(20,$p['sum_tayang']);
        $this->assertSame(3,$p['sum_maks_percobaan']);
    }

    public function testBobotTidakSeratusDitolak():void
    {
        $this->expectException(DomainException::class);
        PaketBabService::parameterTp(['bobot_sumatif'=>'60']);
    }

    public function testJumlahDiagnostikKurangSepuluhDitolak():void
    {
        $this->expectException(DomainException::class);
        PaketBabService::parameterTp(['diag_tayang'=>'9']);
    }

    public function testKkmDiluarRentangDitolak():void
    {
        $this->expectException(DomainException::class);
        PaketBabService::parameterTp(['kkm'=>'101']);
    }

    public function testPilihanSoalTidakBolehBerlubangAtauKunciTidakAda():void
    {
        $q=['pertanyaan'=>'Apakah contoh soal ini benar?','opsi_a'=>'Pilihan satu',
            'opsi_b'=>'Pilihan dua','opsi_c'=>'','opsi_d'=>'','opsi_e'=>'','kunci'=>'B'];
        $this->assertTrue(BabKontenService::barisSoalValid($q));
        $q['opsi_d']='Opsi melewati C';
        $this->assertFalse(BabKontenService::barisSoalValid($q));
        unset($q['opsi_d']);
        $q['kunci']='E';
        $this->assertFalse(BabKontenService::barisSoalValid($q));
    }

    public function testUrlYoutubeDibatasiHttpsDanDomainResmi():void
    {
        $this->assertTrue(BabKontenService::urlYouTubeValid('https://youtu.be/AbCdEfGh123'));
        $this->assertTrue(BabKontenService::urlYouTubeValid('https://www.youtube.com/watch?v=AbCdEfGh123'));
        $this->assertFalse(BabKontenService::urlYouTubeValid('http://youtu.be/AbCdEfGh123'));
        $this->assertFalse(BabKontenService::urlYouTubeValid('https://youtube.com.evil.invalid/watch?v=AbCdEfGh123'));
        $this->assertFalse(BabKontenService::urlYouTubeValid('javascript:alert(1)'));
    }

    public function testHeaderAudioM4aMemilikiDurasiTerverifikasi():void
    {
        // mvhd versi 0, timescale 1000, durasi 15000 ms, yakni 15 detik.
        $atom='mvhd'."\0\0\0\0".pack('N',0).pack('N',0).pack('N',1000).pack('N',15000);
        $this->assertSame(15,BabKontenService::durasiAudio($atom,'m4a'));
        $this->assertNull(BabKontenService::durasiAudio('bukan-audio','m4a'));
    }

    public function testWorkbookXlsxKedelapanSheetDapatDibaca():void
    {
        $w=new Spreadsheet();
        $w->getActiveSheet()->setTitle('PETUNJUK');
        foreach(array_slice(ExcelBabService::SHEETS,1) as $name)
            $w->createSheet()->setTitle($name);
        $sheet=$w->getSheetByName('_META');
        $sheet->setCellValueExplicit('A1','versi',DataType::TYPE_STRING);
        $sheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);
        $path=tempnam(sys_get_temp_dir(),'sks_bab_');
        $this->assertNotFalse($path);
        try{
            (new Xlsx($w))->save($path);
            $a=(new XlsxReader())->load($path);
            $this->assertSame(ExcelBabService::SHEETS,$a->getSheetNames());
            $this->assertSame(
                \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN,
                $a->getSheetByName('_META')->getSheetState()
            );
            $a->disconnectWorksheets();
        }finally{$w->disconnectWorksheets();@unlink($path);}
    }

    public function testSqlPatchTidakMenghapusDataHistoris():void
    {
        $sql=file_get_contents(ROOTPATH.'database/sql/updates/0009_cp_tp_paket_bab_excel.sql');
        $this->assertIsString($sql);
        $clean=preg_replace('/^\s*--.*$/m','',$sql);
        $this->assertSame(14,preg_match_all('/CREATE TABLE\s+\w+\s*\(/i',$clean));
        $this->assertDoesNotMatchRegularExpression('/\b(DROP|TRUNCATE|DELETE\s+FROM|ALTER\s+TABLE)\b/i',$clean);
        $this->assertStringContainsString("VALUES ('K4-0009')",$clean);
        $this->assertStringContainsString('uq_paket_aktif_konteks',$clean);
        $this->assertStringContainsString('uq_tp_cp',$clean);
    }

    public function testKunciBABSiapDanLaranganSelfApprovalAdaPadaDomain():void
    {
        $bab=file_get_contents(APPPATH.'Services/BabKesiapanService.php');
        $usulan=file_get_contents(APPPATH.'Services/UsulanCpService.php');
        $this->assertIsString($bab);
        $this->assertIsString($usulan);
        $this->assertStringContainsString('FOR UPDATE',$bab);
        $this->assertStringContainsString("'status'=>'siap'",$bab);
        $this->assertStringContainsString("id'] === (int) " . "\$admin['id']", $usulan);
        $this->assertStringContainsString("Usulan ini diajukan oleh akun Admin+Guru yang sama",$usulan);
        $this->assertStringContainsString('transaksi(', $usulan);
    }
}
