<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru'), 'attr') ?>">Beranda Guru</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Permintaan Akses Ulang</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Guru · Izin Pengerjaan Ulang Tes Mandiri</p>
            <h1>Permintaan Akses Ulang Tes Mandiri</h1>
            <p class="a-proto-muted">Hanya percobaan siswa dari Gen–Mapel yang sedang Anda ampu.
                Jawaban dan riwayat lama tidak dihapus; tes tidak selesai tidak mendapat skor sah dan tidak mengurangi kesempatan Sumatif/remedial yang sah.</p>
        </div>
    </header>
    <?php if ($pesan = session()->getFlashdata('ujian_sukses')): ?>
        <div class="a-alert" role="status"><?= esc($pesan) ?></div>
    <?php endif ?>
    <?php if ($pesan = session()->getFlashdata('ujian_gagal')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
    <?php endif ?>

    <section class="a-card" aria-labelledby="antrean-ujian">
        <h2 id="antrean-ujian">Menunggu izin akses ulang Guru</h2>
        <?php if ($antrean === []): ?>
            <p class="a-help" role="status">Tidak ada permintaan akses ulang yang menunggu izin Guru.
                Siswa dapat memulai percobaan pengganti hanya setelah Guru memberikan izin.</p>
        <?php else: ?>
            <div class="a-table-wrap">
                <table class="a-table">
                    <thead><tr>
                        <th scope="col">Siswa</th>
                        <th scope="col">Gen / Mapel</th>
                        <th scope="col">TP</th>
                        <th scope="col">Ujian</th>
                        <th scope="col">Status</th>
                        <th scope="col">Tindakan</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($antrean as $r): ?>
                        <tr>
                            <td><?= esc((string) $r['nama_siswa']) ?></td>
                            <td><?= esc((string) $r['nama_gen']) ?>
                                · <?= esc((string) $r['nama_mapel']) ?></td>
                            <td><?= esc((string) $r['kode_tp']) ?>
                                · <?= esc((string) $r['nama_tp']) ?></td>
                            <td><?= esc(ucfirst((string) $r['jenis'])) ?>
                                <?= $r['tahap'] === 'remedial' ? '(remedial)' : '(normal)' ?>
                                #<?= (int) $r['nomor_percobaan'] ?></td>
                            <td><?= $r['keputusan'] === 'tunda_tinjauan'
                                ? 'Ditunda untuk tinjauan' : 'Menunggu izin Guru' ?></td>
                            <td>
                                <a class="a-btn a-btn--sekunder"
                                    href="<?= esc(site_url('guru/akses-ulang/' . (int) $r['id']), 'attr') ?>">
                                    Lihat permintaan
                                </a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </section>

    <?php if ($pilih !== null): ?>
        <section class="a-card a-jarak-atas" aria-labelledby="rincian-ujian-batal">
            <h2 id="rincian-ujian-batal">Rincian percobaan #<?= (int) $pilih['id'] ?></h2>
            <p><strong><?= esc((string) $pilih['nama_siswa']) ?></strong>
                · <?= esc((string) $pilih['nama_gen']) ?> · <?= esc((string) $pilih['nama_mapel']) ?></p>
            <p><?= esc((string) $pilih['kode_tp']) ?> — <?= esc((string) $pilih['nama_tp']) ?></p>
            <p class="a-help">Jenis <?= esc((string) $pilih['jenis']) ?>
                · tahap <?= esc((string) $pilih['tahap']) ?>
                · percobaan #<?= (int) $pilih['nomor_percobaan'] ?>
                · versi konten #<?= (int) $pilih['versi_konten_tp_id'] ?></p>
            <p class="a-help">Penyebab tes tidak selesai: <?= esc((string) ($pilih['alasan_batal'] ?: 'Insiden ujian Ketat.')) ?></p>
            <p class="a-help">Waktu akhir: <?= esc((string) ($pilih['waktu_selesai'] ?? 'Belum tercatat')) ?> UTC.</p>
            <div class="a-alert" role="status">Tes mandiri ini tidak selesai dan tidak memperoleh skor sah.
                Izin akses ulang tidak mengubah skor, butir atau jawaban historis.</div>

            <?php $sudah = $pilih['keputusan']; ?>
            <?php if ($sudah !== null && $sudah['keputusan'] !== 'tunda_tinjauan'): ?>
                <p class="a-alert" role="status">
                    Izin tersimpan: <strong><?= esc(str_replace('_', ' ', (string) $sudah['keputusan'])) ?></strong>.
                    Izin digunakan: <?= $sudah['digunakan_pada'] === null
                        ? 'Belum' : esc((string) $sudah['digunakan_pada']) . ' UTC' ?>.
                    Izin ini tidak dapat diubah melalui halaman ini.
                </p>
            <?php else: ?>
                <?php if ($sudah !== null): ?>
                    <div class="a-alert">Tinjauan ditunda. Guru dapat melanjutkan dengan keputusan
                        <strong>Ulang Sumatif</strong> setelah pemeriksaan.</div>
                <?php endif ?>
                <form method="post" class="a-form a-form-penilaian"
                    action="<?= esc(site_url('guru/akses-ulang/' . (int) $pilih['id']), 'attr') ?>">
                    <?= csrf_field() ?>
                    <?php $aksiIzin = $pilihanKeputusan[0] ?? ''; ?>
                    <input type="hidden" name="keputusan" value="<?= esc($aksiIzin, 'attr') ?>">
                    <p class="a-help">Guru cukup memberikan izin untuk mengulangi
                        <?= esc(ucfirst((string) $pilih['jenis'])) ?>
                        <?= $pilih['tahap'] === 'remedial' ? '(remedial)' : '(tes normal)' ?>.
                        Penyebab tes tidak selesai, waktu, dan identitas pemberi izin
                        tercatat otomatis. Tidak perlu mengisi alasan.</p>
                    <button type="submit" class="a-btn a-btn--utama"
                        <?= $aksiIzin === '' ? 'disabled' : '' ?>
                        onclick="return confirm('Izinkan siswa mengulangi tahap tes yang tidak selesai? Izin hanya sekali pakai.');">
                        Izinkan Akses Ulang
                    </button>
                </form>
            <?php endif ?>
            <p class="a-jarak-atas">
                <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/akses-ulang'), 'attr') ?>">
                    Kembali ke antrean
                </a>
            </p>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
