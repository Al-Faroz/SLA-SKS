<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php $data = $koreksi['usulan']; $tp = $data['tp']; $materi = $data['materi'][0] ?? []; $bolehUbah = $koreksi['status'] === 'draf'; ?>
<div class="a-page">
    <nav class="a-breadcrumb"><a href="<?= esc(site_url('guru/tp/' . (int) $koreksi['tp_id'] . '/koreksi'), 'attr') ?>">Koreksi TP</a> / Permohonan #<?= (int) $koreksi['id'] ?></nav>
    <header class="a-page__heading"><div><p class="a-proto-eyebrow">Guru · Usulan koreksi berversi</p>
        <h1>Koreksi <?= esc($tp['kode_tp']) ?></h1>
        <p class="a-proto-muted">Kategori <?= esc($koreksi['kategori']) ?> · Status <?= esc($koreksi['status']) ?> · Versi dasar #<?= (int) $koreksi['versi_dasar_id'] ?></p>
    </div></header>
    <?php if ($pesan = session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div><?php endif ?>
    <?php if ($pesan = session()->getFlashdata('sukses')): ?><div class="a-alert" role="status"><?= esc($pesan) ?></div><?php endif ?>
    <section class="a-card"><h2>Alasan koreksi</h2><p><?= esc($koreksi['alasan']) ?></p>
        <p class="a-help">Perubahan tersimpan dalam draf terpisah. Soal, berkas Materi, snapshot dan progres siswa versi lama tidak ditimpa sebelum keputusan Admin. Untuk saat ini, berkas PDF/audio/gambar tetap memakai sumber versi lama.</p>
    </section>
    <section class="a-card a-jarak-atas" id="rincian">
        <h2>Rincian TP, KKM dan Materi</h2>
        <?php if ($bolehUbah): ?>
            <form class="a-form" method="post" action="<?= esc(site_url('guru/koreksi-tp/' . (int) $koreksi['id'] . '/rincian'), 'attr') ?>">
                <?= csrf_field() ?>
                <div class="a-field"><label class="a-label" for="r-judul">Judul TP</label>
                    <input id="r-judul" name="judul" required maxlength="180" value="<?= esc($tp['judul'], 'attr') ?>"></div>
                <div class="a-field"><label class="a-label" for="r-rumusan">Rumusan TP</label>
                    <textarea id="r-rumusan" name="rumusan" rows="3" maxlength="20000" required><?= esc($tp['rumusan']) ?></textarea></div>
                <div class="a-field"><label class="a-label" for="r-kkm">KKM tunggal Diagnostik &amp; Sumatif</label>
                    <input id="r-kkm" name="kkm" type="number" min="1" max="100" step="1" required value="<?= (int) $tp['kkm'] ?>"></div>
                <div class="a-field"><label class="a-label" for="r-mjudul">Judul Materi</label>
                    <input id="r-mjudul" name="judul_materi" maxlength="180" required value="<?= esc((string) ($materi['judul'] ?? ''), 'attr') ?>"></div>
                <div class="a-field"><label class="a-label" for="r-resume">Petunjuk Resume</label>
                    <textarea id="r-resume" name="petunjuk_resume" rows="3" maxlength="20000"><?= esc((string) ($materi['petunjuk_resume'] ?? '')) ?></textarea></div>
                <div class="a-field"><label class="a-label" for="r-hots">Petunjuk HOTS</label>
                    <textarea id="r-hots" name="petunjuk_hots" rows="3" maxlength="20000"><?= esc((string) ($materi['petunjuk_hots'] ?? '')) ?></textarea></div>
                <div class="a-field"><label class="a-label" for="r-waktu">Waktu minimum Materi (detik)</label>
                    <input id="r-waktu" name="min_waktu_detik" type="number" min="1" max="65535" required value="<?= (int) ($materi['min_waktu_detik'] ?? 0) ?>"></div>
                <?php if (($materi['jenis'] ?? '') === 'youtube'): ?>
                    <div class="a-field"><label class="a-label" for="r-yt">URL video YouTube</label>
                        <input id="r-yt" name="url_youtube" type="url" maxlength="255" required value="<?= esc((string) ($materi['url_youtube'] ?? ''), 'attr') ?>"></div>
                <?php endif ?>
                <button class="a-btn a-btn--utama" type="submit">Simpan Draf Rincian</button>
            </form>
        <?php if (in_array($materi['jenis'] ?? '', ['pdf','audio','gambar'], true)): ?>
            <?php if ($bolehUbah): ?>
                <form method="post" action="<?= esc(site_url('guru/koreksi-tp/' . (int) $koreksi['id'] . '/media'), 'attr') ?>" enctype="multipart/form-data" class="a-form a-koreksi__unggah">
                    <?= csrf_field() ?>
                    <div class="a-field"><label class="a-label" for="media-koreksi">Berkas <?= esc(strtoupper($materi['jenis'])) ?> pengganti</label>
                        <input id="media-koreksi" type="file" name="media" required accept="<?= $materi['jenis'] === 'pdf' ? '.pdf,application/pdf' : ($materi['jenis'] === 'audio' ? '.mp3,.m4a,audio/*' : '.jpg,.jpeg,.png,.webp,image/*') ?>">
                        <p class="a-help">PDF 6–40 halaman dan maksimal 15 MB; audio/gambar maksimal 10 MB. Berkas lama tetap tersimpan untuk siswa versi lama.</p></div>
                    <button class="a-btn a-btn--sekunder" type="submit">Unggah ke Draf Koreksi</button>
                </form>
            <?php endif ?>
            <a class="a-btn a-btn--sekunder a-jarak-atas" href="<?= esc(site_url('guru/koreksi-tp/' . (int) $koreksi['id'] . '/media'), 'attr') ?>" target="_blank" rel="noopener noreferrer">Pratinjau Berkas Usulan</a>
        <?php endif ?>
        <?php else: ?><p>Pengaturan sudah dikirim ke Admin dan dikunci dari penyuntingan.</p>
            <dl class="a-rincian"><dt>Judul</dt><dd><?= esc($tp['judul']) ?></dd>
                <dt>KKM</dt><dd><?= (int) $tp['kkm'] ?></dd>
                <dt>Materi</dt><dd><?= esc((string) ($materi['judul'] ?? '')) ?></dd></dl>
        <?php endif ?>
    </section>
    <section class="a-card a-jarak-atas" id="daftar-soal">
        <h2>Koreksi butir soal Diagnostik dan Sumatif</h2>
        <p class="a-help">Sunting butir satu per satu, atau perbaiki hanya kuncinya. Formulir teks tidak mengganti Equation Word; soal dengan rumus Word hanya mengizinkan koreksi kunci agar rumus asli tetap aman.</p>
        <?php if ($data['soal'] === []): ?><p class="a-empty">Tidak ada soal dalam versi ini.</p><?php endif ?>
        <?php foreach ($data['soal'] as $soal): ?>
            <details class="a-word-review-item a-koreksi__soal">
                <summary><?= esc(ucfirst((string) $soal['jenis'])) ?> · Soal <?= (int) $soal['urutan'] ?> — <?= esc(mb_strimwidth((string) $soal['pertanyaan'], 0, 100, '…')) ?></summary>
                <?php if ($bolehUbah): ?>
                    <form class="a-form a-koreksi__soal-form" method="post" action="<?= esc(site_url('guru/koreksi-tp/' . (int) $koreksi['id'] . '/soal/' . (int) $soal['id']), 'attr') ?>">
                        <?= csrf_field() ?>
                        <div class="a-field"><label class="a-label" for="p-<?= (int) $soal['id'] ?>">Pertanyaan</label>
                            <textarea id="p-<?= (int) $soal['id'] ?>" name="pertanyaan" rows="3" maxlength="10000" required><?= esc((string) $soal['pertanyaan']) ?></textarea></div>
                        <?php foreach (['a','b','c','d','e'] as $huruf): ?>
                            <div class="a-field"><label class="a-label" for="opsi-<?= (int) $soal['id'] ?>-<?= $huruf ?>">Pilihan <?= strtoupper($huruf) ?></label>
                                <textarea id="opsi-<?= (int) $soal['id'] ?>-<?= $huruf ?>" name="opsi_<?= $huruf ?>" rows="2" maxlength="10000" required><?= esc((string) $soal['opsi_' . $huruf]) ?></textarea></div>
                        <?php endforeach ?>
                        <div class="a-field"><label class="a-label" for="kunci-<?= (int) $soal['id'] ?>">Kunci jawaban</label>
                            <select id="kunci-<?= (int) $soal['id'] ?>" name="kunci" required>
                                <?php foreach (['A','B','C','D','E'] as $huruf): ?>
                                    <option value="<?= $huruf ?>" <?= $soal['kunci'] === $huruf ? 'selected' : '' ?>><?= $huruf ?></option>
                                <?php endforeach ?>
                            </select></div>
                        <button type="submit" class="a-btn a-btn--utama">Simpan Draf Soal <?= (int) $soal['urutan'] ?></button>
                    </form>
                <?php else: ?>
                    <p><?= esc($soal['pertanyaan']) ?></p><p>Kunci: <strong><?= esc($soal['kunci']) ?></strong></p>
                <?php endif ?>
            </details>
        <?php endforeach ?>
    </section>
    <section class="a-card a-jarak-atas">
        <h2>Ajukan kepada Admin</h2>
        <?php if ($bolehUbah): ?>
            <p class="a-help">Simpan seluruh perubahan terlebih dahulu. Sistem memeriksa kelengkapan TP, minimal soal, KKM dan kesiapan sebelum mengirim usulan.</p>
            <form method="post" action="<?= esc(site_url('guru/koreksi-tp/' . (int) $koreksi['id'] . '/kirim'), 'attr') ?>">
                <?= csrf_field() ?>
                <button class="a-btn a-btn--utama" type="submit">Kirim Permohonan Koreksi</button>
            </form>
        <?php else: ?><p>Status: <strong><?= esc(ucfirst($koreksi['status'])) ?></strong>. <?= esc((string) ($koreksi['catatan_keputusan'] ?? 'Menunggu keputusan Admin.')) ?></p><?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>