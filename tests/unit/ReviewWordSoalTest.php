<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PratinjauWordSoalService;
use App\Services\RenderWordSoalService;
use App\Services\TemplateWordSoalService;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class ReviewWordSoalTest extends TestCase
{
    /**
     * Fixture historis memakai 1 soal, sedangkan validator final memerlukan
     * 10 Diagnostik / 30 Sumatif. Lengkapi slot lainnya di DOCX uji saja.
     */
    private static function isiSlotSisanya(DOMXPath $xp, int $jumlah): void
    {
        $rows = $xp->query('//w:body/w:tbl[1]/w:tr');
        self::assertNotFalse($rows);
        for ($n = 2; $n <= $jumlah; $n++) {
            for ($i = 0; $i < 6; $i++) {
                $row = $rows->item(1 + (($n - 1) * 6) + $i);
                self::assertNotNull($row);
                $cells = $xp->query('./w:tc', $row);
                self::assertNotFalse($cells);
                $teks = $xp->query('.//w:t', $cells->item(1))->item(0);
                self::assertNotNull($teks);
                $teks->nodeValue = $i === 0 ? 'Pertanyaan uji nomor ' . $n : 'Pilihan ' . $i . ' soal ' . $n;
                if ($i === 1) {
                    $kunci = $xp->query('.//w:t', $cells->item(2))->item(0);
                    self::assertNotNull($kunci);
                    $kunci->nodeValue = '✓';
                }
            }
        }
    }

    public function testValidasiDanReviewMempertahankanTabelBersarang(): void
    {
        $data = TemplateWordSoalService::buat([
            'tp_id' => 11, 'paket_id' => 22, 'guru_id' => 33,
            'struktur_versi' => 1, 'gen' => 'Gen QA', 'guru' => 'Guru QA',
            'mapel' => 'SKI', 'kurikulum' => 'KBC', 'semester' => '4',
            'kode_tp' => 'TP-QA-01', 'judul_tp' => 'Uji review',
        ], 'diagnostik', 10, str_repeat('x', 32));

        $file = tempnam(sys_get_temp_dir(), 'qa-word-');
        self::assertNotFalse($file);
        file_put_contents($file, $data);
        try {
            $zip = new ZipArchive();
            self::assertTrue($zip->open($file) === true);
            $xml = $zip->getFromName('word/document.xml');
            self::assertIsString($xml);
            $doc = new DOMDocument();
            self::assertTrue($doc->loadXML($xml, LIBXML_NONET));
            $xp = new DOMXPath($doc);
            $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $rows = $xp->query('//w:body/w:tbl[1]/w:tr');
            self::assertNotFalse($rows);
            $teks = ['Pertanyaan dengan tabel', 'Jawaban A', 'Jawaban B', 'Jawaban C', 'Jawaban D', 'Jawaban E'];
            for ($i = 0; $i < 6; $i++) {
                $cells = $xp->query('./w:tc', $rows->item($i + 1));
                $t = $xp->query('.//w:t', $cells->item(1))->item(0);
                $t->nodeValue = $teks[$i];
                if ($i === 2) {
                    $k = $xp->query('.//w:t', $cells->item(2))->item(0);
                    $k->nodeValue = '✓';
                }
                if ($i === 0) {
                    $w = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
                    $tbl = $doc->createElementNS($w, 'w:tbl');
                    $tr = $doc->createElementNS($w, 'w:tr');
                    $tc = $doc->createElementNS($w, 'w:tc');
                    $p = $doc->createElementNS($w, 'w:p');
                    $r = $doc->createElementNS($w, 'w:r');
                    $text = $doc->createElementNS($w, 'w:t', 'Isi tabel uji');
                    $r->appendChild($text);
                    $p->appendChild($r);
                    $tc->appendChild($p);
                    $tr->appendChild($tc);
                    $tbl->appendChild($tr);
                    $cells->item(1)->appendChild($tbl);
                }
            }
            self::isiSlotSisanya($xp, 10);
            self::assertTrue($zip->addFromString('word/document.xml', $doc->saveXML()));
            self::assertTrue($zip->close());

            $hasil = PratinjauWordSoalService::periksa($file, 11, 33, 22, 1, 'diagnostik', str_repeat('x', 32));
            self::assertSame(10, $hasil['jumlah']);
            self::assertSame('B', $hasil['soal'][0]['kunci']);
            self::assertSame(1, $hasil['soal'][0]['konten']['tabel']);
            $html = RenderWordSoalService::hasil($file, $hasil);
            self::assertStringContainsString('<table', $html['soal'][0]['html_pertanyaan']);
            self::assertStringContainsString('Isi tabel uji', $html['soal'][0]['html_pertanyaan']);
            self::assertStringContainsString('Jawaban C', $html['soal'][0]['html_opsi']['C']);
        } finally {
            @unlink($file);
        }
    }
    public function testSaveWordTanpaMetadataTetapDiterimaJikaTpDanStrukturBenar(): void
    {
        $data = TemplateWordSoalService::buat([
            'tp_id' => 11, 'paket_id' => 22, 'guru_id' => 33,
            'struktur_versi' => 1, 'gen' => 'Gen QA', 'guru' => 'Guru QA',
            'mapel' => 'SKI', 'kurikulum' => 'KBC', 'semester' => '4',
            'kode_tp' => 'TP-QA-01', 'judul_tp' => 'Periksa tabel',
        ], 'sumatif', 30, str_repeat('x', 32));
        $file = tempnam(sys_get_temp_dir(), 'qa-save-word-');
        self::assertNotFalse($file);
        file_put_contents($file, $data);
        try {
            $zip = new ZipArchive();
            self::assertTrue($zip->open($file) === true);
            $doc = new DOMDocument();
            self::assertTrue($doc->loadXML($zip->getFromName('word/document.xml'), LIBXML_NONET));
            $xp = new DOMXPath($doc);
            $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $rows = $xp->query('//w:body/w:tbl[1]/w:tr');
            $isi = ['Soal dengan tabel garis baru', 'A benar', 'B salah', 'C salah', 'D salah', 'E salah'];
            foreach ($isi as $i => $text) {
                $cells = $xp->query('./w:tc', $rows->item($i + 1));
                $xp->query('.//w:t', $cells->item(1))->item(0)->nodeValue = $text;
                if ($i === 1) {
                    $xp->query('.//w:t', $cells->item(2))->item(0)->nodeValue = '✓';
                }
            }
            self::isiSlotSisanya($xp, 30);
            self::assertTrue($zip->addFromString('word/document.xml', $doc->saveXML()));
            // Mensimulasikan Microsoft Word menyimpan ulang dokumen dan
            // membuang part customXml yang tidak diregistrasikan generator lama.
            self::assertTrue($zip->deleteName('customXml/sla-sks.xml'));
            self::assertTrue($zip->close());

            $parsed = PratinjauWordSoalService::periksa(
                $file, 11, 33, 22, 1, 'sumatif', str_repeat('x', 32), 'TP-QA-01'
            );
            self::assertSame(30, $parsed['jumlah']);
            self::assertSame('A', $parsed['soal'][0]['kunci']);
            self::assertSame('identitas_terlihat', $parsed['pemeriksaan_identitas']);
        } finally {
            @unlink($file);
        }
    }
    public function testWordTanpaMetadataDariTpLainDitolak(): void
    {
        $data = TemplateWordSoalService::buat([
            'tp_id' => 11, 'paket_id' => 22, 'guru_id' => 33,
            'struktur_versi' => 1, 'kode_tp' => 'TP-QA-01',
        ], 'diagnostik', 1, str_repeat('x', 32));
        $file = tempnam(sys_get_temp_dir(), 'qa-beda-tp-');
        self::assertNotFalse($file);
        file_put_contents($file, $data);
        try {
            $zip = new ZipArchive();
            self::assertTrue($zip->open($file) === true);
            self::assertTrue($zip->deleteName('customXml/sla-sks.xml'));
            self::assertTrue($zip->close());
            $this->expectException(\DomainException::class);
            PratinjauWordSoalService::periksa(
                $file, 11, 33, 22, 1, 'diagnostik', str_repeat('x', 32), 'TP-LAIN'
            );
        } finally {
            @unlink($file);
        }
    }

}
