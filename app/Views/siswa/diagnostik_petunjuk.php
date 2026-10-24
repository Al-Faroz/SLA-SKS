<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('siswa'), 'attr') ?>">Beranda Siswa</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('siswa/pembelajaran'), 'attr') ?>">Pembelajaran</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('siswa/tp/' . (int) $tp['id']), 'attr') ?>">Rincian TP</a>
        <span aria-hidden="true">/</span><span aria-current="page">Petunjuk Diagnostik</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Siswa · S05 · <?= esc((string) $tp['kode_tp']) ?></p>
            <h1>Petunjuk Tes Diagnostik</h1>
            <p class="a-proto-muted"><?= esc((string) $tp['judul']) ?></p>
        </div>
    </header>

    <section class="a-card" aria-labelledby="persiapan-diagnostik">
        <h2 id="persiapan-diagnostik">Persiapan ujian</h2>
        <p>Diagnostik merupakan langkah wajib sebelum pembelajaran Materi.
            Satu percobaan normal disediakan. Jika hasilnya memenuhi KKM,
            TP dinyatakan tuntas; jika belum, pembelajaran Materi, Resume,
            dan HOTS wajib dijalani secara berurutan.</p>
        <div class="a-diagnostik-ringkasan">
            <div><strong><?= (int) $jumlah_tayang ?></strong><span>Soal ditayangkan</span></div>
            <div><strong><?= (int) $durasi_menit ?> menit</strong><span>Durasi ujian</span></div>
            <div><strong>1</strong><span>Percobaan normal</span></div>
            <div><strong>Ketat</strong><span>Profil keamanan</span></div>
        </div>
        <p class="a-help">Angka soal dan durasi diambil dari snapshot TP
            yang telah dikunci untuk akun Anda, bukan dari bank soal yang dapat
            disunting setelahnya.</p>
    </section>

    <section class="a-card a-jarak-atas" aria-labelledby="persyaratan-ujian">
        <h2 id="persyaratan-ujian">Syarat pelaksanaan</h2>
        <ul class="a-diagnostik-petunjuk">
            <li>Gunakan perangkat dan browser yang mendukung layar penuh
                (<em>fullscreen</em>). HP hanya dapat dipakai bila fullscreen benar-benar berhasil.</li>
            <li>Pastikan koneksi internet stabil dan daya perangkat cukup.</li>
            <li>Selama ujian, keluar dari fullscreen menyebabkan waktu berhenti
                sementara. Pelanggaran ketiga membatalkan percobaan.</li>
            <li>Batas jeda fullscreen adalah 90 detik per kejadian dan 180 detik
                total; batas jeda akibat koneksi 300 detik sesuai kebijakan ujian.</li>
            <li>Jawaban dinyatakan tersimpan hanya setelah server mengonfirmasinya.
                Jangan menganggap jawaban offline otomatis tersimpan.</li>
        </ul>
        <h3>Hasil pemeriksaan server</h3>
        <div class="a-tabel-gulir">
            <table class="a-tbl a-tbl--cards a-diagnostik-cek">
                <thead><tr><th scope="col">Persyaratan</th><th scope="col">Status</th><th scope="col">Keterangan</th></tr></thead>
                <tbody>
                <?php foreach ($syarat as $item): ?>
                    <tr>
                        <th scope="row" data-label="Persyaratan"><?= esc($item['label']) ?></th>
                        <td data-label="Status">
                            <span class="a-tp-status <?= $item['terpenuhi'] ? 'is-lengkap' : 'is-kurang' ?>">
                                <?= $item['terpenuhi'] ? 'Terpenuhi' : 'Belum terpenuhi' ?>
                            </span>
                        </td>
                        <td data-label="Keterangan"><?= esc($item['pesan']) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="a-card a-jarak-atas" aria-labelledby="uji-perangkat">
        <h2 id="uji-perangkat">Pemeriksaan perangkat dan fullscreen</h2>
        <?php if (!$boleh_uji): ?>
            <div class="a-alert a-alert--bahaya" role="alert">
                Persyaratan server belum terpenuhi. Anda belum dapat
                melakukan pemeriksaan akhir perangkat; periksa status bersama Guru.
            </div>
        <?php else: ?>
            <p class="a-help">Klik tombol berikut untuk mencoba masuk ke fullscreen
                secara nyata. Jika browser menolaknya, gunakan desktop/laptop
                dengan browser yang mendukung fullscreen.
                Ini adalah pemeriksaan perangkat, <strong>bukan memulai ujian</strong>.</p>
            <div class="a-diagnostik-tombol" data-diagnostik-preflight>
                <button type="button" class="a-btn a-btn--utama" data-uji-fullscreen>
                    Uji fullscreen perangkat
                </button>
                <button type="button" class="a-btn a-btn--sekunder" data-keluar-fullscreen hidden>
                    Keluar dari fullscreen pemeriksaan
                </button>
                <p class="a-diagnostik-hasil" data-hasil-preflight role="status" aria-live="polite">
                    Belum diperiksa. Pemeriksaan memerlukan satu klik dari Anda.
                </p>
            </div>
        <?php endif ?>
        <?php if ($boleh_uji): ?>
            <div class="a-alert" role="status">
                <strong>Diagnostik sudah tersedia.</strong>
                Gunakan tombol berikut untuk membuka ruang ujian khusus tanpa sidebar.
                Percobaan baru dibuat setelah fullscreen berhasil dimasuki dari ruang ujian,
                bukan saat membuka petunjuk ini.
            </div>
            <a class="a-btn a-btn--utama"
                href="<?= esc(site_url('siswa/tp/' . (int) $tp['id'] . '/diagnostik/ujian'), 'attr') ?>">
                Masuk ruang Tes Diagnostik
            </a>
        <?php else: ?>
            <p class="a-help">Ruang ujian belum bisa dimasuki sampai seluruh persyaratan server terpenuhi.</p>
        <?php endif ?>
    </section>
    <p class="a-kesiapan-kembali">
        <a class="a-btn a-btn--sekunder"
            href="<?= esc(site_url('siswa/tp/' . (int) $tp['id']), 'attr') ?>">Kembali ke rincian TP</a>
    </p>
</div>
<script src="<?= esc(base_url('public/assets/js/diagnostik-preflight.js'), 'attr') ?>" defer></script>
<?= $this->endSection() ?>
