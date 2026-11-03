<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= esc($judul) ?></title>
    <style>
        :root{color-scheme:light;background:#edf4f1;color:#173d32;font:15px/1.55 system-ui,sans-serif}
        *{box-sizing:border-box}main{max-width:920px;margin:7vh auto;padding:25px}
        .panel{background:#fff;border:1px solid #b1d2c1;border-radius:18px;
            box-shadow:0 12px 38px #153d3015;padding:28px}
        h1{margin:0 0 8px;font-size:clamp(23px,3vw,32px)}.label{font-size:12px;color:#48675c}
        .status{display:inline-block;padding:6px 14px;border-radius:99px;
            font-weight:750;background:#e3f6e9;border:1px solid #74b997}
        .status.lama{background:#fff0d7;border-color:#d5a459}
        .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
        .grid div{background:#f4f8f6;border-radius:11px;padding:13px}
        .grid strong{display:block;overflow-wrap:anywhere}
        .scroll{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:520px;margin-top:12px}
        th,td{border-bottom:1px solid #d3e3da;padding:9px;text-align:left}
        th{background:#edf5ef}a{color:#125b43}
        .notice{margin:14px 0;font-size:13px}
        @media(max-width:620px){main{padding:10px;margin-top:15px}.panel{padding:18px}
            .grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<main><article class="panel">
    <p class="label">SLA-SKS · Verifikasi Dokumen Akademik</p>
    <h1>Verifikasi Sertifikat</h1>
    <p><span class="status <?= $hasil['status']==='berlaku'?'':'lama' ?>">
        <?= $hasil['status']==='berlaku'?'Sertifikat Berlaku':'Sertifikat Digantikan' ?>
    </span></p>
    <div class="grid">
        <div><span class="label">Nomor</span><strong><?= esc($hasil['nomor']) ?></strong></div>
        <div><span class="label">Nama Siswa</span><strong><?= esc($hasil['nama_siswa']) ?></strong></div>
        <div><span class="label">Mata Pelajaran</span><strong><?= esc($hasil['nama_mapel']) ?></strong></div>
        <div><span class="label">Semester dan Versi</span>
            <strong><?= (int)$hasil['semester'] ?> · V<?= (int)$hasil['versi'] ?></strong></div>
        <div><span class="label">Tanggal Terbit (UTC)</span>
            <strong><?= esc($hasil['tanggal']) ?></strong></div>
    </div>
    <?php if ($hasil['status']!=='berlaku' && $hasil['kode_aktif']!==null): ?>
        <p class="notice">Versi ini sudah digantikan oleh
            <a href="<?= esc(site_url('verifikasi/'.$hasil['kode_aktif']),'attr') ?>">
                <?= esc($hasil['nomor_aktif']) ?></a>. Arsip tetap dapat diverifikasi.</p>
    <?php endif ?>
    <h2>Daftar Tujuan Pembelajaran</h2>
    <div class="scroll"><table>
        <thead><tr><th scope="col">Kode TP</th><th scope="col">Nama TP</th>
            <th scope="col">Jenis</th><th scope="col">Nilai TP</th></tr></thead>
        <tbody>
        <?php foreach ($hasil['tp'] as $t): ?>
            <tr><td><?= esc($t['kode_tp']) ?></td><td><?= esc($t['nama_tp']) ?></td>
                <td><?= ($t['jenis_tp']??'')==='esensial'?'Esensial':'NonEsensial' ?></td>
                <td><?= $t['nilai_tp']===null?'—':
                    esc(number_format((float)$t['nilai_tp'],2,',','.')) ?></td></tr>
        <?php endforeach ?>
        </tbody>
    </table></div>
    <p class="notice">Halaman publik hanya mengonfirmasi catatan dokumen.
        NISN, tanda tangan, kontak dan berkas PDF tidak ditampilkan.
        QR bukan tanda tangan elektronik tersertifikasi.</p>
</article></main>
</body></html>
