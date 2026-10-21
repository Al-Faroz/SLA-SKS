<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php $isDraft=$bab['status']==='draf'; ?>
<div class="a-page a-belajar-guru">
    <nav class="a-breadcrumb"><a href="<?= esc(site_url('guru/paket/'.(int)$bab['paket_id']),'attr') ?>">Paket</a> / BAB <?= (int)$bab['urutan'] ?></nav>
    <header class="a-page__heading a-proto-heading"><div>
        <p class="a-proto-eyebrow">Guru · Struktur TP dan Konten BAB</p><h1><?= esc($bab['judul']) ?></h1>
        <p class="a-proto-muted">Gen ID <?= (int)$bab['angkatan_id'] ?> · Mapel ID <?= (int)$bab['mapel_id'] ?> · Semester <?= (int)$bab['semester'] ?> · Struktur v<?= (int)$bab['struktur_versi'] ?></p>
    </div><span class="a-badge"><?= $isDraft?'Draf · dapat diedit':'Siap/Terkunci · hanya baca' ?></span></header>
    <?php if($msg=session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($msg) ?></div><?php endif ?>
    <?php if($msg=session()->getFlashdata('sukses')): ?><div class="a-alert" role="status"><?= esc($msg) ?></div><?php endif ?>
    <nav class="a-page__jumps" aria-label="Tahapan penyusunan BAB">
        <a href="#tp">1. Struktur TP</a><a href="#konten">2. Materi dan Soal</a>
        <a href="#excel">3. Excel</a><a href="#kesiapan">4. Checklist Siap</a>
    </nav>
    <section class="a-card" id="tp"><h2>1 · Struktur TP dan CP resmi</h2>
        <p class="a-help">Kode TP dan CP utama hanya dapat disusun di layar BAB. Excel tidak boleh mengubah struktur ini.</p>
        <?php if(!$isDraft): ?><div class="a-alert" role="status">BAB Siap terkunci dari penyuntingan struktur.</div>
        <?php elseif($cp===[]): ?><div class="a-alert a-alert--bahaya">Tidak ada CP resmi aktif untuk kurikulum/mapel paket ini. Minta Admin menerbitkan CP sebelum membuat TP.</div>
        <?php else: ?>
            <details class="a-cp__details">
                <summary>Tambah Tujuan Pembelajaran (TP) berikutnya</summary>
                <form class="a-form a-belajar-guru__inner" method="post" action="<?= esc(site_url('guru/bab/'.(int)$bab['id'].'/tp'),'attr') ?>">
                    <?= csrf_field() ?>
                    <div class="a-guru-form-grid">
                        <div class="a-field"><label class="a-label" for="kode-tp">Kode TP</label>
                            <input id="kode-tp" name="kode_tp" maxlength="40" minlength="2" pattern="[A-Za-z0-9._-]{2,40}" required></div>
                        <div class="a-field"><label class="a-label" for="cp-tp">CP utama / CP tambahan</label>
                            <select id="cp-tp" name="cp_versi_id[]" required multiple size="5">
                                <?php foreach($cp as $c): ?><option value="<?= (int)$c['id'] ?>"><?= esc($c['kode'].' · v'.$c['nomor_versi'].' · '.$c['elemen']) ?></option><?php endforeach ?>
                            </select><p class="a-help">Pilih CP pertama sebagai CP utama. Ctrl/Cmd untuk memilih tambahan.</p></div>
                        <div class="a-field a-guru-form-grid__wide"><label class="a-label" for="rumusan-tp">Rumusan TP</label>
                            <textarea id="rumusan-tp" name="rumusan" minlength="20" rows="3" required></textarea></div>
                        <div class="a-field a-guru-form-grid__wide"><label class="a-label" for="indikator-tp">Indikator ketercapaian</label>
                            <textarea id="indikator-tp" name="indikator" minlength="15" rows="3" required></textarea></div>
                    </div>
                    <p class="a-help">Urutan ATP dibuat otomatis; parameter bawaan mengikuti Master Document dan dapat diubah pada setiap TP setelah dibuat.</p>
                    <button class="a-btn a-btn--utama" type="submit">Simpan TP</button>
                </form>
            </details>
        <?php endif ?>
        <?php if($tp===[]): ?><div class="a-empty" role="status"><strong>Belum ada TP.</strong><p>Tambahkan TP dan tautkan dengan CP resmi sebelum mengisi Excel.</p></div>
        <?php else: ?>
            <div class="a-belajar-guru__list">
            <?php foreach($tp as $t): ?>
                <article class="a-belajar-guru__item"><div>
                    <strong><?= (int)$t['urutan_atp'] ?>. <?= esc($t['kode_tp']) ?></strong>
                    <p><?= esc($t['rumusan']) ?></p>
                    <p class="a-help">CP utama <?= $t['kode_cp']===null?'Belum ditentukan':esc($t['kode_cp']) ?> · KKM <?= (int)$t['kkm'] ?></p>
                </div></article>
            <?php endforeach ?>
            </div>
        <?php endif ?>
    </section>
    <section class="a-card" id="konten">
        <h2>2 · Materi, HOTS, bank soal dan parameter</h2>
        <?php if($tp===[]): ?><div class="a-empty">Belum ada TP. Isi struktur TP terlebih dahulu.</div>
        <?php else: ?>
        <?php foreach($tp as $t): ?>
            <?php $mList=array_values(array_filter($materi,static fn($m)=>(int)$m['tp_id']===(int)$t['id']));
                  $hList=array_values(array_filter($hots,static fn($h)=>(int)$h['tp_id']===(int)$t['id']));
                  $dCount=count(array_filter($soal,static fn($q)=>(int)$q['tp_id']===(int)$t['id']&&$q['jenis']==='diagnostik'));
                  $sCount=count(array_filter($soal,static fn($q)=>(int)$q['tp_id']===(int)$t['id']&&$q['jenis']==='sumatif'));
            ?>
            <details class="a-cp__details a-belajar-guru__tp-card">
                <summary><?= esc($t['kode_tp']) ?> · <?= count($mList) ?> materi, <?= $dCount ?> soal diagnostik, <?= $sCount ?> soal sumatif</summary>
                <div class="a-belajar-guru__inner">
                    <div class="a-guru-form-grid">
                        <section><h3>Materi</h3>
                            <?php if($mList===[]): ?><p class="a-help">Belum ada materi.</p><?php endif ?>
                            <?php foreach($mList as $m): ?>
                                <div class="a-belajar-guru__subitem">
                                    <strong><?= (int)$m['urutan'] ?>. <?= esc($m['judul']) ?></strong>
                                    <p class="a-help"><?= esc(strtoupper($m['jenis'])) ?> · <?= (int)$m['min_waktu_detik'] ?> detik
                                    <?php if($m['berkas_id']!==null): ?>
                                        · <a href="<?= esc(site_url('guru/berkas/'.(int)$m['berkas_id']),'attr') ?>">Buka berkas privat</a>
                                    <?php elseif($m['jenis']==='youtube'): ?>
                                        · <span>Tautan YouTube tersimpan</span>
                                    <?php else: ?> · <span>Belum ada berkas</span><?php endif ?></p>
                                    <?php if($isDraft && $m['jenis']!=='youtube'): ?>
                                        <form method="post" enctype="multipart/form-data" action="<?= esc(site_url('guru/materi/'.(int)$m['id'].'/berkas'),'attr') ?>" class="a-form">
                                            <?= csrf_field() ?>
                                            <label class="a-label" for="berkas-<?= (int)$m['id'] ?>">Unggah berkas materi ini</label>
                                            <input id="berkas-<?= (int)$m['id'] ?>" name="berkas" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,.mp3,.m4a" required>
                                            <button class="a-btn a-btn--sekunder" type="submit">Unggah berkas</button>
                                        </form>
                                    <?php endif ?>
                                </div>
                            <?php endforeach ?>
                            <?php if($isDraft): ?><details class="a-cp__details"><summary>Tambah materi pada <?= esc($t['kode_tp']) ?></summary>
                                <form method="post" action="<?= esc(site_url('guru/tp/'.(int)$t['id'].'/materi'),'attr') ?>" class="a-form a-belajar-guru__inner">
                                    <?= csrf_field() ?>
                                    <div class="a-field"><label class="a-label" for="materi-judul-<?= (int)$t['id'] ?>">Judul materi</label>
                                        <input id="materi-judul-<?= (int)$t['id'] ?>" name="judul" minlength="4" maxlength="180" required></div>
                                    <div class="a-field"><label class="a-label" for="materi-jenis-<?= (int)$t['id'] ?>">Jenis media</label>
                                        <select id="materi-jenis-<?= (int)$t['id'] ?>" name="jenis" required>
                                            <option value="pdf">PDF</option><option value="gambar">Gambar</option>
                                            <option value="audio">Audio</option><option value="youtube">YouTube</option>
                                        </select></div>
                                    <div class="a-field"><label class="a-label" for="materi-url-<?= (int)$t['id'] ?>">URL video (hanya jika YouTube)</label>
                                        <input id="materi-url-<?= (int)$t['id'] ?>" type="url" name="url_youtube" placeholder="https://youtu.be/..."></div>
                                    <div class="a-field"><label class="a-label" for="materi-waktu-<?= (int)$t['id'] ?>">Minimal waktu aktif (detik)</label>
                                        <input id="materi-waktu-<?= (int)$t['id'] ?>" name="min_waktu_detik" type="number" min="10" max="36000" value="30" required></div>
                                    <div class="a-field"><label class="a-label" for="materi-resume-<?= (int)$t['id'] ?>">Petunjuk resume</label>
                                        <textarea id="materi-resume-<?= (int)$t['id'] ?>" name="petunjuk_resume" minlength="15" rows="3" required></textarea></div>
                                    <button class="a-btn a-btn--utama" type="submit">Simpan materi</button>
                                </form>
                            </details><?php endif ?>
                        </section>
                        <section><h3>Tugas HOTS</h3>
                            <?php if($hList!==[]): ?>
                                <p><?= esc($hList[0]['pertanyaan']) ?></p>
                                <p class="a-help"><?= esc($hList[0]['petunjuk']) ?></p>
                            <?php else: ?><p class="a-help">Tugas HOTS belum dibuat.</p><?php endif ?>
                            <?php if($isDraft): ?><details class="a-cp__details"><summary><?= $hList===[]?'Buat HOTS':'Perbarui HOTS' ?></summary>
                                <form method="post" action="<?= esc(site_url('guru/tp/'.(int)$t['id'].'/hots'),'attr') ?>" class="a-form a-belajar-guru__inner">
                                    <?= csrf_field() ?>
                                    <div class="a-field"><label class="a-label" for="hots-q-<?= (int)$t['id'] ?>">Pertanyaan HOTS</label>
                                        <textarea id="hots-q-<?= (int)$t['id'] ?>" name="pertanyaan" minlength="15" rows="3" required><?= esc($hList[0]['pertanyaan']??'') ?></textarea></div>
                                    <div class="a-field"><label class="a-label" for="hots-p-<?= (int)$t['id'] ?>">Petunjuk tugas</label>
                                        <textarea id="hots-p-<?= (int)$t['id'] ?>" name="petunjuk" minlength="12" rows="3" required><?= esc($hList[0]['petunjuk']??'') ?></textarea></div>
                                    <button class="a-btn a-btn--utama" type="submit">Simpan HOTS</button>
                                </form>
                            </details><?php endif ?>
                        </section>
                    </div>
                    <section><h3>Bank Soal · Diagnostik dan Sumatif</h3>
                        <p class="a-help">Jumlah diagnostik <?= $dCount ?> / minimal <?= (int)$t['diag_tayang'] ?>; sumatif <?= $sCount ?> / minimal <?= (int)$t['sum_tayang'] ?>. Semua opsi dan kunci divalidasi di server.</p>
                        <?php if($isDraft): ?>
                        <?php foreach(['diagnostik','sumatif'] as $jenis): ?>
                            <details class="a-cp__details"><summary>Tambah soal <?= esc(ucfirst($jenis)) ?></summary>
                                <form method="post" action="<?= esc(site_url('guru/tp/'.(int)$t['id'].'/soal/'.$jenis),'attr') ?>" class="a-form a-belajar-guru__inner">
                                    <?= csrf_field() ?>
                                    <div class="a-field"><label class="a-label" for="soal-q-<?= esc($jenis) ?>-<?= (int)$t['id'] ?>">Pertanyaan</label>
                                        <textarea id="soal-q-<?= esc($jenis) ?>-<?= (int)$t['id'] ?>" name="pertanyaan" minlength="3" rows="3" required></textarea></div>
                                    <div class="a-guru-form-grid">
                                        <?php foreach(['a','b','c','d','e'] as $opsi): ?>
                                            <div class="a-field"><label class="a-label" for="soal-<?= esc($jenis.'-'.$opsi.'-'.$t['id'],'attr') ?>">Opsi <?= strtoupper($opsi) ?><?= in_array($opsi,['a','b'],true)?' (wajib)':'' ?></label>
                                                <input id="soal-<?= esc($jenis.'-'.$opsi.'-'.$t['id'],'attr') ?>" name="opsi_<?= $opsi ?>" type="text" <?= in_array($opsi,['a','b'],true)?'required':'' ?>></div>
                                        <?php endforeach ?>
                                        <div class="a-field"><label class="a-label" for="kunci-<?= esc($jenis.'-'.$t['id'],'attr') ?>">Kunci</label>
                                            <select id="kunci-<?= esc($jenis.'-'.$t['id'],'attr') ?>" name="kunci" required>
                                                <?php foreach(['A','B','C','D','E'] as $opsi): ?><option value="<?= $opsi ?>"><?= $opsi ?></option><?php endforeach ?>
                                            </select></div>
                                    </div>
                                    <button class="a-btn a-btn--utama" type="submit">Simpan soal <?= esc($jenis) ?></button>
                                </form>
                            </details>
                        <?php endforeach ?>
                        <?php endif ?>
                    </section>
                    <section><h3>Parameter nilai dan ujian TP</h3>
                        <?php if($isDraft): ?>
                        <form class="a-form" method="post" action="<?= esc(site_url('guru/tp/'.(int)$t['id'].'/parameter'),'attr') ?>">
                            <?= csrf_field() ?>
                            <div class="a-guru-form-grid">
                                <?php foreach([
                                    'kkm'=>'KKM (1–100)','bobot_resume'=>'Bobot Resume (%)','bobot_tugas'=>'Bobot HOTS (%)',
                                    'bobot_sumatif'=>'Bobot Sumatif (%)','resume_min'=>'Resume minimum karakter',
                                    'tugas_min'=>'Tugas minimum karakter','diag_tayang'=>'Soal tayang diagnostik (≥10)',
                                    'sum_tayang'=>'Soal tayang sumatif (≥5)','diag_durasi'=>'Durasi diagnostik (menit)',
                                    'sum_durasi'=>'Durasi sumatif (menit)','sum_maks_percobaan'=>'Percobaan sumatif maksimum'
                                ] as $field=>$label): ?>
                                    <div class="a-field"><label class="a-label" for="<?= esc($field.'-'.$t['id'],'attr') ?>"><?= esc($label) ?></label>
                                        <input id="<?= esc($field.'-'.$t['id'],'attr') ?>" name="<?= esc($field,'attr') ?>" type="number"
                                            min="1" max="5000" value="<?= (int)$t[$field] ?>" required></div>
                                <?php endforeach ?>
                            </div>
                            <p class="a-help">Jumlah bobot harus 100%, bobot sumatif >0. Batas soal dan durasi diperiksa pada server.</p>
                            <button class="a-btn a-btn--sekunder" type="submit">Simpan parameter TP</button>
                        </form>
                        <?php else: ?><p class="a-help">Parameter terkunci mengikuti snapshot BAB Siap.</p><?php endif ?>
                    </section>
                </div>
            </details>
        <?php endforeach ?>
        <?php endif ?>
    </section>
    <section class="a-card" id="excel"><h2>3 · Impor Excel BAB (8 sheet)</h2>
        <?php if(!$isDraft): ?><div class="a-alert">BAB Siap tidak menerima unggah/impor lagi.</div>
        <?php elseif($tp===[]): ?><div class="a-empty">Buat TP sebelum mengunduh template.</div>
        <?php else: ?>
            <p class="a-help">Template terikat BAB, struktur TP/CP dan Gen melalui HMAC. Excel hanya melengkapi parameter, materi, HOTS dan soal, tidak membuat TP baru.</p>
            <div class="a-belajar-guru__aksi"><a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/bab/'.(int)$bab['id'].'/template'),'attr') ?>">Unduh template 8 sheet XLSX</a></div>
            <form method="post" enctype="multipart/form-data" action="<?= esc(site_url('guru/bab/'.(int)$bab['id'].'/impor'),'attr') ?>" class="a-form">
                <?= csrf_field() ?>
                <div class="a-field"><label class="a-label" for="berkas-excel">Pilih XLSX terisi (maksimal 5 MB)</label>
                    <input type="file" id="berkas-excel" name="berkas" accept=".xlsx" required></div>
                <button class="a-btn a-btn--utama" type="submit">Validasi dan pratinjau impor</button>
            </form>
        <?php endif ?>
    </section>
    <section class="a-card" id="kesiapan"><h2>4 · Checklist kesiapan publikasi BAB</h2>
        <div class="a-belajar-guru__stat">
            <strong><?= $checklist['siap']?'Seluruh syarat terpenuhi':'Perlu dilengkapi' ?></strong>
            <span><?= count($checklist['pesan']) ?> temuan</span>
        </div>
        <?php if($checklist['pesan']!==[]): ?>
            <ul class="a-belajar-guru__masalah">
                <?php foreach(array_slice($checklist['pesan'],0,10) as $m): ?><li><?= esc($m) ?></li><?php endforeach ?>
            </ul>
        <?php endif ?>
        <a class="a-btn a-btn--utama" href="<?= esc(site_url('guru/bab/'.(int)$bab['id'].'/checklist'),'attr') ?>">Periksa checklist lengkap dan publikasi</a>
    </section>
</div>
<?= $this->endSection() ?>
