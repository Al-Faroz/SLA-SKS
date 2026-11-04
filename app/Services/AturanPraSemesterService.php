<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use DomainException;

/**
 * A8-0: kebijakan murni Pra-Semester v2.5. Tidak menyentuh database.
 *
 * Adapter database/Controller pada tahap berikutnya WAJIB mengambil semua
 * argumen dari server setelah autentikasi dan lock transaksi, bukan dari
 * payload browser. Tidak boleh memanggil ini untuk menciptakan attempt.
 */
final class AturanPraSemesterService
{
    public const MINIMAL_SOAL = 30;
    public const ZONA_WAKTU = 'Asia/Jakarta';

    /**
     * Progres siswa diverifikasi untuk SEMUA TP NonEsensial paket semester yang
     * sama; Esensial tidak memerlukan progres, dan nilai <KKM bukan penghalang.
     *
     * @param list<array{id:int,jenis_tp:string}> $daftarTp Dari paket terotorisasi
     * @param array<int,string> $statusPerTp Dari tabel progres siswa terotorisasi
     * @return array{siap:bool,jumlah_wajib:int,jumlah_tuntas:int,belum_tuntas:list<int>,pesan:string}
     */
    public static function kelayakanTp(array $daftarTp, array $statusPerTp): array
    {
        $wajib = [];
        $telahDiperiksa = [];
        foreach ($daftarTp as $tp) {
            $id = (int) ($tp['id'] ?? 0);
            $jenis = (string) ($tp['jenis_tp'] ?? '');
            if ($id < 1 || !in_array($jenis, ['esensial','non_esensial'],true)) {
                throw new DomainException('Daftar TP paket semester tidak valid.');
            }
            if (isset($wajib[$id]) || isset($telahDiperiksa[$id])) {
                throw new DomainException('TP dalam paket semester tidak boleh berulang.');
            }
            $telahDiperiksa[$id] = true;
            if ($jenis === 'non_esensial') $wajib[$id] = true;
        }
        $belum = [];
        $tuntas = 0;
        foreach ($wajib as $id => $_) {
            if (($statusPerTp[$id] ?? '') === 'tuntas') {
                $tuntas++;
            } else {
                $belum[] = $id;
            }
        }
        $jumlah = count($wajib);
        // Jangan membuka ujian untuk paket yang belum memiliki TP wajib.
        // Jika Master nantinya menetapkan paket Esensial-saja, evaluasi aturan
        // khususnya dulu; jangan diam-diam membolehkan ujian kosong.
        $siap = $jumlah > 0 && $belum === [];
        return [
            'siap'=>$siap,'jumlah_wajib'=>$jumlah,
            'jumlah_tuntas'=>$tuntas,'belum_tuntas'=>$belum,
            'pesan'=>$siap
                ? 'Seluruh TP NonEsensial semester sudah selesai.'
                : ($jumlah === 0
                    ? 'Belum ada TP NonEsensial pada paket semester.'
                    : 'Selesaikan seluruh TP NonEsensial sebelum mengikuti Pra-Semester.'),
        ];
    }

    /**
     * Metadata soal/kunci diperiksa server, tidak menerima HTML/kunci dari UI.
     * Bank minimal 30 butir PG A–E sah dan harus mencakup setiap BAB semester.
     * Id BAB harus diambil dari paket semester terotorisasi yang sama.
     *
     * @param list<int> $babIds
     * @param list<array{id:int,bab_id:int,aktif:bool,opsi_lengkap:bool,kunci_sah:bool}> $daftarSoal
     * @return array{siap:bool,jumlah_sah:int,jumlah_bab:int,jumlah_bab_tercakup:int,bab_tanpa_soal:list<int>,jumlah_soal_tidak_sah:int,pesan:string}
     */
    public static function kesiapanBank(array $babIds, array $daftarSoal): array
    {
        $babSet = [];
        foreach ($babIds as $id) {
            if (!is_int($id) || $id < 1 || isset($babSet[$id])) {
                throw new DomainException('Daftar BAB ujian harus unik dan valid.');
            }
            $babSet[$id] = true;
        }

        $tercakup = [];
        $soalDilihat = [];
        $sah = 0;
        $tidakSah = 0;
        foreach ($daftarSoal as $butir) {
            $id = (int) ($butir['id'] ?? 0);
            $babId = (int) ($butir['bab_id'] ?? 0);
            if ($id < 1 || $babId < 1 || isset($soalDilihat[$id])) {
                throw new DomainException('Identitas soal Pra-Semester tidak valid atau berulang.');
            }
            $soalDilihat[$id] = true;
            // Tidak menghitung soal tidak aktif, soal dari BAB lain, opsi
            // belum lengkap atau kunci yang belum lolos validasi server.
            if (($butir['aktif'] ?? null) !== true
                || ($butir['opsi_lengkap'] ?? null) !== true
                || ($butir['kunci_sah'] ?? null) !== true
                || !isset($babSet[$babId])) {
                $tidakSah++;
                continue;
            }
            $sah++;
            $tercakup[$babId] = true;
        }
        $tanpa = array_values(array_map(
            'intval',
            array_keys(array_diff_key($babSet,$tercakup))
        ));
        $siap = count($babSet) > 0 && $sah >= self::MINIMAL_SOAL && $tanpa === [];
        return [
            'siap'=>$siap,'jumlah_sah'=>$sah,
            'jumlah_bab'=>count($babSet),
            'jumlah_bab_tercakup'=>count($tercakup),
            'bab_tanpa_soal'=>$tanpa,
            'jumlah_soal_tidak_sah'=>$tidakSah,
            'pesan'=>$siap
                ? 'Bank Pra-Semester memenuhi minimum dan mencakup seluruh BAB.'
                : 'Bank Pra-Semester belum memenuhi 30 soal sah dan cakupan seluruh BAB.',
        ];
    }

