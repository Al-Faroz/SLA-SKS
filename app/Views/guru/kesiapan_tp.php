<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru/peta-atp'), 'attr') ?>">Peta ATP</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('guru/tp/' . (int) $tp['id']), 'attr') ?>">Rincian TP</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Kesiapan TP</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Guru · Pemeriksaan akhir TP</p>
            <h1>Kesiapan TP Siap</h1>
            <p class="a-proto-muted"><?= esc($tp['kode_tp'] . ' — ' . $tp['judul']) ?></p>
        </div>
    </header>
    <section class="a-card" aria-labelledby="judul-ringkasan-kesiapan">
        <h2 id="judul-ringkasan-kesiapan">Ringkasan pemeriksaan</h2>
        <?php if ($pesan = session()->getFlashdata('sukses_siapan')): ?>
            <div class="a-alert" role="status"><?= esc($pesan) ?></div>
        <?php endif ?>
        <?php if ($pesan = session()->getFlashdata('galat_siapan')): ?>
            <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
        <?php endif ?>
        <?php $jumlahLolos = count(array_filter($cek, static fn (array $baris): bool => $baris['terpenuhi'])); ?>
        <div class="a-kesiapan-ringkasan">
            <div><strong><?= $jumlahLolos ?> / <?= count($cek) ?></strong><span>Ketentuan terpenuhi</span></div>
            <div><strong><?= count($materi) ?></strong><span>Materi (maksimal satu)</span></div>
            <div><strong><?= count($soal) ?></strong><span>Soal aktif</span></div>
            <div><strong><?= $tp['status'] === 'siap' ? 'Siap' : ($siap ? 'Lengkap' : 'Draf') ?></strong><span>Status pemeriksaan</span></div>
        </div>
        <?php if ($tp['status'] === 'siap'): ?>
            <div class="a-alert" role="status">
                <strong>TP sudah Siap.</strong>
                <?php if ($versi_terbit): ?>
                    Versi konten <?= (int) $versi_terbit['nomor_versi'] ?> disimpan
                    pada <?= esc((string) $versi_terbit['disiapkan_pada']) ?> (UTC).
                <?php endif ?>
                <?php if ($suntingDikunci): ?>
                    TP telah digunakan siswa. Rincian, KKM, Materi, Resume/HOTS,
                    dan Bank Soal terkunci untuk menjaga riwayat pembelajaran.
                <?php else: ?>
                    TP belum digunakan siswa. Guru dapat memilih Sunting TP
                    pada Rincian TP untuk kembali ke Draf dan menerbitkan versi baru.
                <?php endif ?>
            </div>
        <?php elseif ($siap): ?>
            <div class="a-alert" role="status">
                Semua pemeriksaan otomatis memenuhi ketentuan. Tinjau konten akademik,
                lalu konfirmasikan sebelum TP diterbitkan.
            </div>
        <?php else: ?>
            <div class="a-alert a-alert--bahaya" role="status">
                TP belum dapat disiapkan. Perbaiki baris yang belum lengkap melalui tautan
                di daftar pemeriksaan.
            </div>
        <?php endif ?>
    </section>

    <section class="a-card a-jarak-atas" aria-labelledby="judul-tahapan-final">
        <h2 id="judul-tahapan-final">Step Progress penyiapan TP</h2>
        <p class="a-help"><?php if ($tp['status'] === 'siap'): ?>
            Semua langkah penerbitan telah diperiksa. Rincian berikut merupakan
            status konten yang sudah diterbitkan, bukan izin untuk menyuntingnya.
            <?php else: ?>
            Langkah yang belum lengkap dapat dibuka langsung.
            Status ini berasal dari pemeriksaan Service yang sama dengan keputusan publikasi.
            <?php endif ?></p>
        <?= view('guru/_step_progress_tp', ['langkah' => $langkah]) ?>
    </section>

    <section class="a-card a-jarak-atas" aria-labelledby="judul-checklist-akhir">
        <h2 id="judul-checklist-akhir">Daftar pemeriksaan final</h2>
        <p class="a-help">Pemeriksaan meliputi CP resmi, Materi, media,
            bank soal Diagnostik/Sumatif, dan pengaturan ujian.
            <?= $tp['status'] === 'siap'
                ? 'Konten TP sudah diterbitkan; hasil ini adalah informasi pemeriksaan.'
                : 'Publikasi memerlukan konfirmasi Guru setelah seluruh syarat terpenuhi.' ?></p>
        <details class="a-tp-kesiapan-detail">
            <summary class="a-btn a-btn--sekunder">Lihat rincian ketentuan (<?= count($cek) ?> pemeriksaan)</summary>
        <div class="a-tabel-gulir">
            <table class="a-tbl a-tbl--cards a-kesiapan-tabel">
                <thead>
                    <tr><th scope="col">Ketentuan</th><th scope="col">Status</th>
                        <th scope="col">Keterangan / tindak lanjut</th></tr>
                </thead>
                <tbody>
                <?php foreach ($cek as $item): ?>
                    <tr>
                        <th scope="row" data-label="Ketentuan"><?= esc($item['label']) ?></th>
                        <td data-label="Status">
                            <span class="a-tp-status <?= $item['terpenuhi'] ? 'is-lengkap' : 'is-kurang' ?>">
                                <?= $item['terpenuhi'] ? 'Terpenuhi' : 'Perlu perbaikan' ?>
                            </span>
                        </td>
                        <td data-label="Tindak lanjut">
                            <?= esc($item['keterangan']) ?>
                            <?php if (!$item['terpenuhi'] && $tp['status'] === 'draf'): ?>
                                <div><a href="<?= esc(site_url($item['tautan']), 'attr') ?>">Periksa bagian ini →</a></div>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
        </details>
    </section>

    <?php if ($tp['status'] === 'draf'): ?>
    <section class="a-card a-jarak-atas" aria-labelledby="judul-konfirmasi-tp">
        <h2 id="judul-konfirmasi-tp">Konfirmasi Guru</h2>
        <?php if ($siap): ?>
            <p>Menyiapkan TP akan membuat snapshot materi, CP, soal, kunci,
                dan pengaturan. Setelah digunakan siswa, konten TP terkunci. Sebelum digunakan,
                Guru dapat membuka kembali editor melalui tombol Sunting TP.</p>
            <form class="a-form" action="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/kesiapan'), 'attr') ?>"
                method="post" onsubmit="return confirm('TP akan diterbitkan dan dikunci. Lanjutkan?');">
                <?= csrf_field() ?>
                <input type="hidden" name="struktur_versi" value="<?= (int) $tp['struktur_versi'] ?>">
                <input type="hidden" name="sidik_jari" value="<?= esc($sidik_jari, 'attr') ?>">
                <label class="a-kesiapan-konfirmasi">
                    <input type="checkbox" name="tinjau_akademik" value="ya" required>
                    Saya sudah meninjau dan menyetujui keakuratan materi, CP,
                    petunjuk kegiatan, seluruh soal dan kunci jawaban.
                </label>
                <button class="a-btn a-btn--utama" type="submit">Konfirmasi &amp; Jadikan TP Siap</button>
            </form>
        <?php else: ?>
            <p class="a-help">Tindakan menjadikan TP Siap belum tersedia sampai semua
                pemeriksaan di atas terpenuhi. Ini mencegah penerbitan konten yang belum lengkap.</p>
        <?php endif ?>
    </section>
    <?php endif ?>
    <p class="a-kesiapan-kembali">
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/tp/' . (int) $tp['id']), 'attr') ?>">Kembali ke rincian TP</a>
    </p>
</div>
<?= $this->endSection() ?>
