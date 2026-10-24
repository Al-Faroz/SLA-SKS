<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru'), 'attr') ?>">Beranda Guru</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('guru/peta-atp'), 'attr') ?>">Peta ATP</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Rincian TP</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Guru · Pembelajaran · Semester <?= (int) $paket['semester'] ?></p>
            <h1><?= esc($tp['judul']) ?></h1>
            <p class="a-proto-muted">Rincian TP dalam paket semester versi <?= (int) $paket['nomor_versi'] ?>. Pembacaan dibatasi pada Gen–Mapel yang Anda ampu.</p>
        </div>
    </header>
    <section class="a-card" aria-labelledby="identitas-tp">
        <h2 id="identitas-tp">Identitas TP</h2>
        <div class="a-tabel-gulir">
            <table class="a-tbl">
                <tbody>
                    <tr><th scope="row">Kode TP</th><td><?= esc($tp['kode_tp']) ?></td></tr>
                    <tr><th scope="row">Urutan ATP</th><td><?= (int) $tp['urutan_atp'] ?></td></tr>
                    <tr><th scope="row">Semester</th><td><?= (int) $paket['semester'] ?></td></tr>
                    <tr><th scope="row">Status TP</th><td><span class="a-badge"><?= esc($tp['status']) ?></span></td></tr>
                    <tr><th scope="row">Jumlah Materi/Submateri</th><td><?= (int) $jumlah_materi ?></td></tr>
                </tbody>
            </table>
        </div>
    </section>
    <section class="a-card a-jarak-atas" aria-labelledby="rumusan-tp">
        <h2 id="rumusan-tp">Rumusan dan indikator</h2>
        <?php if (session()->getFlashdata('sukses')): ?>
            <div class="a-alert" role="status"><?= esc(session()->getFlashdata('sukses')) ?></div>
        <?php endif ?>
        <?php if (session()->getFlashdata('galat')): ?>
            <div class="a-alert" role="alert"><?= esc(session()->getFlashdata('galat')) ?></div>
        <?php endif ?>
        <?php if ($tp['status'] === 'draf'): ?>
            <form method="post" action="<?= esc(site_url('guru/tp/' . (int) $tp['id']), 'attr') ?>" class="a-form">
                <?= csrf_field() ?>
                <input type="hidden" name="struktur_versi" value="<?= (int) $tp['struktur_versi'] ?>">
                <div class="a-field">
                    <label for="tp-judul" class="a-label">Judul TP</label>
                    <input class="a-input" id="tp-judul" type="text" name="judul" maxlength="180" required value="<?= esc($tp['judul'], 'attr') ?>">
                </div>
                <div class="a-field">
                    <label for="tp-rumusan" class="a-label">Rumusan TP</label>
                    <textarea id="tp-rumusan" name="rumusan" rows="5" maxlength="20000" required><?= esc($tp['rumusan'] ?? '') ?></textarea>
                </div>
                <div class="a-field">
                    <label for="tp-indikator" class="a-label">Indikator ketercapaian</label>
                    <textarea id="tp-indikator" name="indikator" rows="5" maxlength="20000" required><?= esc($tp['indikator'] ?? '') ?></textarea>
                </div>
                <button class="a-btn a-btn--utama" type="submit">Simpan draf TP</button>
            </form>
        <?php else: ?>
            <div class="a-alert" role="status">TP sudah Siap. Perubahan melalui editor draf tidak diizinkan.</div>
            <h3>Rumusan TP</h3>
            <p><?= nl2br(esc($tp['rumusan'] ?? '')) ?></p>
            <h3>Indikator</h3>
            <p><?= nl2br(esc($tp['indikator'] ?? '')) ?></p>
        <?php endif ?>
        <?php if ($jumlah_materi === 0): ?>
            <div class="a-empty" role="status">
                <strong>Belum ada Materi/Submateri.</strong>
                <p>Struktur TP sudah terdaftar, tetapi belum memiliki materi. Penyusunan materi akan tersedia dalam tahap berikutnya.</p>
            </div>
        <?php endif ?>
        <p class="a-help">Penyuntingan ini hanya untuk redaksi TP draf. Perubahan urutan ATP, tautan CP, struktur Materi dan publikasi menggunakan alur tersendiri.</p>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/peta-atp'), 'attr') ?>">Kembali ke Peta ATP</a>
    </section>
    <section class="a-card a-jarak-atas" aria-labelledby="daftar-materi">
        <h2 id="daftar-materi">Daftar Materi/Submateri</h2>
        <?php if ($materi === []): ?>
            <div class="a-empty" role="status"><p>Belum ada Materi/Submateri untuk TP ini.</p></div>
        <?php else: ?>
            <div class="a-tabel-gulir">
                <table class="a-tbl">
                    <thead>
                        <tr>
                            <th scope="col">No.</th>
                            <th scope="col">Judul</th>
                            <th scope="col">Jenis</th>
                            <th scope="col">Status</th>
                            <th scope="col">Pratinjau</th>
                            <th scope="col">Urutan</th>
                            <th scope="col">Edit</th>
                            <th scope="col">Hapus</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materi as $item): ?>
                            <tr>
                                <td><?= (int) $item['urutan'] ?></td>
                                <td><?= esc($item['judul']) ?></td>
                                <td><?= esc(strtoupper((string) $item['jenis'])) ?></td>
                                <td><span class="a-badge"><?= (int) $item['aktif'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                                <td class="a-materi-aksi">
                                    <?php if (in_array($item['jenis'], ['pdf', 'gambar', 'audio'], true) && $item['berkas_id'] !== null): ?>
                                        <a class="a-btn a-btn--sekunder" target="_blank" rel="noopener noreferrer" href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/materi/' . (int) $item['id'] . '/pratinjau'), 'attr') ?>">Pratinjau</a>
                                    <?php elseif ($item['jenis'] === 'youtube' && ! empty($item['url_youtube'])): ?>
                                        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/materi/' . (int) $item['id'] . '/pratinjau'), 'attr') ?>">Pratinjau</a>
                                    <?php else: ?>
                                        <span class="a-help">Belum ada file</span>
                                    <?php endif ?>
                                </td>
                                <td class="a-materi-aksi">
                                    <?php if ($tp['status'] === 'draf'): ?>
                                        <div class="a-materi-urutan">
                                            <?php foreach (['naik' => 'Naik', 'turun' => 'Turun'] as $arah => $label): ?>
                                                <form method="post" action="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/materi/' . (int) $item['id'] . '/urut'), 'attr') ?>">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="struktur_versi" value="<?= (int) $tp['struktur_versi'] ?>">
                                                    <input type="hidden" name="arah" value="<?= esc($arah, 'attr') ?>">
                                                    <button type="submit" class="a-btn a-btn--sekunder"
                                                        <?= ($arah === 'naik' && (int) $item['urutan'] === 1) || ($arah === 'turun' && (int) $item['urutan'] === count($materi)) ? 'disabled' : '' ?>><?= esc($label) ?></button>
                                                </form>
                                            <?php endforeach ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="a-help">Terkunci</span>
                                    <?php endif ?>
                                </td>
                                <td class="a-materi-aksi">
                                    <?php if ($tp['status'] === 'draf'): ?>
                                        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/materi/' . (int) $item['id'] . '/edit'), 'attr') ?>">Sunting</a>
                                    <?php else: ?>
                                        <span class="a-help">Terkunci</span>
                                    <?php endif ?>
                                </td>
                                <td class="a-materi-aksi">
                                    <?php if ($tp['status'] === 'draf'): ?>
                                        <form method="post" action="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/materi/' . (int) $item['id'] . '/hapus'), 'attr') ?>"
                                            data-materi-hapus-form>
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="struktur_versi" value="<?= (int) $tp['struktur_versi'] ?>">
                                            <input type="hidden" name="konfirmasi" value="HAPUS">
                                            <button type="submit" class="a-btn a-btn--sekunder" data-materi-hapus
                                                data-judul="<?= esc($item['judul'], 'attr') ?>">Hapus</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="a-help">Terkunci</span>
                                    <?php endif ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </section>
    <?php if ($tp['status'] === 'draf'): ?>
        <section class="a-card a-jarak-atas" aria-labelledby="tambah-materi">
            <h2 id="tambah-materi">Tambah Materi/Submateri</h2>
            <?php $pesanMateriGagal = session()->getFlashdata('materi_galat'); ?>
            <?php $pesanMateriSukses = session()->getFlashdata('materi_sukses'); ?>
            <?php $nilaiMateriLama = session()->getFlashdata('materi_input_lama'); ?>
            <?php $kolomMateriGalat = (string) (session()->getFlashdata('materi_field_galat') ?? ''); ?>
            <?php $nilaiMateriTambah = is_array($nilaiMateriLama) ? $nilaiMateriLama : []; ?>
            <?php if (is_string($pesanMateriGagal) && $pesanMateriGagal !== ''): ?>
                <div class="a-alert" role="alert">
                    <strong>Materi belum disimpan:</strong> <?= esc($pesanMateriGagal) ?>
                </div>
            <?php endif ?>
            <?php if (is_string($pesanMateriSukses) && $pesanMateriSukses !== ''): ?>
                <div class="a-alert" role="status"><?= esc($pesanMateriSukses) ?></div>
            <?php endif ?>
            <p class="a-help">Pilih YouTube untuk mengisi URL, atau PDF/Gambar/Audio untuk memilih file. Simpan menghasilkan Materi Nonaktif; file dan data Materi hanya disimpan bila semua pemeriksaan berhasil.</p>
            <?php if (is_string($pesanMateriGagal) && $pesanMateriGagal !== '' && in_array(($nilaiMateriTambah['jenis'] ?? ''), ['pdf', 'gambar', 'audio'], true)): ?>
                <p class="a-help"><strong>Perlu pilih ulang file:</strong> Demi keamanan, browser tidak dapat mempertahankan pilihan file setelah pengiriman gagal. Judul, waktu, petunjuk Resume dan HOTS tetap dipulihkan.</p>
            <?php endif ?>
            <form method="post" class="a-form" enctype="multipart/form-data" data-materi-form data-materi-sudah-upload="0" action="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/materi'), 'attr') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="struktur_versi" value="<?= (int) $tp['struktur_versi'] ?>">
                <?= view('guru/_materi_fields', ['nilai' => $nilaiMateriTambah, 'galatKolom' => $kolomMateriGalat]) ?>
                <button class="a-btn a-btn--utama" type="submit" data-materi-submit>Tambah Materi draf</button>
                <p class="a-help" data-materi-status aria-live="polite">Belum disimpan.</p>
            </form>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
