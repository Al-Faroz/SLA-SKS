<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-sertifikat">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('admin'),'attr') ?>">Beranda Admin</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Pemeriksaan Sertifikat</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Admin · Sertifikat Akademik</p>
            <h1>Pemeriksaan Kelayakan Sertifikat</h1>
            <p class="a-proto-muted">Pantau ketuntasan TP dan nilai Final; penerbitan dan pembaruan berlangsung otomatis saat siswa mengakses Sertifikat.
                Pra-Semester bukan syarat dan nilainya tidak dicantumkan dalam sertifikat.</p>
        </div>
    </header>
    <?php if ($sukses = session()->getFlashdata('sukses_sertifikat')): ?>
        <div class="a-alert a-alert--sukses" role="status"><?= esc($sukses) ?></div>
    <?php endif ?>
    <?php if ($pesan = session()->getFlashdata('galat_sertifikat')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
    <?php endif ?>
    <section class="a-card a-jarak-atas" aria-labelledby="siap-administrasi">
        <h2 id="siap-administrasi">Kesiapan Administratif</h2>
        <div class="a-sertifikat__status">
            <div class="a-sertifikat__status-item">
                <span>Identitas Madrasah</span>
                <strong><?= $identitas['lengkap'] ? 'Lengkap' : 'Perlu dilengkapi' ?></strong>
                <?php if (!$identitas['lengkap']): ?>
                    <p class="a-help"><?= esc(implode(', ',$identitas['kekurangan'])) ?></p>
                <?php endif ?>
                <a href="<?= esc(site_url('admin/identitas'),'attr') ?>" class="a-btn a-btn--sekunder">
                    Kelola Identitas
                </a>
            </div>
            <div class="a-sertifikat__status-item">
                <span>Tanda Tangan PNG dan Template</span>
                <strong><?= isset($asetAktif['ttd']) ? 'TTD aktif — siap'
                    : 'TTD belum aktif' ?></strong>
                <p class="a-help">Background:
                    <?= isset($asetAktif['background']) ? 'Template unggahan aktif' :
                        'Template bawaan (fallback)' ?>.
                    TTD PNG wajib untuk penerbitan.</p>
                <a class="a-btn a-btn--sekunder" href="<?= esc(site_url(
                    'admin/sertifikat/aset'),'attr') ?>">Kelola Template &amp; TTD</a>
            </div>
        </div>
        <div class="a-alert" role="status">
            Penerbitan memerlukan semua TP NonEsensial selesai dan Nilai TP Final,
            identitas madrasah lengkap serta TTD PNG aktif.
            Pra-Semester tidak menjadi syarat dan nilainya tidak dicetak.
            Perubahan isi cetak akan menghasilkan versi pengganti saat siswa mengakses Sertifikat; versi lama tetap arsip.
        </div>
    </section>
    <section class="a-card a-jarak-atas" aria-labelledby="pilih-paket-sertifikat">
        <h2 id="pilih-paket-sertifikat">Pilih Gen, Mata Pelajaran dan Semester</h2>
        <?php if ($daftarPaket === []): ?>
            <div class="a-alert" role="status">Belum ada paket akademik aktif yang ditugaskan.</div>
        <?php else: ?>
            <form method="get" action="<?= esc(site_url('admin/sertifikat'),'attr') ?>"
                class="a-form a-sertifikat__filter">
                <div class="a-field">
                    <label class="a-label" for="pilih-paket-sertifikat-input">Paket Akademik</label>
                    <select class="a-input" id="pilih-paket-sertifikat-input" name="paket" required>
                        <?php foreach ($daftarPaket as $p): ?>
                            <option value="<?= (int)$p['id'] ?>"
                                <?= (int)($terpilih['id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>>
                                <?= esc($p['nama_gen'].' — '.$p['nama_mapel'].' — Semester '.$p['semester']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <button class="a-btn a-btn--utama" type="submit">Periksa Kelayakan</button>
            </form>
        <?php endif ?>
    </section>
    <?php if ($rekap !== null): ?>
        <section class="a-card a-jarak-atas" aria-labelledby="hasil-sertifikat">
            <h2 id="hasil-sertifikat">Hasil Pemeriksaan <?= esc($rekap['paket']['nama_gen']) ?></h2>
            <p class="a-help"><?= esc($rekap['paket']['nama_mapel']) ?> ·
                Semester <?= (int)$rekap['paket']['semester'] ?> ·
                <?= (int)$rekap['total'] ?> siswa ·
                <?= (int)$rekap['total_tp'] ?> TP tercatat
                (<?= (int)$rekap['total_non_esensial'] ?> NonEsensial).
                Halaman <?= (int)$rekap['halaman'] ?> dari <?= (int)$rekap['jumlah_halaman'] ?>.</p>
            <?php if ($rekap['baris'] === []): ?>
                <div class="a-alert" role="status">Belum ada siswa pada Gen ini.</div>
            <?php else: ?>
                <div class="a-tabel-gulir a-sertifikat__scroll" tabindex="0"
                    role="region" aria-label="Tabel pemeriksaan sertifikat dapat digulir">
                    <table class="a-tbl a-sertifikat__table">
                        <thead>
                            <tr>
                                <th scope="col">Siswa</th>
                                <th scope="col">TP NonEsensial Selesai Sah</th>
                                <th scope="col">Nilai TP Final</th>
                                <th scope="col">Kelayakan Akademik</th>
                                <th scope="col">Keterangan</th>
                                <th scope="col">Status Sertifikat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rekap['baris'] as $baris): ?>
                                <tr>
                                    <th scope="row"><?= esc($baris['nama_siswa']) ?></th>
                                    <td><?= (int)$baris['tp_selesai'] ?> /
                                        <?= (int)$baris['total_tp'] ?></td>
                                    <td><?= (int)$baris['nilai_final'] ?> /
                                        <?= (int)$baris['total_tp'] ?></td>
                                    <td>
                                        <span class="a-sertifikat__badge <?= $baris['layak_akademik']
                                            ? 'a-sertifikat__badge--layak' : 'a-sertifikat__badge--tahan' ?>">
                                            <?= $baris['layak_akademik']
                                                ? 'Layak akademik' : 'Belum layak' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($baris['alasan'] === []): ?>
                                            <span class="a-help">Semua nilai wajib TP sudah Final.</span>
                                        <?php else: ?>
                                            <details class="a-sertifikat__alasan">
                                                <summary><?= count($baris['alasan']) ?>
                                                    kondisi perlu ditindaklanjuti</summary>
                                                <ul>
                                                    <?php foreach ($baris['alasan'] as $alasan): ?>
                                                        <li><?= esc($alasan) ?></li>
                                                    <?php endforeach ?>
                                                </ul>
                                            </details>
                                        <?php endif ?>
                                    </td>
                                    <td class="a-sertifikat__aksi">
                                        <?php $terbit = $sudahTerbit[(int)$baris['siswa_id']] ?? null; ?>
                                        <?php if ($terbit !== null): ?>
                                            <span class="a-sertifikat__badge a-sertifikat__badge--layak">
                                                Versi <?= (int)$terbit['versi'] ?> berlaku
                                            </span>
                                            <a class="a-btn a-btn--sekunder" href="<?= esc(
                                                site_url('admin/sertifikat/'.(int)$terbit['id'].'/unduh'),'attr') ?>">
                                                Unduh PDF
                                            </a>
                                            <p class="a-help">Pembaruan versi otomatis pada akses berikutnya oleh siswa.</p>
                                        <?php elseif ($baris['layak_akademik'] && $identitas['lengkap']
                                            && isset($asetAktif['ttd'])): ?>
                                            <span class="a-help">Siap diterbitkan otomatis ketika siswa membuka Sertifikat.</span>
                                        <?php else: ?>
                                            <span class="a-help">Menunggu seluruh syarat.</span>
                                        <?php endif ?>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
            <?php if ($rekap['jumlah_halaman'] > 1): ?>
                <nav class="a-penilaian-pager" aria-label="Halaman pemeriksaan sertifikat">
                    <?php $halamanUrl = static fn (int $n): string =>
                        site_url('admin/sertifikat').'?'.http_build_query([
                            'paket'=>$rekap['paket']['id'],'halaman'=>$n,
                        ]); ?>
                    <?php if ($rekap['halaman'] > 1): ?>
                        <a class="a-btn a-btn--sekunder"
                            href="<?= esc($halamanUrl($rekap['halaman']-1),'attr') ?>">Sebelumnya</a>
                    <?php endif ?>
                    <strong>Halaman <?= (int)$rekap['halaman'] ?></strong>
                    <?php if ($rekap['halaman'] < $rekap['jumlah_halaman']): ?>
                        <a class="a-btn a-btn--sekunder"
                            href="<?= esc($halamanUrl($rekap['halaman']+1),'attr') ?>">Berikutnya</a>
                    <?php endif ?>
                </nav>
            <?php endif ?>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
