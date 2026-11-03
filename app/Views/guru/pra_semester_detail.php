<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <?php $tautanDaftar = site_url('guru/pra-semester')
        . ($filterGen !== null ? '?gen='.$filterGen : ''); ?>
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru'),'attr') ?>">Beranda Guru</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc($tautanDaftar,'attr') ?>">Bank Pra-Semester</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Kelola Soal</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Guru · Pengelolaan Bank Soal</p>
            <h1>Kelola Soal Pra-Semester</h1>
            <p class="a-proto-muted">
                <?= esc((string)$konteks['paket']['nama_mapel']) ?> · Gen
                <?= esc((string)$konteks['paket']['nama_gen']) ?> ·
                Semester <?= (int)$konteks['paket']['semester'] ?>.
                Kelola KKM dan soal untuk paket yang dipilih.
            </p>
        </div>
        <a class="a-btn a-btn--sekunder"
            href="<?= esc($tautanDaftar,'attr') ?>">Kembali ke daftar</a>
    </header>
    <?php if ($pesan = session()->getFlashdata('sukses')): ?>
        <div class="a-alert" role="status"><?= esc($pesan) ?></div>
    <?php endif ?>
    <?php if ($pesan = session()->getFlashdata('galat')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
    <?php endif ?>
    <?php if ($konteks !== null): ?>
        <?php $paket = $konteks['paket']; $ujian = $konteks['exam']; $kesiapan = $konteks['kesiapan'];
            $kkm = $konteks['kkm']; $terpakai = $konteks['terpakai']; ?>
        <section class="a-card a-jarak-atas">
            <h2><?= esc((string) $paket['nama_mapel']) ?> — Semester <?= (int) $paket['semester'] ?></h2>
            <p class="a-help">Gen <?= esc((string) $paket['nama_gen']) ?> ·
                <?= (int) $kesiapan['jumlah_sah'] ?> soal sah ·
                <?= (int) $kesiapan['jumlah_bab_tercakup'] ?>/<?= (int) $kesiapan['jumlah_bab'] ?> BAB terwakili</p>
            <div class="a-alert" role="status">
                <strong><?= $kesiapan['siap'] ? 'Bank soal siap' : 'Bank soal belum siap' ?></strong>.
                <?= esc((string) $kesiapan['pesan']) ?>
            </div>
            <?php if ($kesiapan['bab_tanpa_soal'] !== []): ?>
                <p class="a-help">BAB tanpa soal sah:
                    <?= esc(implode(', ', array_map('strval', $kesiapan['bab_tanpa_soal']))) ?>.</p>
            <?php endif ?>
            <?php if ($terpakai): ?>
                <div class="a-alert">Bank soal dan KKM terkunci karena sudah digunakan siswa.
                    Jawaban, kunci dan nilai historis tetap utuh.</div>
            <?php endif ?>
        </section>
        <section class="a-card a-jarak-atas" aria-labelledby="kkm-pra">
            <h2 id="kkm-pra">KKM Pra-Semester</h2>
            <p>Default Admin: <strong><?= $kkm['default'] === null ? 'Belum ditetapkan' : (int)$kkm['default'] ?></strong>.
                KKM efektif: <strong><?= $kkm['efektif'] === null ? 'Belum tersedia' : (int)$kkm['efektif'] ?></strong>.
                Sumber: <?= esc((string)$kkm['sumber']) ?>.</p>
            <?php if (!$terpakai): ?>
                <form method="post" action="<?= esc(site_url('guru/pra-semester/' . (int)$paket['id'] . '/kkm') . ($filterGen !== null ? '?gen='.$filterGen : ''),'attr') ?>" class="a-form">
                    <?= csrf_field() ?>
                    <div class="a-field">
                        <label class="a-label" for="kkm-pra-input">KKM khusus Guru (opsional)</label>
                        <input id="kkm-pra-input" name="kkm_khusus" class="a-input" type="number" min="1" max="100"
                            value="<?= esc((string)($kkm['khusus'] ?? ''),'attr') ?>">
                        <p class="a-help">Kosongkan untuk mengikuti default Admin. Tidak menggunakan KKM salah satu TP.</p>
                    </div>
                    <button class="a-btn a-btn--utama" type="submit">Simpan KKM Pra-Semester</button>
                </form>
            <?php endif ?>
        </section>
        <section class="a-card a-jarak-atas">
            <h2>Daftar soal</h2>
            <?php if ($konteks['soal'] === []): ?>
                <p class="a-help" role="status">Belum ada soal. Tambahkan soal PG A–E dan pilih BAB asal soal.</p>
            <?php else: ?>
                <div class="a-tabel-gulir a-pra-table-wrap">
                    <table class="a-tbl a-pra-table">
                        <thead><tr><th>No.</th><th>BAB</th><th>Soal</th><th>Status</th><th>Tindakan</th></tr></thead>
                        <tbody>
                        <?php foreach ($konteks['soal'] as $soal): ?>
                            <tr>
                                <td><?= (int)$soal['urutan'] ?></td>
                                <td>#
                                    <?= (int)$soal['bab_id'] ?></td>
                                <td><?= esc(mb_strimwidth((string)$soal['pertanyaan'],0,120,'…','UTF-8')) ?></td>
                                <td><?= (int)$soal['aktif'] === 1 ? 'Aktif' : 'Nonaktif' ?></td>
                                <td>
                                    <?php if (!$terpakai): ?>
                                        <a class="a-btn a-btn--sekunder"
                                            href="<?= esc(site_url('guru/pra-semester/' . (int)$paket['id']) . '?edit=' . (int)$soal['id'] . ($filterGen !== null ? '&gen='.$filterGen : ''),'attr') ?>">Sunting</a>
                                        <form method="post" style="display:inline"
                                            action="<?= esc(site_url('guru/pra-semester/' . (int)$paket['id'] . '/soal/' . (int)$soal['id'] . '/status') . ($filterGen !== null ? '?gen='.$filterGen : ''),'attr') ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="aktif" value="<?= (int)$soal['aktif'] === 1 ? '0':'1' ?>">
                                            <button class="a-btn a-btn--sekunder" type="submit">
                                                <?= (int)$soal['aktif'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="a-help">Terkunci</span>
                                    <?php endif ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
        </section>
        <?php if (!$terpakai): ?>
            <section class="a-card a-jarak-atas" aria-labelledby="soal-pra">
                <h2 id="soal-pra"><?= $soalEdit !== null ? 'Sunting soal' : 'Tambah soal' ?></h2>
                <form method="post" class="a-form"
                    action="<?= esc(site_url('guru/pra-semester/' . (int)$paket['id'] . '/soal') . ($filterGen !== null ? '?gen='.$filterGen : ''),'attr') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="soal_id" value="<?= $soalEdit !== null ? (int)$soalEdit['id'] : 0 ?>">
                    <div class="a-field">
                        <label class="a-label" for="pra-bab">BAB asal soal</label>
                        <select id="pra-bab" class="a-input" name="bab_id" required>
                            <option value="">Pilih BAB</option>
                            <?php foreach ($konteks['bab'] as $bab): ?>
                                <option value="<?= (int)$bab['id'] ?>"
                                    <?= (string) old('bab_id',$soalEdit['bab_id'] ?? '') === (string)$bab['id'] ? 'selected' : '' ?>>
                                    BAB <?= (int)$bab['nomor_bab'] ?> — <?= esc((string)$bab['nama_bab']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <?php foreach (['pertanyaan'=>'Pertanyaan','opsi_a'=>'Opsi A','opsi_b'=>'Opsi B',
                        'opsi_c'=>'Opsi C','opsi_d'=>'Opsi D','opsi_e'=>'Opsi E'] as $kolom => $label): ?>
                        <div class="a-field">
                            <label class="a-label" for="pra-<?= esc($kolom,'attr') ?>"><?= esc($label) ?></label>
                            <textarea id="pra-<?= esc($kolom,'attr') ?>" class="a-input" name="<?= esc($kolom,'attr') ?>"
                                maxlength="12000" rows="<?= $kolom === 'pertanyaan' ? '4':'2' ?>" required><?= esc((string)old($kolom,$soalEdit[$kolom] ?? '')) ?></textarea>
                        </div>
                    <?php endforeach ?>
                    <div class="a-field">
                        <label class="a-label" for="pra-kunci">Jawaban benar</label>
                        <select id="pra-kunci" class="a-input" name="kunci" required>
                            <option value="">Pilih kunci</option>
                            <?php foreach (['A','B','C','D','E'] as $huruf): ?>
                                <option value="<?= $huruf ?>"
                                    <?= old('kunci',$soalEdit['kunci'] ?? '') === $huruf ? 'selected' : '' ?>><?= $huruf ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <button class="a-btn a-btn--utama" type="submit">Simpan Soal</button>
                    <?php if ($soalEdit !== null): ?>
                        <a class="a-btn a-btn--sekunder"
                            href="<?= esc(site_url('guru/pra-semester/' . (int)$paket['id']) . ($filterGen !== null ? '?gen='.$filterGen : ''),'attr') ?>">Batal sunting</a>
                    <?php endif ?>
                </form>
            </section>
        <?php endif ?>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