    /**
     * Jam lokal Admin WIB, waktu pembanding hanya epoch jam SERVER.
     * Jendela setengah terbuka: mulai <= sekarang < akhir.
     *
     * @return array{boleh_mulai:bool,status:string,mulai:?int,akhir:?int,pesan:string}
     */
    public static function jadwal(?string $mulai, ?string $akhir, int $sekarang): array
    {
        if ($sekarang < 1) {
            throw new DomainException('Waktu server tidak sah.');
        }
        $hasil = [
            'boleh_mulai'=>false,'status'=>'belum_dijadwalkan',
            'mulai'=>null,'akhir'=>null,
            'pesan'=>'Jadwal Pra-Semester belum ditetapkan Admin.',
        ];
        if ($mulai === null || $akhir === null || trim($mulai) === '' || trim($akhir) === '') {
            return $hasil;
        }
        $awal = self::tanggalWib($mulai);
        $selesai = self::tanggalWib($akhir);
        if ($awal >= $selesai) {
            throw new DomainException('Jadwal akhir Pra-Semester harus setelah waktu mulai.');
        }
        $hasil['mulai'] = $awal;
        $hasil['akhir'] = $selesai;
        if ($sekarang < $awal) {
            $hasil['status']='belum_mulai';
            $hasil['pesan']='Ujian belum dibuka. Ikuti waktu serentak yang ditetapkan Admin.';
        } elseif ($sekarang >= $selesai) {
            $hasil['status']='ditutup';
            $hasil['pesan']='Jendela waktu mulai Pra-Semester sudah berakhir.';
        } else {
            $hasil['status']='berlangsung';
            $hasil['boleh_mulai']=true;
            $hasil['pesan']='Jadwal Pra-Semester sedang berlangsung.';
        }
        return $hasil;
    }

    private static function tanggalWib(string $nilai): int
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D',$nilai)) {
            throw new DomainException('Jadwal harus berformat YYYY-MM-DD HH:MM:SS WIB.');
        }
        $zona = new DateTimeZone(self::ZONA_WAKTU);
        $d = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$nilai,$zona);
        if (!$d || $d->format('Y-m-d H:i:s') !== $nilai) {
            throw new DomainException('Tanggal atau jam Pra-Semester tidak sah.');
        }
        return $d->getTimestamp();
    }

    /**
     * Semua gerbang wajib lolos sebelum adapter transaksi mengizinkan mulai.
     * @return array{boleh_mulai:bool,alasan:list<string>,tp:array,bank:array,jadwal:array}
     */
    public static function gerbangMulai(
        array $daftarTp, array $statusPerTp, array $babIds, array $daftarSoal,
        ?string $mulai, ?string $akhir, int $sekarang
    ): array {
        $tp = self::kelayakanTp($daftarTp,$statusPerTp);
        $bank = self::kesiapanBank($babIds,$daftarSoal);
        $jadwal = self::jadwal($mulai,$akhir,$sekarang);
        $alasan = [];
        if (!$tp['siap']) $alasan[]=$tp['pesan'];
        if (!$bank['siap']) $alasan[]=$bank['pesan'];
        if (!$jadwal['boleh_mulai']) $alasan[]=$jadwal['pesan'];
        return [
            'boleh_mulai'=>$alasan === [], 'alasan'=>$alasan,
            'tp'=>$tp,'bank'=>$bank,'jadwal'=>$jadwal,
        ];
    }
}
