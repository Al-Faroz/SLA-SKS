<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php
    $peran = $panduan['peran'];
    $kelompok = $panduan['kelompok'];
?>
<div class="a-page a-bantuan">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url($peran),'attr') ?>">Beranda <?= esc($panduan['nama_peran']) ?></a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Bantuan</span>
    </nav>

    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow"><?= esc($panduan['nama_peran']) ?> · Panduan Penggunaan</p>
            <h1>Pusat Bantuan</h1>
            <p class="a-proto-muted">Petunjuk penggunaan SLA-SKS sesuai peran aktif dan alur akademik yang berlaku.
                Pertanyaan di bawah ini mengarah ke halaman yang sudah tersedia.</p>
        </div>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url($peran),'attr') ?>">Kembali ke Beranda</a>
    </header>

    <section class="a-card a-bantuan__pengenalan a-jarak-atas" aria-label="Cara menggunakan panduan">
        <div>
            <h2>Panduan untuk <?= esc($panduan['nama_peran']) ?></h2>
            <p class="a-help">Pilih pertanyaan untuk melihat jawabannya, kemudian buka menu yang ditautkan.
                Hak akses tetap diperiksa oleh server. Halaman ini berisi panduan penggunaan,
                bukan pengumuman atau notifikasi dari sekolah.</p>
        </div>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('profil'),'attr') ?>">Profil &amp; Peran</a>
    </section>

    <div class="a-bantuan__kelompok a-jarak-atas">
        <?php foreach ($kelompok as $ke => $bagian): ?>
            <section class="a-card a-bantuan__panel"
                aria-labelledby="bantuan-kelompok-<?= (int) $ke ?>">
                <h2 id="bantuan-kelompok-<?= (int) $ke ?>"><?= esc($bagian['judul']) ?></h2>
                <p class="a-help"><?= esc($bagian['keterangan']) ?></p>
                <div class="a-bantuan__pertanyaan">
                    <?php foreach ($bagian['pertanyaan'] as $i => $item): ?>
                        <details class="a-bantuan__butir" <?= $ke === 0 && $i === 0 ? 'open' : '' ?>>
                            <summary><?= esc($item['tanya']) ?></summary>
                            <div class="a-bantuan__jawaban">
                                <p><?= esc($item['jawab']) ?></p>
                                <a class="a-btn a-btn--sekunder"
                                   href="<?= esc(site_url($item['path']), 'attr') ?>">
                                    <?= esc($item['aksi']) ?>
                                </a>
                            </div>
                        </details>
                    <?php endforeach ?>
                </div>
            </section>
        <?php endforeach ?>

    </div>

    <section class="a-card a-bantuan__faq-resmi a-jarak-atas" aria-labelledby="judul-faq-resmi">
        <header class="a-informasi__kepala">
            <div>
                <p class="a-proto-eyebrow">FAQ · Informasi Madrasah</p>
                <h2 id="judul-faq-resmi">Pertanyaan Resmi</h2>
                <p class="a-help">Jawaban di bawah ini dikelola dan diterbitkan oleh Admin madrasah.
                    Pertanyaan yang masih draf atau sudah diarsipkan tidak ditampilkan.</p>
            </div>
            <?php if ($peran === 'admin'): ?>
                <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/faq'),'attr') ?>">Kelola FAQ</a>
            <?php endif ?>
        </header>
        <?php if (!$faqResmi['data']): ?>
            <p class="a-informasi__kosong" role="status">Belum ada FAQ resmi yang diterbitkan. Panduan penggunaan di atas tetap dapat digunakan.</p>
        <?php else: ?>
            <div class="a-bantuan__pertanyaan">
                <?php foreach ($faqResmi['data'] as $item): ?>
                    <details class="a-bantuan__butir">
                        <summary><?= esc($item['pertanyaan']) ?></summary>
                        <div class="a-bantuan__jawaban">
                            <p class="a-informasi__isi"><?= esc($item['jawaban']) ?></p>
                        </div>
                    </details>
                <?php endforeach ?>
            </div>
        <?php endif ?>
        <?php if ($faqResmi['total_halaman'] > 1): ?>
            <nav class="a-informasi__pagination" aria-label="Halaman FAQ resmi">
                <span>Halaman <?= (int)$faqResmi['halaman'] ?> dari <?= (int)$faqResmi['total_halaman'] ?></span>
                <?php if ($faqResmi['halaman'] > 1): ?>
                    <a class="a-btn a-btn--sekunder"
                       href="<?= esc(site_url('bantuan').'?halaman_faq='.($faqResmi['halaman']-1),'attr') ?>">Sebelumnya</a>
                <?php endif ?>
                <?php if ($faqResmi['halaman'] < $faqResmi['total_halaman']): ?>
                    <a class="a-btn a-btn--sekunder"
                       href="<?= esc(site_url('bantuan').'?halaman_faq='.($faqResmi['halaman']+1),'attr') ?>">Selanjutnya</a>
                <?php endif ?>
            </nav>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
