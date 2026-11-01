<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-tp-satu-materi">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru'), 'attr') ?>">Beranda Guru</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('guru/peta-atp'), 'attr') ?>">Peta ATP</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Kelola TP</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Guru · Semester <?= (int) $paket['semester'] ?> · <?= esc($tp['kode_tp']) ?></p>
            <h1><?= esc(($tp['nama_tp'] ?? '') !== '' ? $tp['nama_tp'] : $tp['judul']) ?></h1>
            <p class="a-proto-muted"><?= esc(($tp['jenis_tp'] ?? '') === 'esensial' ? 'Esensial' : 'NonEsensial') ?>
                · <?= esc($tp['status']) ?> · Satu TP = satu Materi</p>
        </div>
    </header>
    <section class="a-card" aria-labelledby="identitas-tp">
        <h2 id="identitas-tp">Identitas TP</h2>
        <div class="a-tabel-gulir">
            <table class="a-tbl a-tp-identitas">
                <tbody>
                    <tr><th scope="row">Kode TP</th><td><?= esc($tp['kode_tp']) ?></td></tr>
                    <tr><th scope="row">Urutan ATP</th><td><?= (int) $tp['urutan_atp'] ?></td></tr>
                    <tr><th scope="row">Semester</th><td><?= (int) $paket['semester'] ?></td></tr>
                    <tr><th scope="row">Status TP</th><td><span class="a-badge"><?= esc($tp['status']) ?></span></td></tr>
                    <tr><th scope="row">Materi</th><td><?= (int) $jumlah_materi ?> dari 1</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="a-card a-jarak-atas" id="langkah-tp" aria-labelledby="judul-langkah-tp">
        <h2 id="judul-langkah-tp">Langkah penyiapan TP</h2>
        <p class="a-help">Setiap langkah memakai hasil pemeriksaan Service. TP belum diterbitkan
            sebelum semua ketentuan terpenuhi dan Guru menyetujui publikasi.</p>
        <?= view('guru/_step_progress_tp', ['langkah' => $langkah]) ?>
    </section>

    <section class="a-card a-jarak-atas" id="rumusan-tp" aria-labelledby="judul-rumusan-tp">
        <h2 id="judul-rumusan-tp">1. Data TP</h2>
        <?php if ($pesan = session()->getFlashdata('sukses')): ?>
            <div class="a-alert" role="status"><?= esc($pesan) ?></div>
        <?php endif ?>
        <?php if ($pesan = session()->getFlashdata('galat')): ?>
            <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
        <?php endif ?>
        <?php if ($tp['status'] === 'draf'): ?>
            <form method="post" action="<?= esc(site_url('guru/tp/' . (int) $tp['id']), 'attr') ?>" class="a-form">
                <?= csrf_field() ?>
                <input type="hidden" name="struktur_versi" value="<?= (int) $tp['struktur_versi'] ?>">
                <div class="a-field">
                    <label class="a-label" for="tp-judul">Judul TP</label>
                    <input class="a-input" id="tp-judul" type="text" name="judul" maxlength="180" required
                           value="<?= esc($tp['judul'], 'attr') ?>">
                </div>
                <div class="a-field">
                    <label class="a-label" for="tp-rumusan">Rumusan / deskripsi TP</label>
                    <textarea id="tp-rumusan" name="rumusan" rows="3" maxlength="20000" required><?= esc($tp['rumusan'] ?? '') ?></textarea>
                </div>
                <p class="a-help">Indikator ketercapaian tidak lagi menjadi kolom wajib terpisah.
                    Informasi lama yang telah tersimpan tetap dipertahankan.</p>
                <button class="a-btn a-btn--utama" type="submit">Simpan data TP</button>
            </form>
        <?php else: ?>
            <p><?= nl2br(esc($tp['rumusan'] ?? '')) ?></p>
            <p class="a-help">TP telah Siap dan terkunci; perubahan akademik melalui mekanisme koreksi.</p>
        <?php endif ?>
    </section>

    <section class="a-card a-jarak-atas" id="materi-tp" aria-labelledby="judul-materi-tp">
        <span id="daftar-materi"></span>
        <h2 id="judul-materi-tp">2. Satu Materi TP</h2>
        <?php if (count($materi) === 1): ?>
            <?php $item = $materi[0]; ?>
            <div class="a-tp-materi-ringkas">
                <strong><?= esc($item['judul']) ?></strong>
                <span class="a-badge"><?= esc(strtoupper((string) $item['jenis'])) ?></span>
                <span class="a-badge"><?= (int) $item['aktif'] === 1 ? 'Aktif' : 'Draf — belum terbit' ?></span>
                <?php if (($item['jenis'] === 'youtube' && ! empty($item['url_youtube']))
                    || (in_array($item['jenis'], ['pdf', 'audio', 'gambar'], true) && $item['berkas_id'] !== null)): ?>
                    <a class="a-btn a-btn--sekunder"
                        href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/materi/' . (int) $item['id'] . '/pratinjau'), 'attr') ?>"
                        target="_blank" rel="noopener noreferrer">Pratinjau</a>
                <?php endif ?>
            </div>
            <?php if ($tp['status'] === 'draf'): ?>
                <?php
                $pesanSuntingGalat = session()->getFlashdata('materi_edit_galat');
                $pesanSuntingSukses = session()->getFlashdata('materi_edit_sukses');
                $inputUlang = session()->getFlashdata('materi_edit_input_lama');
                $kolomSuntingGalat = (string) (session()->getFlashdata('materi_edit_field_galat') ?? '');
                $nilaiSunting = is_array($inputUlang) ? array_merge($item, $inputUlang) : $item;
                $nilaiSunting['berkas_id'] = $item['berkas_id'];
                if ($item['berkas_id'] !== null) {
                    $nilaiSunting['jenis'] = $item['jenis'];
                }
                ?>
                <h3>Lengkapi satu Materi ini</h3>
                <p class="a-help">Sunting Materi yang sudah dibuat dari ATP. Tidak perlu menambah Materi
                    kedua. Isi media dan waktu minimal; Resume/HOTS hanya diwajibkan untuk NonEsensial.</p>
                <?php if (is_string($pesanSuntingGalat) && $pesanSuntingGalat !== ''): ?>
                    <div class="a-alert a-alert--bahaya" role="alert">
                        <strong>Materi belum disimpan:</strong> <?= esc($pesanSuntingGalat) ?>
                    </div>
                <?php endif ?>
                <?php if (is_string($pesanSuntingSukses) && $pesanSuntingSukses !== ''): ?>
                    <div class="a-alert" role="status"><?= esc($pesanSuntingSukses) ?></div>
                <?php endif ?>
                <?php if ($item['berkas_id'] !== null): ?>
                    <p class="a-help">Berkas tersimpan. Jenis media terkunci; berkas pengganti
                        harus berjenis sama agar riwayat aman.</p>
                <?php endif ?>
                <?php if (is_string($pesanSuntingGalat) && $pesanSuntingGalat !== ''
                    && in_array(($nilaiSunting['jenis'] ?? ''), ['pdf', 'gambar', 'audio'], true)): ?>
                    <p class="a-help">Jika penyimpanan file gagal, pilih ulang file dari komputer Anda.</p>
                <?php endif ?>
                <form method="post" class="a-form" enctype="multipart/form-data"
                    data-materi-form data-materi-sudah-upload="<?= $item['berkas_id'] !== null ? '1' : '0' ?>"
                    action="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/materi/' . (int) $item['id']), 'attr') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="struktur_versi" value="<?= (int) $tp['struktur_versi'] ?>">
                    <div class="a-materi-form-fields">
                        <?= view('guru/_materi_fields', [
                            'nilai' => $nilaiSunting,
                            'galatKolom' => $kolomSuntingGalat,
                            'jenisTp' => (string) ($tp['jenis_tp'] ?? 'non_esensial'),
                        ]) ?>
                    </div>
                    <button class="a-btn a-btn--utama" type="submit" data-materi-submit>Simpan Materi</button>
                    <p class="a-help" data-materi-status aria-live="polite">Perubahan belum disimpan.</p>
                </form>
            <?php else: ?>
                <p class="a-help">Materi telah dikunci bersama TP Siap.</p>
            <?php endif ?>
        <?php elseif ($jumlah_materi === 0 && $tp['status'] === 'draf'): ?>
            <?php
            $pesanMateriGagal = session()->getFlashdata('materi_galat');
            $pesanMateriSukses = session()->getFlashdata('materi_sukses');
            $nilaiMateriLama = session()->getFlashdata('materi_input_lama');
            $kolomMateriGalat = (string) (session()->getFlashdata('materi_field_galat') ?? '');
            $nilaiMateriTambah = is_array($nilaiMateriLama) ? $nilaiMateriLama : [];
            ?>
            <div class="a-empty" role="status"><p>TP belum memiliki Materi. Tambahkan tepat satu Materi.</p></div>
            <?php if ($pesanMateriGagal): ?>
                <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesanMateriGagal) ?></div>
            <?php endif ?>
            <?php if ($pesanMateriSukses): ?>
                <div class="a-alert" role="status"><?= esc($pesanMateriSukses) ?></div>
            <?php endif ?>
            <form method="post" class="a-form" enctype="multipart/form-data"
                data-materi-form data-materi-sudah-upload="0"
                action="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/materi'), 'attr') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="struktur_versi" value="<?= (int) $tp['struktur_versi'] ?>">
                <div class="a-materi-form-fields">
                    <?= view('guru/_materi_fields', [
                        'nilai' => $nilaiMateriTambah,
                        'galatKolom' => $kolomMateriGalat,
                        'jenisTp' => (string) ($tp['jenis_tp'] ?? 'non_esensial'),
                    ]) ?>
                </div>
                <button class="a-btn a-btn--utama" type="submit" data-materi-submit>Tambah satu Materi</button>
                <p class="a-help" data-materi-status aria-live="polite">Belum disimpan.</p>
            </form>
        <?php else: ?>
            <div class="a-alert a-alert--bahaya" role="alert">
                Ditemukan <?= (int) $jumlah_materi ?> Materi pada TP ini.
                Data perlu diperiksa Admin; jangan menghapus data pembelajaran historis.
            </div>
        <?php endif ?>
    </section>

    <?php if (($tp['jenis_tp'] ?? '') === 'non_esensial'): ?>
        <section class="a-card a-jarak-atas" id="bank-soal-tautan" aria-labelledby="judul-bank-soal">
            <h2 id="judul-bank-soal">3. Diagnostik dan Sumatif</h2>
            <p class="a-help">Diagnostik memerlukan minimal 10 soal, Sumatif minimal 30 soal.
                Pengelolaan soal melalui editor khusus agar rumus, gambar, dan tabel tetap nyaman.</p>
            <a class="a-btn a-btn--sekunder"
                href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal?jenis=diagnostik'), 'attr') ?>">Kelola Diagnostik</a>
            <a class="a-btn a-btn--sekunder"
                href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal?jenis=sumatif'), 'attr') ?>">Kelola Sumatif</a>
        </section>
    <?php endif ?>

    <section class="a-card a-jarak-atas" id="kesiapan-tp" aria-labelledby="judul-kesiapan-tp">
        <h2 id="judul-kesiapan-tp"><?= $tp['status'] === 'siap' ? 'TP sudah Siap' : 'Langkah akhir · Pemeriksaan kesiapan' ?></h2>
        <p class="a-help">Penerbitan tetap memerlukan pemeriksaan final server, konfirmasi Guru,
            dan penyimpanan snapshot konten yang terkunci.</p>
        <a class="a-btn a-btn--utama"
            href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/kesiapan'), 'attr') ?>">
            <?= $tp['status'] === 'siap' ? 'Lihat status dan versi TP' : 'Periksa kesiapan TP Siap' ?>
        </a>
        <?php if (($tp['jenis_tp'] ?? '') === 'non_esensial' && $tp['status'] === 'siap'): ?>
            <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/penilaian'), 'attr') ?>">Antrean Penilaian</a>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
