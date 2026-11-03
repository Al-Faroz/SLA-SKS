<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-proto-dashboard a-page">
    <header class="a-proto-heading a-page__heading">
        <div>
            <p class="a-proto-eyebrow">Ruang kerja / Administrasi</p>
            <h1>Dashboard Admin</h1>
            <p class="a-proto-muted">Selamat datang, <strong><?= esc($namaPengguna) ?></strong>. Berikut ringkasan referensi, kegiatan akademik, dan pekerjaan administrasi yang terpantau.</p>
        </div>
        <a class="a-btn a-btn--utama" href="<?= esc(site_url('admin/referensi'), 'attr') ?>">Kelola Gen <span aria-hidden="true">↗</span></a>
    </header>

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

    <section class="a-card a-dashboard-admin__section a-jarak-atas"
        aria-labelledby="admin-ringkasan-operasional">
        <div class="a-proto-section-head">
            <div>
                <h2 id="admin-ringkasan-operasional">Ringkasan Operasional</h2>
                <p>Jumlah dari data tersimpan, bukan angka contoh.
                    TP Siap/Draf adalah status konten Guru, bukan nilai siswa.</p>
            </div>
        </div>
        <div class="a-dashboard-admin__metrics">
            <article class="a-dashboard-admin__metric">
                <span>Akun aktif</span>
                <strong><?= (int)$ringkasan['akun_aktif'] ?></strong>
                <small>Semua peran dengan status akun aktif</small>
            </article>
            <article class="a-dashboard-admin__metric">
                <span>Siswa terdaftar</span>
                <strong><?= (int)$ringkasan['siswa'] ?></strong>
                <a href="<?= esc(site_url('admin/siswa'),'attr') ?>">Kelola siswa</a>
            </article>
            <article class="a-dashboard-admin__metric">
                <span>TP Siap</span>
                <strong><?= (int)$ringkasan['tp_siap'] ?></strong>
                <small>Konten siap tersimpan, dikelola Guru G1</small>
            </article>
            <article class="a-dashboard-admin__metric">
                <span>TP Draf</span>
                <strong><?= (int)$ringkasan['tp_draf'] ?></strong>
                <small>Masih disiapkan Guru, tidak otomatis gagal</small>
            </article>
            <article class="a-dashboard-admin__metric">
                <span>Foto menunggu persetujuan</span>
                <strong><?= (int)$ringkasan['foto_menunggu'] ?></strong>
                <a href="<?= esc(site_url('admin/foto-profil'),'attr') ?>">Tinjau antrean</a>
            </article>
            <article class="a-dashboard-admin__metric">
                <span>Sertifikat berlaku</span>
                <strong><?= (int)$ringkasan['sertifikat_berlaku'] ?></strong>
                <a href="<?= esc(site_url('admin/sertifikat'),'attr') ?>">Periksa sertifikat</a>
            </article>
        </div>
    </section>

    <div class="a-dashboard-admin__health a-jarak-atas">
        <section class="a-card a-dashboard-admin__section" aria-labelledby="admin-perhatian">
            <h2 id="admin-perhatian">Perhatian Admin</h2>
            <p class="a-help">Hanya kondisi yang dapat diperiksa dari data.
                Penugasan Guru dan persetujuan foto tetap diproses melalui halaman resminya.</p>
            <?php if ($ringkasan['perhatian'] === []): ?>
                <div class="a-alert" role="status">
                    Tidak ada penugasan kosong, foto menunggu, atau kendala TTD
                    yang terdeteksi oleh pemeriksaan ini. Ini bukan audit
                    keamanan dan kesehatan server menyeluruh.
                </div>
            <?php else: ?>
                <ul class="a-dashboard-admin__attention">
                    <?php foreach ($ringkasan['perhatian'] as $item): ?>
                        <li>
                            <span><?= esc($item['pesan']) ?></span>
                            <a class="a-btn a-btn--sekunder"
                                href="<?= esc(site_url($item['path']),'attr') ?>">
                                <?= esc($item['label']) ?>
                            </a>
                        </li>
                    <?php endforeach ?>
                </ul>
            <?php endif ?>
        </section>

        <section class="a-card a-dashboard-admin__section" aria-labelledby="admin-kesiapan">
            <h2 id="admin-kesiapan">Kesiapan Aset dan Operasional</h2>
            <div class="a-dashboard-admin__facts">
                <div>
                    <span>Tanda tangan sertifikat</span>
                    <?php if ($ringkasan['ttd_status'] === 'siap'): ?>
                        <strong>Aktif dan terverifikasi</strong>
                    <?php elseif ($ringkasan['ttd_status'] === 'berkas_tidak_valid'): ?>
                        <strong>Berkas perlu diperiksa</strong>
                    <?php else: ?>
                        <strong>Belum aktif</strong>
                    <?php endif ?>
                    <a href="<?= esc(site_url('admin/sertifikat/aset'),'attr') ?>">Kelola aset</a>
                </div>
                <div>
                    <span>Ruang disk tersisa pada volume writable/</span>
                    <strong>
                        <?php if ($ringkasan['disk_sisa_byte'] === null): ?>
                            Tidak dapat diukur
                        <?php else: ?>
                            <?= esc(number_format((float)$ringkasan['disk_sisa_byte'] / 1073741824,2,',','.')) ?> GB
                        <?php endif ?>
                    </strong>
                    <small>Angka kapasitas, bukan pemeriksaan ambang aman hosting</small>
                </div>
                <div>
                    <span>Status cron</span>
                    <strong>Belum diperiksa otomatis</strong>
                    <small>Jadwal tugas server tidak dinilai dari kunjungan halaman</small>
                </div>
                <div>
                    <span>Status backup</span>
                    <strong>Belum diverifikasi</strong>
                    <small>Pastikan backup database dan writable/ secara terpisah</small>
                </div>
            </div>
        </section>
    </div>

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
                <a href="<?= esc(site_url('admin/kurikulum-cp'), 'attr') ?>">
                    <span class="a-proto-shortcut-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v14H4zM8 9h8M8 13h6M8 17h4"/></svg></span>
                    <span><strong>Kurikulum &amp; CP</strong><small>Tambah, cari, dan sunting CP</small></span>
                    <span class="a-proto-shortcut-arrow" aria-hidden="true">↗</span>
                </a>
                <a href="<?= esc(site_url('admin/siswa'), 'attr') ?>">
                    <span class="a-proto-shortcut-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2M17 8h4M19 6v4"/></svg></span>
                    <span><strong>Data Siswa</strong><small>Akun dan penempatan Gen</small></span>
                    <span class="a-proto-shortcut-arrow" aria-hidden="true">↗</span>
                </a>
                <a href="<?= esc(site_url('admin/kkm'), 'attr') ?>">
                    <span class="a-proto-shortcut-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4zM8 12l3 3 5-6"/></svg></span>
                    <span><strong>KKM Default</strong><small>Ambang ketuntasan akademik</small></span>
                    <span class="a-proto-shortcut-arrow" aria-hidden="true">↗</span>
                </a>
                <a href="<?= esc(site_url('admin/identitas'), 'attr') ?>">
                    <span class="a-proto-shortcut-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21V9l9-6 9 6v12H3ZM9 21v-8h6v8M7 10h.01M17 10h.01"/></svg></span>
                    <span><strong>Identitas Madrasah</strong><small>Informasi resmi sekolah</small></span>
                    <span class="a-proto-shortcut-arrow" aria-hidden="true">↗</span>
                </a>
                <a href="<?= esc(site_url('admin/foto-profil'), 'attr') ?>">
                    <span class="a-proto-shortcut-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m5 18 5-5 4 3 2-2 3 4"/></svg></span>
                    <span><strong>Persetujuan Foto</strong><small>Tinjau foto profil pengelola</small></span>
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
                <p>Ringkasan berisi data referensi dan penugasan. Kelengkapan TP dan hasil pembelajaran diperiksa pada halaman modul masing-masing.</p>
            </div>
            <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/mapel-guru'), 'attr') ?>">Periksa penugasan</a>
        </aside>
    </div>
</div>
<?= $this->endSection() ?>
