<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-penilaian-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru'), 'attr') ?>">Beranda Guru</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Antrean Penilaian</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Guru · Penilaian per Gen–Mapel</p>
            <h1>Penilaian Resume &amp; HOTS</h1>
            <p class="a-proto-muted">Pilih Belum Dinilai untuk memberi skor atau Sudah Dinilai untuk memperbaiki
                skor secara langsung melalui tombol Koreksi Nilai.</p>
        </div>
    </header>
    <?php if (session()->getFlashdata('nilai_galat')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc(session()->getFlashdata('nilai_galat')) ?></div>
    <?php endif ?>
    <?php if (session()->getFlashdata('nilai_sukses')): ?>
        <div class="a-alert" role="status"><?= esc(session()->getFlashdata('nilai_sukses')) ?></div>
    <?php endif ?>
    <section class="a-card" aria-labelledby="filter-penilaian">
        <h2 id="filter-penilaian">Filter penugasan Guru</h2>
        <?php if ($penugasan === []): ?>
            <div class="a-alert" role="status">Belum ada penugasan Gen–Mapel aktif.
                Hubungi Admin untuk menetapkan Guru pengampu.</div>
        <?php else: ?>
            <form method="get" action="<?= esc(site_url('guru/penilaian'), 'attr') ?>"
                class="a-form a-penilaian-filter" id="filter-antrean">
                <div class="a-field">
                    <label class="a-label" for="pilihan">Gen – Mata Pelajaran</label>
                    <select class="a-input" id="pilihan" name="pilihan">
                        <?php foreach ($penugasan as $i => $p): ?>
                            <option value="<?= (int) $i ?>" <?= (int) $pilihan_aktif === $i ? 'selected' : '' ?>>
                                <?= esc($p['nama_gen'] . ' (' . $p['kode_gen'] . ') — ' . $p['nama_mapel']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="a-field">
                    <label class="a-label" for="semester">Semester</label>
                    <select class="a-input" id="semester" name="semester">
                        <option value="0" <?= $semester === 0 ? 'selected' : '' ?>>Semua semester</option>
                        <?php for ($s=1;$s<=6;$s++): ?>
                            <option value="<?= $s ?>" <?= $semester === $s ? 'selected' : '' ?>>Semester <?= $s ?></option>
                        <?php endfor ?>
                    </select>
                </div>
                <div class="a-field">
                    <label class="a-label" for="bab">BAB</label>
                    <select class="a-input" id="bab" name="bab">
                        <option value="0" <?= $bab === 0 ? 'selected' : '' ?>>Semua BAB</option>
                        <?php foreach ($daftarBab as $item): ?>
                            <option value="<?= (int)$item['id'] ?>" <?= $bab === (int)$item['id'] ? 'selected' : '' ?>>
                                <?= esc('Semester '.$item['semester'].' · BAB '.$item['nomor_bab'].' — '.$item['nama_bab']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="a-field">
                    <label class="a-label" for="jenis">Jenis Tugas</label>
                    <select class="a-input" id="jenis" name="jenis">
                        <option value="semua" <?= $jenis === 'semua' ? 'selected' : '' ?>>Resume dan HOTS</option>
                        <option value="resume" <?= $jenis === 'resume' ? 'selected' : '' ?>>Resume</option>
                        <option value="hots" <?= $jenis === 'hots' ? 'selected' : '' ?>>HOTS</option>
                    </select>
                </div>
                <div class="a-field">
                    <label class="a-label" for="status-penilaian">Status Penilaian</label>
                    <select class="a-input" id="status-penilaian" name="status_penilaian">
                        <option value="belum" <?= $status_penilaian==='belum'?'selected':'' ?>>Belum Dinilai</option>
                        <option value="sudah" <?= $status_penilaian==='sudah'?'selected':'' ?>>Sudah Dinilai / Koreksi Nilai</option>
                    </select>
                </div>
                <div class="a-penilaian-filter__aksi">
                    <button class="a-btn a-btn--utama" type="submit">Terapkan Filter</button>
                    <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/penilaian'), 'attr') ?>">Reset</a>
                </div>
            </form>
            <script>
            (() => {
                const form=document.getElementById('filter-antrean');
                if (!form) return;
                for (const id of ['pilihan','semester']) {
                    form.querySelector('#'+id)?.addEventListener('change', () => {
                        form.querySelector('#bab').value='0';
                        form.submit();
                    });
                }
            })();
            </script>
        <?php endif ?>
    </section>
    <section class="a-card a-jarak-atas" aria-labelledby="antrean-pengumpulan">
        <h2 id="antrean-pengumpulan"><?= $status_penilaian==='sudah'?'Nilai yang dapat dikoreksi':'Jawaban final belum dinilai' ?></h2>
        <p class="a-help"><?= (int) $antrean['total'] ?> <?= $status_penilaian==='sudah'?'tugas sudah dinilai':'tugas menunggu penilaian' ?>.
            Halaman <?= (int) $antrean['halaman'] ?> dari <?= (int) $antrean['jumlah_halaman'] ?>.</p>
        <?php if ($antrean['baris'] === []): ?>
            <div class="a-alert" role="status"><?= $status_penilaian==='sudah'?'Belum ada nilai Resume/HOTS yang dapat dikoreksi.':'Belum ada Resume/HOTS yang perlu dinilai.' ?></div>
        <?php else: ?>
            <div class="a-tabel-gulir">
                <table class="a-tbl a-penilaian-tabel">
                    <thead><tr>
                        <th scope="col">Dikirim (UTC)</th>
                        <th scope="col">Siswa</th>
                        <th scope="col">TP / Materi</th>
                        <th scope="col">Jenis</th>
                        <th scope="col">Aksi</th>
                    </tr></thead>
                    <tbody>
                        <?php foreach ($antrean['baris'] as $r): ?>
                            <tr>
                                <td><?= esc((string) $r['dikirim_pada']) ?></td>
                                <td><?= esc((string) $r['nama_siswa']) ?></td>
                                <td>
                                    <strong><?= esc($r['kode_tp'] . ' — ' . $r['tp_judul']) ?></strong>
                                    <span class="a-penilaian-terpisah">Materi <?= (int) $r['urutan_materi'] ?>:
                                        <?= esc($r['materi_judul']) ?></span>
                                </td>
                                <td><span class="a-badge"><?= esc(strtoupper($r['jenis'])) ?></span>
                                </td>
                                <td>
                                    <?php if ($status_penilaian==='sudah'): ?>
                                        <strong class="a-help">Skor <?= (int)$r['nilai'] ?>/100</strong>
                                        <a class="a-btn a-btn--sekunder"
                                            href="<?= esc(site_url('guru/koreksi-nilai/'.$r['jenis'].'/'.(int)$r['id']),'attr') ?>">Koreksi Nilai</a>
                                    <?php else: ?>
                                        <a class="a-btn a-btn--sekunder"
                                            href="<?= esc(site_url('guru/penilaian/'.$r['jenis'].'/'.(int)$r['id']),'attr') ?>">Nilai jawaban</a>
                                    <?php endif ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
        <?php if ($antrean['jumlah_halaman'] > 1): ?>
            <nav class="a-penilaian-pager" aria-label="Halaman antrean penilaian">
                <?php $query = '?' . http_build_query([
                    'pilihan'=>$pilihan_aktif,'semester'=>$semester,
                    'bab'=>$bab,'jenis'=>$jenis,
                    'status_penilaian'=>$status_penilaian,
                ]) . '&halaman='; ?>
                <?php if ($antrean['halaman'] > 1): ?>
                    <a class="a-btn a-btn--sekunder"
                        href="<?= esc(site_url('guru/penilaian') . $query . ($antrean['halaman'] - 1), 'attr') ?>">Sebelumnya</a>
                <?php else: ?>
                    <span aria-disabled="true">Halaman pertama</span>
                <?php endif ?>
                <strong>Halaman <?= (int) $antrean['halaman'] ?></strong>
                <?php if ($antrean['halaman'] < $antrean['jumlah_halaman']): ?>
                    <a class="a-btn a-btn--sekunder"
                        href="<?= esc(site_url('guru/penilaian') . $query . ($antrean['halaman'] + 1), 'attr') ?>">Berikutnya</a>
                <?php else: ?>
                    <span aria-disabled="true">Halaman terakhir</span>
                <?php endif ?>
            </nav>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
