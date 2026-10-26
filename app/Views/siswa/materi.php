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
            <div class="a-materi-pdf a-pembaca-fullscreen" data-pembaca-fullscreen
                data-pdf-pembaca data-pdf-ready="tidak"
                data-pdf-url="<?= esc($media_url, 'attr') ?>"
                data-pdf-worker="<?= esc(base_url('public/assets/vendor/pdfjs/3.2.146/pdf.worker.min.js'), 'attr') ?>"
                data-pdf-expected="<?= (int) $materi['halaman_pdf'] ?>">
                <div class="a-pembaca-toolbar">
                    <span class="a-pembaca-toolbar__judul">Pembaca PDF</span>
                    <div class="a-pdf-controls" role="group" aria-label="Navigasi halaman PDF">
                        <button class="a-btn a-btn--sekunder" type="button" data-pdf-sebelum disabled>← Sebelumnya</button>
                        <output data-pdf-jumlah aria-live="polite">Memuat PDF…</output>
                        <button class="a-btn a-btn--sekunder" type="button" data-pdf-berikut disabled>Berikutnya →</button>
                    </div>
                    <div class="a-pdf-controls" role="group" aria-label="Zoom PDF">
                        <button class="a-btn a-btn--sekunder" type="button" data-pdf-perkecil disabled aria-label="Perkecil halaman PDF">−</button>
                        <output data-pdf-zoom>100%</output>
                        <button class="a-btn a-btn--sekunder" type="button" data-pdf-perbesar disabled aria-label="Perbesar halaman PDF">+</button>
                    </div>
                    <button class="a-btn a-btn--sekunder" type="button" data-pembaca-toggle
                        aria-label="Perbesar PDF ke layar penuh" aria-pressed="false">Layar penuh</button>
                </div>
                <div class="a-pembaca-pdf-frame a-pdf-kanvas-area" data-pdf-baca-area tabindex="0"
                    aria-label="Halaman PDF, gunakan tombol navigasi atau PageUp/PageDown untuk berpindah.">
                    <canvas data-pdf-kanvas aria-label="Halaman PDF materi pembelajaran"></canvas>
                </div>
                <p class="a-pembaca-pesan" data-pdf-pesan role="status" aria-live="polite">Memuat pembaca PDF lokal…</p>
                <p class="a-pembaca-pesan" data-pembaca-pesan role="status"></p>
            </div>
            <p class="a-help">Baca setiap halaman minimal 15 detik dengan tab aktif,
                serta penuhi waktu total minimum dari Guru.
                Progres menampilkan halaman yang belum lengkap; zoom dan layar penuh
                tidak otomatis menambah waktu.</p>
        <?php elseif ($materi['jenis'] === 'audio'): ?>
            <div class="a-materi-audio">
                <audio controls preload="metadata" data-audio-materi
                    src="<?= esc($media_url, 'attr') ?>"
                    aria-label="Pemutar audio: <?= esc($materi['judul'], 'attr') ?>">
                    Browser tidak mendukung pemutar audio.
                </audio>
            </div>
            <p class="a-help">Audio selesai saat yang lebih dulu tercapai: waktu Guru
                atau 90% durasi audio. Hanya pemutaran sah pada kecepatan normal
                dan tab aktif yang dihitung.</p>
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
                            draggable="false" loading="lazy">
                    </div>
                </div>
                <p class="a-pembaca-pesan" data-pembaca-pesan role="status" aria-live="polite"></p>
            </div>
            <p class="a-help">Gambar harus tampak sekurang-kurangnya 50% pada layar
                dengan tab aktif, selama minimal 30 detik atau waktu Guru
                (mana yang lebih lama).</p>
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
                <progress data-media-bilah value="0" max="100" aria-label="Kemajuan keseluruhan syarat media"></progress>
                <p data-media-waktu class="a-help">Waktu aktif tersimpan: 0 detik.</p>
                <p class="a-help">
                    Selesai media tidak membuka Materi selanjutnya sebelum Resume dan HOTS
                    Materi ini dikirim final. Resume dan HOTS tersedia setelah media ini selesai.
                </p>
            </div>
        <?php endif ?>
    </section>

    <section class="a-card a-jarak-atas a-materi-kegiatan" aria-labelledby="petunjuk-kegiatan">
        <h2 id="petunjuk-kegiatan">Kegiatan Materi <?= (int) $materi['urutan'] ?></h2>

        <?php if ($sukarela): ?>
            <p class="a-help">TP sudah tuntas dari Diagnostik. Resume dan HOTS berikut hanya petunjuk baca;
                tidak ada pengumpulan atau nilai tambahan.</p>
            <h3>Petunjuk Resume</h3>
            <p class="a-teks-pratinjau"><?= nl2br(esc($materi['petunjuk_resume'])) ?></p>
            <h3>Petunjuk HOTS</h3>
            <p class="a-teks-pratinjau"><?= nl2br(esc($materi['petunjuk_hots'])) ?></p>
        <?php else: ?>
            <?php foreach (['resume', 'hots'] as $jenisKegiatan): ?>
                <?php
                $rekaman = $kegiatan[$jenisKegiatan];
                $adalahHots = $jenisKegiatan === 'hots';
                $izin = $kegiatan['media_selesai']
                    && (!$adalahHots || $kegiatan['resume']['status'] === 'final');
                $terkirim = $rekaman['status'] === 'final';
                $label = $adalahHots ? 'HOTS' : 'Resume';
                $petunjuk = $adalahHots ? $materi['petunjuk_hots'] : $materi['petunjuk_resume'];
                $batas = $adalahHots ? $kegiatan['minimal_hots'] : $kegiatan['minimal_resume'];
                $isian = old('jenis') === $jenisKegiatan
                    ? (string) old('jawaban') : $rekaman['isi'];
                ?>
                <div class="a-kegiatan-editor" id="kegiatan-<?= esc($jenisKegiatan, 'attr') ?>">
                    <h3><?= esc($label) ?> Materi <?= (int) $materi['urutan'] ?></h3>
                    <?php if (session()->getFlashdata('kegiatan_jenis') === $jenisKegiatan): ?>
                        <?php if (session()->getFlashdata('kegiatan_sukses')): ?>
                            <div class="a-alert" role="status"><?= esc(session()->getFlashdata('kegiatan_sukses')) ?></div>
                        <?php endif ?>
                        <?php if (session()->getFlashdata('kegiatan_gagal')): ?>
                            <div class="a-alert a-alert--bahaya" role="alert"><?= esc(session()->getFlashdata('kegiatan_gagal')) ?></div>
                        <?php endif ?>
                    <?php endif ?>
                    <p class="a-teks-pratinjau"><?= nl2br(esc($petunjuk)) ?></p>
                    <?php if ($terkirim): ?>
                        <div class="a-alert" role="status">Jawaban <?= esc($label) ?> terkirim final
                            <?= esc((string) $rekaman['dikirim_pada']) ?> UTC. Tidak dapat diubah;
                            menunggu penilaian Guru.</div>
                        <div class="a-kegiatan-jawaban"><?= nl2br(esc($rekaman['isi'])) ?></div>
                    <?php elseif (!$izin): ?>
                        <div class="a-alert" role="status"<?= !$kegiatan['media_selesai'] ? ' data-kegiatan-tunggu-media' : '' ?>>
                            <?= $kegiatan['media_selesai']
                                ? 'Kirim Final Resume terlebih dahulu sebelum mengerjakan HOTS.'
                                : 'Selesaikan media Materi sesuai ketentuan sebelum mengerjakan Resume/HOTS.' ?>
                            <?php if (!$kegiatan['media_selesai']): ?>
                                <a class="a-btn a-btn--sekunder" href="<?= esc(current_url(), 'attr') ?>">
                                    Muat ulang status kegiatan
                                </a>
                                <p class="a-help">Halaman ini diperbarui otomatis setelah seluruh syarat media terpenuhi.
                                    Memuat ulang tidak menggantikan pemutaran nyata sampai batas efektif tercapai.</p>
                            <?php endif ?>
                        </div>
                    <?php else: ?>
                        <?php $ruteKegiatan = 'siswa/tp/' . (int) $tp['id'] . '/materi/'
                            . (int) $materi['id'] . '/kegiatan/' . $jenisKegiatan; ?>
                        <form method="post" data-kegiatan-form
                            data-minimal="<?= (int) $batas ?>"
                            action="<?= esc(site_url($ruteKegiatan), 'attr') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="jenis" value="<?= esc($jenisKegiatan, 'attr') ?>">
                            <input type="hidden" name="revisi" value="<?= (int) $rekaman['revisi'] ?>">
                            <label class="a-label" for="jawaban-<?= esc($jenisKegiatan, 'attr') ?>">Jawaban <?= esc($label) ?></label>
                            <textarea id="jawaban-<?= esc($jenisKegiatan, 'attr') ?>" name="jawaban"
                                class="a-kegiatan-teks" rows="9" maxlength="12000"
                                spellcheck="true" autocomplete="off"
                                aria-describedby="panduan-<?= esc($jenisKegiatan, 'attr') ?>"><?= esc($isian) ?></textarea>
                            <p id="panduan-<?= esc($jenisKegiatan, 'attr') ?>" class="a-help">
                                Minimal <?= (int) $batas ?> karakter untuk kirim final; maksimal 12.000.
                                Simpan draf manual. Tidak ada penyimpanan otomatis.
                                Penempelan dan salin dari editor dibatasi sebagai pencegahan dasar di browser.
                            </p>
                            <p class="a-help" data-kegiatan-indikator role="status" aria-live="polite">Tidak ada perubahan yang belum disimpan.</p>
                            <p class="a-help" data-kegiatan-validasi role="status" aria-live="polite"></p>
                            <?php if ($rekaman['status'] === 'draf'): ?>
                                <p class="a-help">Draf terakhir tersimpan <?= esc((string) $rekaman['diubah_pada']) ?> UTC.</p>
                            <?php endif ?>
                            <div class="a-kegiatan-aksi">
                                <button class="a-btn a-btn--sekunder" type="submit" name="aksi" value="simpan"
                                    formaction="<?= esc(site_url($ruteKegiatan . '/simpan'), 'attr') ?>">Simpan draf</button>
                                <button class="a-btn a-btn--utama" type="submit" name="aksi" value="kirim"
                                    formaction="<?= esc(site_url($ruteKegiatan . '/kirim'), 'attr') ?>">Kirim final</button>
                            </div>
                        </form>
                    <?php endif ?>
                </div>
            <?php endforeach ?>
        <?php endif ?>
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
                    <?= esc($materi_berikutnya['judul']) ?><?= $sukarela ? ' — belajar sukarela' : '' ?></span>
            <?php else: ?>
                <button class="a-btn a-btn--utama" type="button"
                    disabled aria-describedby="a-materi-lanjut-syarat">Materi Selanjutnya</button>
                <p class="a-materi-lanjut__keterangan" id="a-materi-lanjut-syarat">
                    <?= esc($materi_berikutnya['alasan']) ?>
                    Materi berikutnya akan terbuka setelah seluruh syarat sah.
                </p>
            <?php endif ?>
        <?php else: ?>
            <p class="a-materi-lanjut__keterangan">Ini adalah Materi terakhir pada TP ini.</p>
        <?php endif ?>
    </nav>
</div>
<?php if (!$sukarela): ?>
    <script src="<?= esc(base_url('public/assets/js/aktivitas-materi.js?v=20261026-media3'), 'attr') ?>" defer></script>
<?php endif ?>
<?php if (!$sukarela): ?>
    <script src="<?= esc(base_url('public/assets/js/kegiatan-materi.js?v=20261026-final2'), 'attr') ?>" defer></script>
<?php endif ?>
<?php if ($materi['jenis'] === 'pdf'): ?>
    <script src="<?= esc(base_url('public/assets/vendor/pdfjs/3.2.146/pdf.min.js'), 'attr') ?>" defer></script>
    <script src="<?= esc(base_url('public/assets/js/pembaca-pdf-materi.js?v=20261025-s08d'), 'attr') ?>" defer></script>
<?php endif ?>
<?php if (in_array($materi['jenis'], ['gambar', 'pdf'], true)): ?>
    <script src="<?= esc(base_url('public/assets/js/pembaca-materi.js?v=20261025-zoom2'), 'attr') ?>" defer></script>
<?php endif ?>
<?= $this->endSection() ?>
