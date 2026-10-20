<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-proto-dashboard">
    <div class="a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Ruang kerja / Administrasi</p>
            <h1>Dashboard Admin</h1>
            <p class="a-proto-muted">Selamat datang, <strong><?= esc($namaPengguna) ?></strong>. Berikut ringkasan data induk akademik yang tersedia saat ini.</p>
        </div>
        <a class="a-btn a-btn--utama" href="<?= esc(site_url('admin/referensi'), 'attr') ?>">Kelola Gen <span aria-hidden="true">↗</span></a>
    </div>

    <?php if ($pesan = session()->getFlashdata('sukses')): ?>
        <div class="a-alert" role="status"><?= esc($pesan) ?></div>
    <?php endif ?>

    <section aria-labelledby="judul-ringkasan" class="a-proto-section">
        <div class="a-proto-section-head">
            <div>
                <h2 id="judul-ringkasan">Ringkasan akademik</h2>
                <p>Data langsung dari referensi dan penugasan K2.</p>
            </div>
            <span class="a-proto-context">Gen × Mapel</span>
        </div>
        <div class="a-proto-metrics">
            <article class="a-proto-metric">
                <span class="a-proto-metric__symbol a-proto-metric__symbol--mint" aria-hidden="true">G</span>
                <p>Gen terdaftar</p>
                <strong><?= esc((string) $ringkasan['gen']) ?></strong>
                <a href="<?= esc(site_url('admin/referensi'), 'attr') ?>">Lihat Gen <span aria-hidden="true">→</span></a>
            </article>
            <article class="a-proto-metric">
                <span class="a-proto-metric__symbol a-proto-metric__symbol--blue" aria-hidden="true">M</span>
                <p>Mata pelajaran</p>
                <strong><?= esc((string) $ringkasan['mapel']) ?></strong>
                <a href="<?= esc(site_url('admin/mapel-guru'), 'attr') ?>">Lihat mapel <span aria-hidden="true">→</span></a>
            </article>
            <article class="a-proto-metric">
                <span class="a-proto-metric__symbol a-proto-metric__symbol--violet" aria-hidden="true">✓</span>
                <p>Pasangan Gen–Mapel terisi Guru</p>
                <strong><?= esc((string) $ringkasan['guru_ditugaskan']) ?></strong>
                <a href="<?= esc(site_url('admin/mapel-guru'), 'attr') ?>">Lihat penugasan <span aria-hidden="true">→</span></a>
            </article>
            <article class="a-proto-metric">
                <span class="a-proto-metric__symbol a-proto-metric__symbol--amber" aria-hidden="true">!</span>
                <p>Pasangan belum memiliki Guru</p>
                <strong><?= esc((string) $ringkasan['belum_ditugaskan']) ?></strong>
                <a href="<?= esc(site_url('admin/mapel-guru'), 'attr') ?>">Atur pengampu <span aria-hidden="true">→</span></a>
            </article>
        </div>
    </section>

    <div class="a-proto-dashboard-grid">
        <section class="a-card a-proto-panel" aria-labelledby="judul-akses-cepat">
            <div class="a-proto-section-head">
                <div>
                    <h2 id="judul-akses-cepat">Akses cepat</h2>
                    <p>Kelola data yang telah tersedia dalam aplikasi.</p>
                </div>
            </div>
            <div class="a-proto-shortcuts">
                <a href="<?= esc(site_url('admin/pengguna'), 'attr') ?>">
                    <span class="a-proto-shortcut-icon" aria-hidden="true">PG</span>
                    <span><strong>Pengguna</strong><small>Akun, peran dan sandi awal</small></span>
                    <span class="a-proto-shortcut-arrow" aria-hidden="true">↗</span>
                </a>
                <a href="<?= esc(site_url('admin/referensi'), 'attr') ?>">
                    <span class="a-proto-shortcut-icon" aria-hidden="true">GN</span>
                    <span><strong>Gen &amp; Tahun Ajaran</strong><small>Data induk pembelajaran</small></span>
                    <span class="a-proto-shortcut-arrow" aria-hidden="true">↗</span>
                </a>
                <a href="<?= esc(site_url('admin/mapel-guru'), 'attr') ?>">
                    <span class="a-proto-shortcut-icon" aria-hidden="true">G1</span>
                    <span><strong>Mapel &amp; Guru per Gen</strong><small>Penugasan pengampu</small></span>
                    <span class="a-proto-shortcut-arrow" aria-hidden="true">↗</span>
                </a>
                <a href="<?= esc(site_url('admin/identitas'), 'attr') ?>">
                    <span class="a-proto-shortcut-icon" aria-hidden="true">ID</span>
                    <span><strong>Identitas Madrasah</strong><small>Informasi resmi sekolah</small></span>
                    <span class="a-proto-shortcut-arrow" aria-hidden="true">↗</span>
                </a>
            </div>
        </section>

        <aside class="a-card a-proto-panel a-proto-information" aria-labelledby="judul-tahap">
            <span class="a-proto-pill">Fondasi akademik</span>
            <h2 id="judul-tahap">Kesiapan pengelolaan</h2>
            <?php if ($ringkasan['gen'] === 0 || $ringkasan['mapel'] === 0): ?>
                <p>Data Gen atau mata pelajaran masih kosong. Lengkapi referensi sebelum menetapkan pengampu.</p>
            <?php elseif ($ringkasan['belum_ditugaskan'] > 0): ?>
                <p>Ada pasangan Gen–Mapel yang belum mempunyai Guru. Penetapan dapat dilakukan satu per satu.</p>
            <?php else: ?>
                <p>Setiap pasangan Gen–Mapel yang terdaftar sudah memiliki Guru pengampu.</p>
            <?php endif ?>
            <div class="a-proto-note">
                <span class="a-proto-note__mark" aria-hidden="true">i</span>
                <p>Ringkasan ini hanya menampilkan data referensi. Modul pembelajaran, penilaian, dan laporan akan muncul setelah benar-benar diimplementasikan.</p>
            </div>
            <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/mapel-guru'), 'attr') ?>">Periksa penugasan</a>
        </aside>
    </div>
</div>
<?= $this->endSection() ?>
