<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru'), 'attr') ?>">Beranda Guru</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Ujian Dibatalkan</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Guru · Penanganan Ujian Ketat</p>
            <h1>Ujian yang Dibatalkan</h1>
            <p class="a-proto-muted">Hanya percobaan siswa dari Gen–Mapel yang sedang Anda ampu.
                Jawaban dan riwayat lama tidak dihapus; ujian batal tidak memperoleh skor.</p>
        </div>
    </header>
    <?php if ($pesan = session()->getFlashdata('ujian_sukses')): ?>
        <div class="a-alert" role="status"><?= esc($pesan) ?></div>
    <?php endif ?>
    <?php if ($pesan = session()->getFlashdata('ujian_gagal')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
    <?php endif ?>

    <section class="a-card" aria-labelledby="antrean-ujian">
        <h2 id="antrean-ujian">Menunggu keputusan Guru</h2>
        <?php if ($antrean === []): ?>
            <p class="a-help" role="status">Tidak ada ujian dibatalkan yang masih memerlukan keputusan.
                Jika Guru telah menyetujui ulang, siswa dapat memulai percobaan pengganti.</p>
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
                                ? 'Ditunda untuk tinjauan' : 'Menunggu keputusan' ?></td>
                            <td>
                                <a class="a-btn a-btn--sekunder"
                                    href="<?= esc(site_url('guru/ujian-batal/' . (int) $r['id']), 'attr') ?>">
                                    Tinjau ujian
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
            <p class="a-help">Alasan pembatalan: <?= esc((string) ($pilih['alasan_batal'] ?: 'Insiden ujian Ketat.')) ?></p>
            <p class="a-help">Waktu akhir: <?= esc((string) ($pilih['waktu_selesai'] ?? 'Belum tercatat')) ?> UTC.</p>
            <div class="a-alert" role="status">Percobaan ini dibatalkan tanpa skor.
                Keputusan ulang tidak mengubah skor, butir atau jawaban historis.</div>

            <?php $sudah = $pilih['keputusan']; ?>
            <?php if ($sudah !== null && $sudah['keputusan'] !== 'tunda_tinjauan'): ?>
                <p class="a-alert" role="status">
                    Keputusan tersimpan: <strong><?= esc(str_replace('_', ' ', (string) $sudah['keputusan'])) ?></strong>.
                    Izin digunakan: <?= $sudah['digunakan_pada'] === null
                        ? 'Belum' : esc((string) $sudah['digunakan_pada']) . ' UTC' ?>.
                    Keputusan final tidak dapat diubah melalui halaman ini.
                </p>
            <?php else: ?>
                <?php if ($sudah !== null): ?>
                    <div class="a-alert">Tinjauan ditunda. Guru dapat melanjutkan dengan keputusan
                        <strong>Ulang Sumatif</strong> setelah pemeriksaan.</div>
                <?php endif ?>
                <form method="post" class="a-form a-form-penilaian"
                    action="<?= esc(site_url('guru/ujian-batal/' . (int) $pilih['id']), 'attr') ?>">
                    <?= csrf_field() ?>
                    <div class="a-field">
                        <label class="a-label" for="keputusan-ujian">Keputusan Guru</label>
                        <select class="a-input" id="keputusan-ujian" name="keputusan" required>
                            <option value="">Pilih keputusan</option>
                            <?php foreach ($pilihanKeputusan as $aksi): ?>
                                <option value="<?= esc($aksi, 'attr') ?>" <?= old('keputusan') === $aksi ? 'selected' : '' ?>>
                                    <?= esc(match ($aksi) {
                                        'ulang_diagnostik' => 'Izinkan Ulang Diagnostik',
                                        'langsung_belajar' => 'Langsung Belajar (tanpa skor Diagnostik)',
                                        'ulang_sumatif' => 'Izinkan Ulang Sumatif',
                                        'tunda_tinjauan' => 'Tunda untuk Tinjauan',
                                        default => $aksi,
                                    }) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="a-field">
                        <label class="a-label" for="alasan-keputusan">Alasan keputusan</label>
                        <textarea class="a-input" id="alasan-keputusan" name="alasan"
                            maxlength="255" rows="4" required><?= esc((string) old('alasan')) ?></textarea>
                        <p class="a-help">Maksimal 255 karakter. Alasan, waktu, dan Guru pencatat
                            disimpan dalam riwayat audit.</p>
                    </div>
                    <button type="submit" class="a-btn a-btn--utama"
                        onclick="return confirm('Simpan keputusan ujian ini? Persetujuan ulang hanya sekali pakai.');">
                        Simpan keputusan
                    </button>
                </form>
            <?php endif ?>
            <p class="a-jarak-atas">
                <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/ujian-batal'), 'attr') ?>">
                    Kembali ke antrean
                </a>
            </p>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
