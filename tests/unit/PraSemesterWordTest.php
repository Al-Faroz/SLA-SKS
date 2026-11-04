<?php

declare(strict_types=1);

use App\Services\PratinjauWordSoalService;
use App\Services\RenderWordSoalService;
use App\Services\SusunButirUjianService;
use App\Services\TemplateWordSoalService;
use PHPUnit\Framework\TestCase;

/** Kontrak Word Pra-Semester 30+ PG, BAB per butir, HMAC, rich preview. */
final class PraSemesterWordTest extends TestCase
{
    private const KEY = '01234567890123456789012345678901';

    private function identitas(): array
    {
        return [
            'tp_id'=>0,'paket_id'=>22,'guru_id'=>33,'struktur_versi'=>1,
            'gen'=>'Gen QA','guru'=>'Guru QA','mapel'=>'SKI',
            'kurikulum'=>'KBC','semester'=>'4','bab_nomor'=>[8,9],
        ];
    }

    private function fileWord(bool $tanpaTandaTangan=false): string
    {
        $bin=TemplateWordSoalService::buat(
            $this->identitas(),'pra_semester',30,self::KEY
        );
        $path=tempnam(sys_get_temp_dir(),'pra-word-');
        self::assertIsString($path);
        file_put_contents($path,$bin);
        $zip=new \ZipArchive();
        self::assertTrue($zip->open($path)===true);
        $doc=new \DOMDocument();
        self::assertTrue($doc->loadXML($zip->getFromName('word/document.xml'),LIBXML_NONET));
        $xp=new \DOMXPath($doc);
        $xp->registerNamespace('w','http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $rows=$xp->query('//w:body/w:tbl[1]/w:tr');
        self::assertCount(181,$rows);
        for($i=0;$i<30;$i++) {
            for($j=0;$j<6;$j++) {
                $row=$rows->item(1+$i*6+$j);
                $cells=$xp->query('./w:tc',$row);
                $texts=$xp->query('.//w:t',$cells->item(1));
                self::assertNotNull($texts->item(0));
                $texts->item(0)->nodeValue=$j===0
                    ? 'Pertanyaan '.($i+1) : 'Opsi '.chr(64+$j).' soal '.($i+1);
                if($j===1) {
                    $check=$xp->query('.//w:t',$cells->item(2));
                    self::assertNotNull($check->item(0));
                    $check->item(0)->nodeValue='✓';
                }
            }
        }
        self::assertTrue($zip->addFromString('word/document.xml',$doc->saveXML()));
        if($tanpaTandaTangan) self::assertTrue($zip->deleteName('customXml/sla-sks.xml'));
        self::assertTrue($zip->close());
        return $path;
    }

    public function testTemplateBersamaDenganBABPerSoalDanTandaTangan(): void
    {
        $data=TemplateWordSoalService::buat(
            $this->identitas(),'pra_semester',30,self::KEY
        );
        $path=tempnam(sys_get_temp_dir(),'pra-template-');
        self::assertIsString($path);
        try {
            file_put_contents($path,$data);
            $zip=new \ZipArchive();
            self::assertTrue($zip->open($path)===true);
            $xml=$zip->getFromName('word/document.xml');
            $meta=$zip->getFromName('customXml/sla-sks.xml');
            $zip->close();
            self::assertStringContainsString('BANK SOAL PRA-SEMESTER',$xml);
            self::assertSame(15,substr_count($xml,'>BAB 8</w:t>'));
            self::assertSame(15,substr_count($xml,'>BAB 9</w:t>'));
            self::assertStringContainsString('BAB / Benar',$xml);
            self::assertStringContainsString('PRA-22',$xml);
            self::assertStringContainsString('bab_nomor',$meta);
            self::assertStringNotContainsString('✓</w:t>',$xml);
        } finally { @unlink($path); }
    }

    public function testParserBaca30SoalBesertaBABDanRenderHTML(): void
    {
        $file=$this->fileWord();
        try {
            $parsed=PratinjauWordSoalService::periksa(
                $file,0,33,22,1,'pra_semester',self::KEY,null,[8=>80,9=>90]
            );
            self::assertSame(30,$parsed['jumlah']);
            self::assertSame(80,$parsed['soal'][0]['bab_id']);
            self::assertSame(90,$parsed['soal'][1]['bab_id']);
            self::assertSame('A',$parsed['soal'][0]['kunci']);
            self::assertSame('tanda_tangan',$parsed['pemeriksaan_identitas']);
            $render=RenderWordSoalService::hasil($file,$parsed);
            self::assertStringContainsString('Pertanyaan 1',
                $render['soal'][0]['html_pertanyaan']);
            self::assertStringContainsString('Opsi A soal 1',
                $render['soal'][0]['html_opsi']['A']);
        } finally { @unlink($file); }
    }

    public function testWordYangDisimpanUlangTanpaMetadataTetapDikenaliDariIdentitas(): void
    {
        $file=$this->fileWord(true);
        try {
            $parsed=PratinjauWordSoalService::periksa(
                $file,0,33,22,1,'pra_semester',self::KEY,null,[8=>80,9=>90]
            );
            self::assertSame('identitas_terlihat',$parsed['pemeriksaan_identitas']);
            self::assertSame(30,$parsed['jumlah']);
        } finally { @unlink($file); }
    }

    public function testBABAsingDitolakTanpaMengubahDatabase(): void
    {
        $file=$this->fileWord();
        try {
            $zip=new \ZipArchive();
            self::assertTrue($zip->open($file)===true);
            $xml=$zip->getFromName('word/document.xml');
            $xml=str_replace('>BAB 8</w:t>','>BAB 99</w:t>',$xml);
            self::assertTrue($zip->addFromString('word/document.xml',$xml));
            self::assertTrue($zip->close());
            $this->expectException(\DomainException::class);
            PratinjauWordSoalService::periksa(
                $file,0,33,22,1,'pra_semester',self::KEY,null,[8=>80,9=>90]
            );
        } finally { @unlink($file); }
    }

    public function testNomorBabYangHilangDitolak(): void
    {
        $file=$this->fileWord();
        try {
            $zip=new \ZipArchive();
            self::assertTrue($zip->open($file)===true);
            $xml=$zip->getFromName('word/document.xml');
            $xml=str_replace('>BAB 9</w:t>','>BAB 8</w:t>',$xml);
            self::assertTrue($zip->addFromString('word/document.xml',$xml));
            self::assertTrue($zip->close());
            $this->expectException(\DomainException::class);
            PratinjauWordSoalService::periksa(
                $file,0,33,22,1,'pra_semester',self::KEY,null,[8=>80,9=>90]
            );
        } finally { @unlink($file); }
    }

    public function testTemplatePaketLainDitolak(): void
    {
        $file=$this->fileWord();
        try {
            $this->expectException(\DomainException::class);
            PratinjauWordSoalService::periksa(
                $file,0,33,23,1,'pra_semester',self::KEY,null,[8=>80,9=>90]
            );
        } finally { @unlink($file); }
    }

    public function testSoalRichWordTetapMengikutiAcakOpsi(): void
    {
        $bank=[];
        for($i=1;$i<=30;$i++) {
            $rich=[
                'format'=>'SLA-SKS-RICH-V1',
                'word_source_id'=>str_repeat('a',32),
                'html_pertanyaan'=>'<p>Soal rich '.$i.'</p>',
                'html_opsi'=>['A'=>'<p>A'.$i.'</p>','B'=>'<p>B'.$i.'</p>',
                    'C'=>'<p>C'.$i.'</p>','D'=>'<p>D'.$i.'</p>','E'=>'<p>E'.$i.'</p>'],
            ];
            $bank[]=[
                'id'=>$i,'bab_id'=>$i%2===0?8:9,'aktif'=>1,
                'pertanyaan'=>'Soal '.$i,'opsi_a'=>'A'.$i,'opsi_b'=>'B'.$i,
                'opsi_c'=>'C'.$i,'opsi_d'=>'D'.$i,'opsi_e'=>'E'.$i,'kunci'=>'B',
                'konten_terstruktur_json'=>json_encode($rich),
            ];
        }
        $items=SusunButirUjianService::praSemester($bank,[8,9],30);
        self::assertCount(30,$items);
        foreach($items as $item) {
            $rich=json_decode($item['konten_terstruktur_json'],true);
            self::assertSame(str_repeat('a',32),$rich['word_source_id']);
            self::assertCount(5,$rich['html_opsi']);
            self::assertArrayHasKey($item['kunci_acak'],$rich['html_opsi']);
            self::assertStringContainsString('<p>B',
                $rich['html_opsi'][$item['kunci_acak']]);
        }
    }

    public function testRuteFormDanSQLStagingAda(): void
    {
        $root=dirname(__DIR__,2);
        $routes=file_get_contents($root.'/app/Config/Routes.php');
        $view=file_get_contents($root.'/app/Views/guru/pra_semester_detail.php');
        $review=file_get_contents($root.'/app/Views/guru/pra_semester_word_review.php');
        $sql=file_get_contents($root.'/database/sql/updates/V4-0010_BANK_WORD_PRA_SEMESTER.sql');
        self::assertStringContainsString('GuruPraSemester::unduhWord',$routes);
        self::assertStringContainsString('GuruPraSemester::unggahWord',$routes);
        self::assertStringContainsString('GuruPraSemester::reviewWord',$routes);
        self::assertStringContainsString('GuruPraSemester::konfirmasiWord',$routes);
        self::assertStringContainsString('SiswaPraSemester::gambarWord',$routes);
        self::assertStringContainsString('Unggah &amp; Pratinjau Word',$view);
        self::assertStringContainsString('Konfirmasi &amp; Simpan',$review);
        self::assertStringContainsString('CREATE TABLE impor_soal_pra_docx',$sql);
        self::assertStringNotContainsString('DROP TABLE',$sql);
    }
}
