<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Menjaga batas antara source of truth pengembangan dan runtime aplikasi.
 * File contoh di docs/ boleh dipakai dalam tes, bukan di kode app/.
 */
final class DeploymentBoundaryTest extends TestCase
{
    public function testKodeRuntimeTidakMemuatPathDirektoriPengembangan(): void
    {
        $folderApp = dirname(__DIR__, 2) . '/app';
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folderApp, FilesystemIterator::SKIP_DOTS)
        );
        $pelanggaran = [];

        foreach ($iter as $berkas) {
            if (! $berkas instanceof SplFileInfo || ! $berkas->isFile()
                || strtolower($berkas->getExtension()) !== 'php') {
                continue;
            }
            $isi = file_get_contents($berkas->getPathname());
            self::assertNotFalse($isi);
            // Token parser melewatkan komentar dan PHPDoc yang menyebut docs/.
            foreach (token_get_all($isi) as $token) {
                if (! is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING) {
                    continue;
                }
                $literal = trim($token[1], "'\"");
                if (preg_match('~^/?(?:docs|tests|scripts|tools|builds)/~i', $literal) === 1) {
                    $pelanggaran[] = str_replace(dirname(__DIR__, 2) . '/', '',
                        $berkas->getPathname()) . ':' . $token[2] . ' => ' . $literal;
                }
            }
        }

        self::assertSame([], $pelanggaran,
            'Kode aplikasi tidak boleh bergantung pada direktori khusus pengembangan.');
    }
}
