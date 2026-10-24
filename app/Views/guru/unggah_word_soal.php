<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
  <nav class="a-breadcrumb" aria-label="Breadcrumb"><a href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal'), 'attr') ?>">Bank Soal</a><span aria-hidden="true">/</span><span aria-current="page">Pratinjau Word</span></nav>
  <header class="a-page__heading"><div><h1>Hasil pemeriksaan Word <?= esc(ucfirst($jenis)) ?></h1><p class="a-proto-muted"><?= esc($tp['kode_tp']) ?></p></div></header>
  <section class="a-card">
    <?php if ($pesan !== null): ?>
      <div role="alert" class="a-alert a-alert--bahaya"><?= esc($pesan) ?></div>
    <?php elseif ($pratinjau !== null): ?>
      <div role="status" class="a-alert">File Word diterima dan <?= (int) $pratinjau['jumlah'] ?> soal beserta opsi A–E serta kuncinya lolos pemeriksaan struktur.</div>
      <h2>Komponen konten kaya terdeteksi</h2>
      <div class="a-tabel-gulir"><table class="a-tbl"><thead><tr><th scope="col">Komponen</th><th scope="col">Jumlah</th></tr></thead><tbody>
      <?php foreach ($pratinjau['kaya'] as $komponen => $jumlah): ?>
        <tr><th scope="row"><?= esc(str_replace('_', ' ', $komponen)) ?></th><td><?= (int) $jumlah ?></td></tr>
      <?php endforeach ?>
      </tbody></table></div>
      <div role="status" class="a-alert a-alert--bahaya"><strong>Belum siap diimpor.</strong> Pratinjau di bawah adalah ekstraksi teks; gambar, rumus, dan tabel bertingkat belum ditampilkan setia sesuai Word. Tidak ada soal yang disimpan ke database. Konfirmasi impor akan tersedia setelah renderer konten kaya selesai dan teruji.</div>
      <h2>Pratinjau teks soal</h2>
      <?php foreach ($pratinjau['soal'] as $soal): ?>
        <section class="a-jarak-atas" aria-label="Soal <?= (int) $soal['nomor'] ?>">
          <h3>Soal <?= (int) $soal['nomor'] ?></h3>
          <p><?= esc($soal['pertanyaan']) ?></p>
          <div class="a-tabel-gulir"><table class="a-tbl"><thead><tr><th scope="col">Opsi</th><th scope="col">Isi</th><th scope="col">Kunci</th></tr></thead><tbody>
          <?php foreach ($soal['pilihan'] as $huruf => $isi): ?>
            <tr><th scope="row"><?= esc($huruf) ?></th><td><?= esc($isi) ?></td><td><?= $soal['kunci'] === $huruf ? '✓' : '' ?></td></tr>
          <?php endforeach ?>
          </tbody></table></div>
        </section>
      <?php endforeach ?>
    <?php endif ?>
    <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal'), 'attr') ?>">Kembali ke Bank Soal</a>
  </section>
</div>
<?= $this->endSection() ?>
