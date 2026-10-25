<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('siswa'), 'attr') ?>">Beranda Siswa</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('siswa/pembelajaran'), 'attr') ?>">Pembelajaran</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('siswa/tp/' . (int) $tp['id']), 'attr') ?>">Rincian TP</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Materi <?= (int) $materi['urutan'] ?></span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Siswa · <?= esc((string) $tp['kode_tp']) ?> · Materi <?= (int) $materi['urutan'] ?></p>
            <h1><?= esc($materi['judul']) ?></h1>
            <p class="a-proto-muted">
                <?= $sukarela ? 'Belajar sukarela setelah lulus Diagnostik.' :
                    'Pembelajaran wajib setelah Diagnostik belum memenuhi KKM.' ?>
            </p>
        </div>
    </header>

    <section class="a-card a-siswa-media" aria-labelledby="konten-media">
        <h2 id="konten-media">Media pembelajaran · <?= esc(strtoupper($materi['jenis'])) ?></h2>
        <?php if ($materi['jenis'] === 'youtube'): ?>
            <div class="a-media-video">
                <iframe id="a-siswa-video-pemutar"
                    src="<?= esc($video_url . '?enablejsapi=1', 'attr') ?>"
                    title="Video pembelajaran: <?= esc($materi['judul'], 'attr') ?>"
                    loading="lazy" referrerpolicy="strict-origin-when-cross-origin"
                    allow="encrypted-media; picture-in-picture; fullscreen"
                    allowfullscreen></iframe>
            </div>
            <p class="a-help">Durasi dipantau selama video benar-benar diputar,
                halaman aktif, dan kecepatan normal. Minimal 90% durasi
                serta waktu belajar yang ditentukan Guru.</p>
        <?php elseif ($materi['jenis'] === 'pdf'): ?>
            <div class="a-materi-pdf a-pembaca-fullscreen" data-pembaca-fullscreen>
                <div class="a-pembaca-toolbar">
                    <span class="a-pembaca-toolbar__judul">Pembaca PDF</span>
                    <button class="a-btn a-btn--sekunder" type="button"
                        data-pembaca-toggle aria-label="Perbesar PDF ke layar penuh"
                        aria-pressed="false">Layar penuh</button>
                </div>
                <div class="a-pembaca-pdf-frame">
                    <iframe src="<?= esc($media_url, 'attr') ?>#toolbar=1"
                        title="PDF pembelajaran: <?= esc($materi['judul'], 'attr') ?>"
                        loading="lazy" allowfullscreen></iframe>
                </div>
                <p class="a-pembaca-pesan" data-pembaca-pesan role="status" aria-live="polite"></p>
            </div>
            <p class="a-help">PDF dapat dibaca. Namun pembaca PDF.js lokal belum terpasang:
                sistem <strong>belum</strong> menghitung halaman PDF sebagai selesai.
                Diperlukan seluruh halaman dan waktu aktif per halaman yang sah.</p>
        <?php elseif ($materi['jenis'] === 'audio'): ?>
            <div class="a-materi-audio">
                <audio controls preload="metadata" data-audio-materi
                    src="<?= esc($media_url, 'attr') ?>"
                    aria-label="Pemutar audio: <?= esc($materi['judul'], 'attr') ?>">
                    Browser tidak mendukung pemutar audio.
                </audio>
            </div>
            <p class="a-help">Durasi dihitung saat audio diputar pada halaman aktif
                dengan kecepatan normal, tanpa menghitung lompatan posisi.</p>
        <?php elseif ($materi['jenis'] === 'gambar'): ?>
            <div class="a-materi-gambar a-pembaca-fullscreen" data-pembaca-fullscreen data-pembaca-gambar>
                <div class="a-pembaca-toolbar">
                    <span class="a-pembaca-toolbar__judul">Pembaca gambar</span>
                    <div class="a-pembaca-zoom-controls" role="group" aria-label="Perbesaran gambar">
                        <button type="button" class="a-btn a-btn--sekunder"
                            data-zoom-kurang aria-label="Perkecil gambar">−</button>
                        <output data-zoom-nilai aria-live="polite">100%</output>
                        <button type="button" class="a-btn a-btn--sekunder"
                            data-zoom-tambah aria-label="Perbesar gambar">+</button>
                        <button type="button" class="a-btn a-btn--sekunder"
                            data-zoom-reset>Atur ulang</button>
                    </div>
                    <button class="a-btn a-btn--sekunder" type="button"
                        data-pembaca-toggle aria-label="Perbesar gambar ke layar penuh"
                        aria-pressed="false">Layar penuh</button>
                </div>
                <div class="a-pembaca-gambar-frame" data-gambar-observer tabindex="0"
                    aria-label="Area gambar. Gunakan tombol zoom, lalu gulir untuk melihat detail gambar.">
                    <div class="a-pembaca-gambar-stage" data-gambar-stage>
                        <img data-gambar-materi src="<?= esc($media_url, 'attr') ?>"
                            alt="Gambar materi <?= esc($materi['judul'], 'attr') ?>"
                            loading="lazy">
                    </div>
                </div>
                <p class="a-pembaca-pesan" data-pembaca-pesan role="status" aria-live="polite"></p>
            </div>
            <p class="a-help">Waktu pengamatan dihitung saat gambar tampil
                sekurang-kurangnya 50% di layar dan tab berada di depan.</p>
        <?php endif ?>
        <?php if ($sukarela): ?>
            <div class="a-alert" role="status">
                TP sudah Tuntas melalui Diagnostik. Materi ini hanya untuk belajar sukarela,
                sehingga waktu membaca tidak dicatat sebagai tugas atau tambahan nilai.
            </div>
        <?php else: ?>
            <div class="a-siswa-media-progres"
                data-media-progres
                data-media-jenis="<?= esc($materi['jenis'], 'attr') ?>"
                data-media-url="<?= esc(site_url('siswa/tp/' . (int) $tp['id']
                    . '/materi/' . (int) $materi['id'] . '/aktivitas'), 'attr') ?>"
                data-csrf-name="<?= esc(csrf_token(), 'attr') ?>"
                data-csrf-hash="<?= esc(csrf_hash(), 'attr') ?>">
                <h3>Aktivitas belajar</h3>
                <p data-media-status role="status" aria-live="polite">Memeriksa progres media…</p>
                <progress data-media-bilah value="0" max="100" aria-label="Kemajuan waktu belajar"></progress>
                <p data-media-waktu class="a-help">Waktu aktif tersimpan: 0 detik.</p>
                <p class="a-help">
                    Selesai media tidak membuka Materi selanjutnya sebelum Resume dan HOTS
                    Materi ini dikirim final. Draf/Kirim Final tersedia pada tahap berikutnya.
                </p>
            </div>
        <?php endif ?>
    </section>

    <section class="a-card a-jarak-atas a-materi-kegiatan" aria-labelledby="petunjuk-kegiatan">
        <h2 id="petunjuk-kegiatan">Petunjuk kegiatan</h2>
        <div class="a-siswa-materi-instruksi">
            <section>
                <h3>Resume Materi <?= (int) $materi['urutan'] ?></h3>
                <p class="a-teks-pratinjau"><?= nl2br(esc($materi['petunjuk_resume'])) ?></p>
            </section>
            <section>
                <h3>HOTS Materi <?= (int) $materi['urutan'] ?></h3>
                <p class="a-teks-pratinjau"><?= nl2br(esc($materi['petunjuk_hots'])) ?></p>
            </section>
        </div>
        <p class="a-help"><?= $sukarela
            ? 'Resume dan HOTS resmi tidak diwajibkan atau dinilai pada jalur lulus Diagnostik.'
            : 'Simpan Draf dan Kirim Final belum tersedia pada tahap ini; tidak ada pengiriman tugas palsu.' ?></p>
    </section>
    <nav class="a-kesiapan-kembali a-materi-lanjut" aria-label="Navigasi materi">
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('siswa/tp/' . (int) $tp['id']), 'attr') ?>">
            Kembali ke Rincian TP
        </a>
        <?php if ($materi_berikutnya !== null): ?>
            <?php if ($materi_berikutnya['boleh_buka']): ?>
                <a class="a-btn a-btn--utama"
                    href="<?= esc(site_url('siswa/tp/' . (int) $tp['id'] . '/materi/'
                        . (int) $materi_berikutnya['id']), 'attr') ?>">
                    Materi Selanjutnya <span aria-hidden="true">→</span>
                </a>
                <span class="a-materi-lanjut__keterangan">Materi <?= (int) $materi_berikutnya['urutan'] ?>:
                    <?= esc($materi_berikutnya['judul']) ?> — belajar sukarela</span>
            <?php else: ?>
                <button class="a-btn a-btn--utama" type="button"
                    disabled aria-describedby="a-materi-lanjut-syarat">Materi Selanjutnya</button>
                <p class="a-materi-lanjut__keterangan" id="a-materi-lanjut-syarat">
                    <?= esc($materi_berikutnya['alasan']) ?>
                    Tombol akan tersedia setelah alur Resume dan HOTS final diimplementasikan.
                </p>
            <?php endif ?>
        <?php else: ?>
            <p class="a-materi-lanjut__keterangan">Ini adalah Materi terakhir pada TP ini.</p>
        <?php endif ?>
    </nav>
</div>
<?php if (!$sukarela): ?>
    <script src="<?= esc(base_url('public/assets/js/aktivitas-materi.js'), 'attr') ?>" defer></script>
<?php endif ?>
<?php if (in_array($materi['jenis'], ['gambar', 'pdf'], true)): ?>
    <script src="<?= esc(base_url('public/assets/js/pembaca-materi.js'), 'attr') ?>" defer></script>
<?php endif ?>
<?= $this->endSection() ?>
