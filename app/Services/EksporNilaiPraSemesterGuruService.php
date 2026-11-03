<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

/** A9: XLSX khusus Pra-Semester; tidak memasukkan nilai TP atau data ujian privat. */
final class EksporNilaiPraSemesterGuruService
{
    private RekapNilaiPraSemesterGuruService $rekap;

    public function __construct(?BaseConnection $db = null, ?callable $akun = null)
    {
        $this->rekap = new RekapNilaiPraSemesterGuruService($db,$akun);
    }

    private static function teks($sheet,string $cell,mixed $value): void
    {
        $sheet->setCellValueExplicit($cell,(string)($value ?? ''),DataType::TYPE_STRING);
    }

    private static function nilai($sheet,string $cell,mixed $value): void
    {
        if ($value === null) return;
        $sheet->setCellValue($cell,round((float)$value,2));
        $sheet->getStyle($cell)->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
    }

    public function unduh(array $pilihan,int $semester): array
    {
        if ($semester < 1 || $semester > 6) {
            throw new DomainException('Semester unduhan Pra-Semester tidak valid.');
        }
        $book = new Spreadsheet();
        $file = null;
        try {
            $sheet = $book->getActiveSheet();
            $sheet->setTitle('NILAI PRA-SEMESTER');
            self::teks($sheet,'A1','REKAP NILAI PRA-SEMESTER — SLA-SKS');
            self::teks($sheet,'A2','Nilai Pra-Semester berdiri sendiri; tidak ada Nilai Akhir Semester.');
            self::teks($sheet,'A3','Gen');
            self::teks($sheet,'B3',$pilihan['nama_gen'] ?? '');
            self::teks($sheet,'A4','Mata Pelajaran');
            self::teks($sheet,'B4',$pilihan['nama_mapel'] ?? '');
            self::teks($sheet,'A5','Semester');
            $sheet->setCellValue('B5',$semester);
            self::teks($sheet,'A6','Waktu ekspor (UTC)');
            self::teks($sheet,'B6',gmdate('Y-m-d H:i:s'));
            $headers = ['No.','Nama Siswa','Semester','KKM','Normal',
                'Remedial','Nilai Terbaik','Status Nilai'];
            foreach ($headers as $i=>$title) {
                self::teks($sheet,chr(65+$i).'8',$title);
            }
            $sheet->getStyle('A8:H8')->applyFromArray([
                'font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']],
                'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'125C4A']],
            ]);
            $sheet->freezePane('C9');
            $row = 9;
            $first = $this->rekap->rekap($pilihan,$semester,1,500);
            if ($first['total'] > 5000) {
                throw new DomainException('Jumlah siswa melebihi batas ekspor aman.');
            }
            for ($page=1; $page<=$first['jumlah_halaman']; $page++) {
                $data = $page===1 ? $first : $this->rekap->rekap($pilihan,$semester,$page,500);
                if ($data['total'] !== $first['total']) {
                    throw new DomainException('Data siswa berubah selama ekspor. Coba lagi.');
                }
                foreach ($data['baris'] as $s) {
                    $sheet->setCellValue('A'.$row,$row-8);
                    self::teks($sheet,'B'.$row,$s['nama_siswa']);
                    $sheet->setCellValue('C'.$row,$semester);
                    if ($s['kkm'] !== null) $sheet->setCellValue('D'.$row,(int)$s['kkm']);
                    self::nilai($sheet,'E'.$row,$s['skor_normal']);
                    self::nilai($sheet,'F'.$row,$s['skor_remedial']);
                    self::nilai($sheet,'G'.$row,$s['nilai_terbaik']);
                    self::teks($sheet,'H'.$row,$s['status_label']);
                    $row++;
                }
            }
            foreach (['A'=>9,'B'=>36,'C'=>14,'D'=>12,'E'=>15,
                'F'=>15,'G'=>19,'H'=>38] as $col=>$width) {
                $sheet->getColumnDimension($col)->setWidth($width);
            }
            if ($row>9) $sheet->setAutoFilter('A8:H'.($row-1));
            self::teks($sheet,'A'.($row+2),
                'Sel nilai kosong berarti belum ada skor sah. Skor 0 tetap nilai sah.');
            $book->setActiveSheetIndex(0);
            $file = tempnam(sys_get_temp_dir(),'slaks_pra_xlsx_');
            if ($file === false) throw new RuntimeException('Gagal membuat berkas sementara.');
            (new Xlsx($book))->save($file);
            $bytes = file_get_contents($file);
            if (!is_string($bytes) || strlen($bytes)<100) {
                throw new RuntimeException('File Excel Pra-Semester gagal dibuat.');
            }
            $gen=preg_replace('/[^a-zA-Z0-9_-]+/','-',(string)($pilihan['kode_gen']??'GEN'));
            $mapel=preg_replace('/[^a-zA-Z0-9_-]+/','-',(string)($pilihan['kode_mapel']??'MAPEL'));
            return [
                'nama'=>'NILAI_PRA_SEMESTER_'.substr((string)$gen,0,30)
                    .'_'.substr((string)$mapel,0,30).'_S'.$semester.'_'.gmdate('Ymd_His').'.xlsx',
                'isi'=>$bytes,
            ];
        } finally {
            $book->disconnectWorksheets();
            if ($file !== null && is_file($file)) @unlink($file);
        }
    }
}
