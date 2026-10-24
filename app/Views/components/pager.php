<?php
/**
 * Paginasi server-side seragam: halaman, total data dan tautan aman ke filter asli.
 * Input: urlPath, pageParam, halaman, perHalaman, total, filter, label, anchor.
 */
$urlPath = (string) ($urlPath ?? '');
$pageParam = (string) ($pageParam ?? 'halaman');
$halaman = max(1, (int) ($halaman ?? 1));
$perHalaman = max(1, (int) ($perHalaman ?? 25));
$total = max(0, (int) ($total ?? 0));
$filter = is_array($filter ?? null) ? $filter : [];
$label = (string) ($label ?? 'Daftar');
$anchor = (string) ($anchor ?? '');
$jumlahHalaman = max(1, (int) ceil($total / $perHalaman));
$halaman = min($halaman, $jumlahHalaman);
$awal = $total === 0 ? 0 : ($halaman - 1) * $perHalaman + 1;
$akhir = min($total, $halaman * $perHalaman);
$filter = array_filter($filter, static fn ($nilai): bool => $nilai !== null && $nilai !== '');
$tautan = static function (int $ke) use ($urlPath, $pageParam, $filter, $anchor): string {
    $query = http_build_query(array_merge($filter, [$pageParam => $ke]));
    return site_url($urlPath) . ($query === '' ? '' : '?' . $query)
        . ($anchor === '' ? '' : '#' . rawurlencode($anchor));
};
$nomor = array_values(array_unique(array_merge(
    [1, $jumlahHalaman],
    range(max(1, $halaman - 2), min($jumlahHalaman, $halaman + 2))
)));
sort($nomor, SORT_NUMERIC);
?>
<nav class="a-pager a-pager--lengkap" aria-label="<?= esc('Navigasi halaman ' . $label, 'attr') ?>">
    <div class="a-pager__ringkasan">
        <span><?= $awal ?>–<?= $akhir ?> dari <?= $total ?> data</span>
        <span>Halaman <?= $halaman ?> dari <?= $jumlahHalaman ?></span>
    </div>
    <?php if ($jumlahHalaman > 1): ?>
        <div class="a-pager__aksi">
            <?php if ($halaman > 1): ?>
                <a class="a-pager__tombol" href="<?= esc($tautan(1), 'attr') ?>">Pertama</a>
                <a class="a-pager__tombol" href="<?= esc($tautan($halaman - 1), 'attr') ?>">Sebelumnya</a>
            <?php else: ?>
                <span class="a-pager__tombol is-nonaktif" aria-disabled="true">Pertama</span>
                <span class="a-pager__tombol is-nonaktif" aria-disabled="true">Sebelumnya</span>
            <?php endif ?>
            <?php $sebelumnya = 0; ?>
            <?php foreach ($nomor as $n): ?>
                <?php if ($sebelumnya > 0 && $n > $sebelumnya + 1): ?>
                    <span class="a-pager__elipsis" aria-hidden="true">…</span>
                <?php endif ?>
                <?php if ($n === $halaman): ?>
                    <span class="a-pager__tombol is-aktif" aria-current="page"><?= $n ?></span>
                <?php else: ?>
                    <a class="a-pager__tombol" href="<?= esc($tautan($n), 'attr') ?>" aria-label="Halaman <?= $n ?>"><?= $n ?></a>
                <?php endif ?>
                <?php $sebelumnya = $n; ?>
            <?php endforeach ?>
            <?php if ($halaman < $jumlahHalaman): ?>
                <a class="a-pager__tombol" href="<?= esc($tautan($halaman + 1), 'attr') ?>">Berikutnya</a>
                <a class="a-pager__tombol" href="<?= esc($tautan($jumlahHalaman), 'attr') ?>">Terakhir</a>
            <?php else: ?>
                <span class="a-pager__tombol is-nonaktif" aria-disabled="true">Berikutnya</span>
                <span class="a-pager__tombol is-nonaktif" aria-disabled="true">Terakhir</span>
            <?php endif ?>
        </div>
    <?php endif ?>
</nav>
