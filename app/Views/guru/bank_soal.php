<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru/peta-atp'), 'attr') ?>">Peta ATP</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('guru/tp/' . (int) $tp['id']), 'attr') ?>">Rincian TP</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Bank Soal</span>
    </nav>
    <header class="a-page__heading">
        <div>
            <p class="a-proto-eyebrow">Guru · Bank Soal TP</p>
            <h1>Bank Soal Diagnostik dan Sumatif</h1>
            <p class="a-proto-muted"><?= esc($tp['kode_tp'] . ' — ' . $tp['judul']) ?></p>
        </div>
    </header>
    <section class="a-card" aria-labelledby="bank-soal-status">
        <h2 id="bank-soal-status">Ketersediaan bank soal</h2>
        <p class="a-help">Ringkasan hanya menampilkan jumlah soal aktif, tanpa pertanyaan atau kunci jawaban. Tahap ini belum menyediakan pembuatan, unduhan Word, atau impor soal.</p>
        <div class="a-tabel-gulir">
            <table class="a-tbl">
                <thead><tr><th scope="col">Jenis ujian</th><th scope="col">Soal aktif</th><th scope="col">Jumlah tayang</th><th scope="col">Status jumlah</th></tr></thead>
                <tbody>
                    <?php foreach ($ringkasan_bank as $jenis => $data): ?>
                        <tr>
                            <th scope="row"><?= esc(ucfirst($jenis)) ?></th>
                            <td><?= (int) $data['aktif'] ?></td>
                            <td><?= (int) $data['target'] ?></td>
                            <td><?= $data['memadai'] ? 'Jumlah mencukupi' : 'Belum mencukupi' ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <p class="a-help">Jumlah yang mencukupi belum berarti soal siap dipublikasikan: isi soal, opsi A–E, kunci dan aset tetap harus diperiksa.</p>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/tp/' . (int) $tp['id']), 'attr') ?>">Kembali ke Rincian TP</a>
    </section>
</div>
<?= $this->endSection() ?>
