<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php
$kpi=$dashboard['ringkasan'];
$pilihan=$dashboard['pilihan'];
$aktif=$dashboard['terpilih'];
$nomor=(int)($dashboard['index']??0);
$semester=(int)$dashboard['semester'];
$urlPenilaian=site_url('guru/penilaian').'?'.http_build_query([
    'pilihan'=>$nomor,'semester'=>$semester,
]);
$urlPeta=site_url('guru/peta-atp').'?'.http_build_query(['pilihan'=>$nomor]);
?>
<div class="a-page a-dashboard-guru">
    <header class="a-page__heading a-proto-heading">
        <div>
            <h1>Dashboard Guru</h1>
            </div>
        <a class="a-btn a-btn--sekunder" href="<?= esc($urlPeta,'attr') ?>">Buka Peta ATP</a>
    </header>

    <?php if ($pesan=session()->getFlashdata('galat_dashboard_guru')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
    <?php endif ?>

    <section class="a-card a-jarak-atas" aria-labelledby="dash-guru-filter">
        <h2 id="dash-guru-filter">Filter Penugasan Guru</h2>
        <?php if ($pilihan===[]): ?>
            <div class="a-alert" role="status">Belum ada Gen–Mapel yang ditugaskan
                kepada akun Guru aktif. Hubungi Admin untuk pemeriksaan penugasan.</div>
        <?php else: ?>
            <form class="a-form a-dashboard-guru__filter" method="get"
                action="<?= esc(site_url('guru'),'attr') ?>">
                <div class="a-field">
                    <label class="a-label" for="guru-dash-penugasan">Gen — Mata Pelajaran</label>
                    <select class="a-input" name="pilihan" id="guru-dash-penugasan" required>
                        <?php foreach ($pilihan as $n=>$p): ?>
                            <option value="<?= (int)$n ?>" <?= $nomor===(int)$n?'selected':'' ?>>
                                <?= esc($p['nama_gen'].' — '.$p['nama_mapel']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="a-field">
                    <label class="a-label" for="guru-dash-semester">Semester</label>
                    <select class="a-input" name="semester" id="guru-dash-semester">
                        <option value="0">Semua Semester 1–6</option>
                        <?php for($s=1;$s<=6;$s++): ?>
                            <option value="<?= $s ?>" <?= $semester===$s?'selected':'' ?>>
                                Semester <?= $s ?>
                            </option>
                        <?php endfor ?>
                    </select>
                </div>
                <div class="a-dashboard-guru__filter-actions">
                    <button type="submit" class="a-btn a-btn--utama">Tampilkan Ringkasan</button>
                    <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru'),'attr') ?>">Reset</a>
                </div>
            </form>
        <?php endif ?>
    </section>

    <?php if ($aktif!==null): ?>
        <section class="a-jarak-atas" aria-labelledby="guru-dash-kpi">
            <h2 id="guru-dash-kpi">Indikator Utama</h2>
            <p class="a-help"><?= esc($aktif['nama_gen'].' — '.$aktif['nama_mapel']) ?>
                · <?= $semester===0?'Semua Semester':'Semester '.$semester ?></p>
            <div class="a-dashboard-guru__metrics">
                <article class="a-card a-dashboard-guru__metric">
                    <span>Paket Semester</span>
                    <strong><?= (int)$kpi['paket'] ?></strong>
                    
                </article>
                <article class="a-card a-dashboard-guru__metric">
                    <span>TP Siap</span>
                    <strong><?= (int)$kpi['tp_siap'] ?></strong>
                    <small>Dari <?= (int)$kpi['tp_total'] ?> TP nonarsip</small>
                </article>
                <article class="a-card a-dashboard-guru__metric">
                    <span>TP Draf</span>
                    <strong><?= (int)$kpi['tp_draf'] ?></strong>
                    
                </article>
                <article class="a-card a-dashboard-guru__metric">
                    <span>Materi perlu diperiksa</span>
                    <strong><?= (int)$kpi['tp_tanpa_materi'] ?></strong>
                    
                </article>
                <article class="a-card a-dashboard-guru__metric">
                    <span>Antrean Resume/HOTS</span>
                    <strong><?= (int)($kpi['antrean_resume']+$kpi['antrean_hots']) ?></strong>
                    <small><?= (int)$kpi['antrean_resume'] ?> Resume · <?= (int)$kpi['antrean_hots'] ?> HOTS</small>
                </article>
                <article class="a-card a-dashboard-guru__metric">
                    <span>Siswa perlu bantuan</span>
                    <strong><?= (int)$kpi['perlu_bantuan'] ?></strong>
                    
                </article>
            </div>
        </section>

        <section class="a-card a-jarak-atas" aria-labelledby="guru-dash-aksi">
            <h2 id="guru-dash-aksi">Tindakan Guru</h2>
            <div class="a-dashboard-guru__actions">
                <a class="a-btn a-btn--utama" href="<?= esc($urlPenilaian,'attr') ?>">
                    Nilai Resume / HOTS
                </a>
                <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/akses-ulang'),'attr') ?>">
                    Periksa Akses Ulang
                </a>
                <a class="a-btn a-btn--sekunder" href="<?= esc($urlPeta,'attr') ?>">
                    Kelola ATP dan TP
                </a>
                <a class="a-btn a-btn--sekunder"
                    href="<?= esc(site_url('guru/pra-semester'),'attr') ?>">
                    Pra-Semester
                </a>
            </div>
            <p class="a-help">Ujian Diagnostik/Sumatif sedang berjalan:
                <strong><?= (int)$kpi['ujian_berjalan'] ?></strong> percobaan pada paket terpilih.
                Ujian Pra-Semester dan nilainya dikelola terpisah.</p>
        </section>

        <section class="a-card a-jarak-atas" aria-labelledby="guru-dash-persemester">
            <h2 id="guru-dash-persemester">Ringkasan per Semester</h2>
            <p class="a-help">Indikator Materi dihitung dari kelengkapan jumlah Materi aktif;
                pemeriksaan lengkap kesiapan TP tetap dilakukan pada Kelola TP.
                Status angka 0 berarti tidak ada data yang memenuhi indikator terkait.</p>
            <?php if ($dashboard['baris']===[]): ?>
                <div class="a-alert" role="status">Belum ada paket Semester aktif untuk
                    Gen–Mapel dan filter yang dipilih. Periksa Peta ATP.</div>
            <?php else: ?>
                <div class="a-tabel-gulir a-dashboard-guru__scroll"
                    role="region" tabindex="0" aria-label="Tabel semester dapat digulir">
                    <table class="a-tbl a-dashboard-guru__table">
                        <thead><tr>
                            <th scope="col">Semester</th>
                            <th scope="col">BAB</th>
                            <th scope="col">Total TP</th>
                            <th scope="col">Siap</th>
                            <th scope="col">Draf</th>
                            <th scope="col">Materi perlu diperiksa</th>
                            <th scope="col">Resume / HOTS</th>
                            <th scope="col">Siswa perlu bantuan</th>
                            <th scope="col">Ujian berjalan</th>
                            <th scope="col">Aksi</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($dashboard['baris'] as $row): ?>
                            <tr>
                                <th scope="row">Semester <?= (int)$row['semester'] ?></th>
                                <td><?= (int)$row['bab'] ?></td>
                                <td><?= (int)$row['tp_total'] ?></td>
                                <td><?= (int)$row['tp_siap'] ?></td>
                                <td><?= (int)$row['tp_draf'] ?></td>
                                <td><?= (int)$row['tp_tanpa_materi'] ?></td>
                                <td><?= (int)$row['antrean_resume'] ?> /
                                    <?= (int)$row['antrean_hots'] ?></td>
                                <td><?= (int)$row['perlu_bantuan'] ?></td>
                                <td><?= (int)$row['ujian_berjalan'] ?></td>
                                <td><a class="a-btn a-btn--sekunder"
                                    href="<?= esc(site_url('guru/peta-atp/semester/'.(int)$row['semester'])
                                        .'?'.http_build_query(['pilihan'=>$nomor]),'attr') ?>">
                                        Buka Semester
                                </a></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
