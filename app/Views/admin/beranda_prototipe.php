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
                <span class="a-proto-metric__symbol a-proto-metric__symbol--mint" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="3"/><path d="M8 9h8M8 13h8M8 17h5"/></svg></span>
                <p>Gen terdaftar</p>
                <strong><?= esc((string) $ringkasan['gen']) ?></strong>
                <a href="<?= esc(site_url('admin/referensi'), 'attr') ?>">Lihat Gen <span aria-hidden="true">→</span></a>
            </article>
            <article class="a-proto-metric">
                <span class="a-proto-metric__symbol a-proto-metric__symbol--blue" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 6c-2.8-1.8-5.5-2-9-1v14c3.5-1 6.2-.8 9 1 2.8-1.8 5.5-2 9-1V5c-3.5-1-6.2-.8-9 1ZM12 6v14"/></svg></span>
                <p>Mata pelajaran</p>
                <strong><?= esc((string) $ringkasan['mapel']) ?></strong>
                <a href="<?= esc(site_url('admin/mapel-guru'), 'attr') ?>">Lihat mapel <span aria-hidden="true">→</span></a>
            </article>
            <article class="a-proto-metric">
                <span class="a-proto-metric__symbol a-proto-metric__symbol--violet" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 19v-1.5a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4V19"/><circle cx="8.5" cy="7" r="3"/><path d="m16 10 2.3 2.3L22 8.5"/></svg></span>
                <p>Pasangan Gen–Mapel terisi Guru</p>
                <strong><?= esc((string) $ringkasan['guru_ditugaskan']) ?></strong>
                <a href="<?= esc(site_url('admin/mapel-guru'), 'attr') ?>">Lihat penugasan <span aria-hidden="true">→</span></a>
            </article>
            <article class="a-proto-metric">
                <span class="a-proto-metric__symbol a-proto-metric__symbol--amber" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
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
                <a href="<?= esc(site_url('admin/pengelola'), 'attr') ?>">
                    <span class="a-proto-shortcut-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8.5" cy="8" r="2.5"/><path d="M3 19v-2.2A4.8 4.8 0 0 1 7.8 12h1.4a4.8 4.8 0 0 1 4.8 4.8V19M16 5a3 3 0 0 1 0 6M17 13a4 4 0 0 1 4 4v2"/></svg></span>
                    <span><strong>Pengelola</strong><small>Akun staf dan reset sandi</small></span>
                    <span class="a-proto-shortcut-arrow" aria-hidden="true">↗</span>
                </a>
                <a href="<?= esc(site_url('admin/referensi'), 'attr') ?>">
                    <span class="a-proto-shortcut-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="3"/><path d="M8 9h8M8 13h8M8 17h5"/></svg></span>
                    <span><strong>Gen &amp; Tahun Ajaran</strong><small>Data induk pembelajaran</small></span>
                    <span class="a-proto-shortcut-arrow" aria-hidden="true">↗</span>
                </a>
                <a href="<?= esc(site_url('admin/mapel-guru'), 'attr') ?>">
                    <span class="a-proto-shortcut-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 19v-1.5a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4V19"/><circle cx="8.5" cy="7" r="3"/><path d="m16 10 2.3 2.3L22 8.5"/></svg></span>
                    <span><strong>Mapel &amp; Guru per Gen</strong><small>Penugasan pengampu</small></span>
                    <span class="a-proto-shortcut-arrow" aria-hidden="true">↗</span>
                </a>
                <a href="<?= esc(site_url('admin/identitas'), 'attr') ?>">
                    <span class="a-proto-shortcut-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21V9l9-6 9 6v12H3ZM9 21v-8h6v8M7 10h.01M17 10h.01"/></svg></span>
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
