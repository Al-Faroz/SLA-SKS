<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('siswa'), 'attr') ?>">Beranda Siswa</a>
        <span aria-hidden="true">/</span><a href="<?= esc(site_url('siswa/pembelajaran'), 'attr') ?>">Mata Pelajaran</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('siswa/mapel/' . (int) $paket['mapel_id']), 'attr') ?>">Semester</a>
        <span aria-hidden="true">/</span><span aria-current="page">Daftar TP</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Siswa · <?= esc((string) $paket['kode_mapel']) ?> · Gen <?= esc((string) $siswa['gen_kode']) ?></p>
            <h1>Semester <?= (int) $paket['semester'] ?> — <?= esc((string) $paket['nama_mapel']) ?></h1>
            <p class="a-proto-muted">TP dibuka berurutan sesuai ATP.</p>
        </div>
    </header>
    <section class="a-card">
        <h2>Tujuan Pembelajaran</h2>
        <?php if ($tp === []): ?>
            <div class="a-empty" role="status">
                <strong>Belum ada TP pada semester ini.</strong>
                <p>Guru sedang menyiapkan struktur pembelajaran.</p>
            </div>
        <?php else: ?>
            <div class="a-siswa-daftar-tp">
                <?php foreach ($bab as $kelompok): ?>
                    <section class="a-siswa-bab" aria-labelledby="bab-siswa-<?= (int) $kelompok['id'] ?>">
                        <header class="a-siswa-bab__judul">
                            <div>
                                <p class="a-proto-eyebrow">BAB <?= (int) $kelompok['nomor'] ?></p>
                                <h3 id="bab-siswa-<?= (int) $kelompok['id'] ?>"><?= esc($kelompok['nama']) ?></h3>
                                <?php if ($kelompok['deskripsi'] !== ''): ?>
                                    <p class="a-help"><?= esc($kelompok['deskripsi']) ?></p>
                                <?php endif ?>
                            </div>
                            <span class="a-badge"><?= count($kelompok['tp']) ?> TP</span>
                        </header>
                        <div class="a-siswa-bab__tp">
                            <?php foreach ($kelompok['tp'] as $item): ?>
                                <article class="a-siswa-kartu">
                                    <div class="a-siswa-tp-heading">
                                        <div>
                                            <p class="a-proto-eyebrow">Urutan <?= (int) $item['urutan_atp'] ?> · <?= esc((string) $item['kode_tp']) ?></p>
                                            <h4><?= esc((string) $item['judul']) ?></h4>
                                        </div>
                                        <span class="a-siswa-status <?= esc($item['akses']['kode'], 'attr') ?>"><?= esc($item['akses']['label']) ?></span>
                                    </div>
                                    <?php if ($item['akses']['buka']): ?>
                                        <a class="a-btn a-btn--utama" href="<?= esc(site_url('siswa/tp/' . (int) $item['id']), 'attr') ?>">Lihat rincian TP</a>
                                    <?php elseif ($item['akses']['kode'] === 'esensial'): ?>
                                        <p class="a-help">TP Esensial tercatat dalam ATP tanpa aktivitas atau nilai wajib siswa.</p>
                                    <?php else: ?>
                                        <p class="a-help"><?= esc($item['akses']['label']) ?></p>
                                    <?php endif ?>
                                </article>
                            <?php endforeach ?>
                        </div>
                    </section>
                <?php endforeach ?>
            </div>
        <?php endif ?>
        <p class="a-help a-jarak-atas">Buka Rincian TP untuk melanjutkan belajar.</p>
    </section>
    <section class="a-card a-jarak-atas" aria-labelledby="pra-siswa">
        <?php
            $pra = $praSemester;
            $p = $pra['gerbang']['tp'];
            $bank = $pra['gerbang']['bank'];
            $fase = (string)$pra['fase'];
            $faseLabel = match ($fase) {
                'sedang_ujian' => 'Percobaan sedang berlangsung',
                'menunggu_remedial' => 'Menunggu jadwal remedial Admin',
                'butuh_susulan_normal' => 'Menunggu jadwal susulan normal Admin',
                'butuh_susulan_remedial' => 'Menunggu jadwal susulan remedial Admin',
                'selesai' => 'Ujian selesai',
                default => ($pra['boleh_mulai'] ? 'Jadwal tersedia — siap dimulai'
                    : 'Belum dapat memulai ujian'),
            };
        ?>
        <div class="a-siswa-tp-heading">
            <div>
                <p class="a-proto-eyebrow">Evaluasi gabungan BAB semester</p>
                <h2 id="pra-siswa">Ujian Pra-Semester</h2>
            </div>
            <span class="a-siswa-status"><?= esc($faseLabel) ?></span>
        </div>
        <p class="a-help">Syarat: seluruh TP NonEsensial selesai sah, bank soal siap,
            dan jadwal dari Admin. Durasi dihitung sejak masing-masing siswa mulai,
            bukan sejak awal jendela jadwal.</p>
        <p class="a-help">TP wajib selesai:
            <strong><?= (int)$p['jumlah_tuntas'] ?> dari <?= (int)$p['jumlah_wajib'] ?></strong>.
            Bank soal: <strong><?= (int)$bank['jumlah_sah'] ?> sah</strong>
            dan <strong><?= (int)$bank['jumlah_bab_tercakup'] ?>
            dari <?= (int)$bank['jumlah_bab'] ?> BAB</strong> terwakili.
        </p>
        <?php if ($pra['ujian'] !== null): ?>
            <p class="a-help">Jadwal utama (WIB):
                <strong><?= esc((string)($pra['ujian']['mulai_pada'] ?? 'Belum ditetapkan')) ?></strong>
                sampai <strong><?= esc((string)($pra['ujian']['selesai_pada'] ?? 'Belum ditetapkan')) ?></strong>.
                Durasi individual:
                <strong><?= (int)$pra['ujian']['durasi_menit'] ?> menit</strong>.
                KKM: <strong><?= $pra['kkm'] === null ? 'Belum tersedia' : (int)$pra['kkm'] ?></strong>.
            </p>
        <?php else: ?>
            <p class="a-help" role="status">Bank atau jadwal utama belum dibuat Guru/Admin.</p>
        <?php endif ?>
        <?php if ($pra['nilai_terbaik'] !== null): ?>
            <p class="a-help">Nilai Pra-Semester:
                <strong><?= number_format((float)$pra['nilai_terbaik'],2,',','.') ?></strong>
                (<?= $pra['status_nilai'] === 'final' ? 'final' : 'sementara, menunggu remedial' ?>).
            </p>
        <?php endif ?>
        <?php if ($pra['jadwal_aktif'] !== null): ?>
            <p class="a-help" role="status">
                Sesi yang bisa digunakan:
                <strong><?= esc(ucfirst((string)$pra['jadwal_aktif']['jenis'])) ?></strong>,
                tahap <?= esc((string)$pra['jadwal_aktif']['tahap']) ?>.
                Durasi sesi: <strong><?= (int)$pra['jadwal_aktif']['durasi_menit'] ?> menit</strong>.
            </p>
        <?php elseif (in_array($fase, [
            'menunggu_remedial','butuh_susulan_normal','butuh_susulan_remedial'
        ], true)): ?>
            <p class="a-help" role="status">
                Kesempatan berikutnya hanya tersedia setelah Admin membuka
                jadwal yang sesuai. Tidak ada akses ujian ulang mandiri.
            </p>
        <?php elseif (!$p['siap']): ?>
            <p class="a-help" role="status"><?= esc((string)$p['pesan']) ?></p>
        <?php endif ?>
        <a class="a-btn a-btn--utama"
            href="<?= esc(site_url('siswa/pra-semester/' . (int)$paket['id']),'attr') ?>">
            <?= $fase === 'sedang_ujian' ? 'Lanjutkan Ujian Pra-Semester'
                : 'Lihat Jadwal &amp; Status Pra-Semester' ?>
        </a>
    </section>
</div>
<?= $this->endSection() ?>
