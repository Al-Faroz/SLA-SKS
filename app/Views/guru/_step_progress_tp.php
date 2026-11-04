<?php
/** @var list<array{label:string,keterangan:string,terpenuhi:bool,tautan:string}> $langkah */
?>
<ol class="a-tp-langkah" aria-label="Langkah penyiapan TP">
    <?php foreach ($langkah as $urutan => $step): ?>
        <li class="a-tp-langkah__item <?= $step['terpenuhi'] ? 'is-selesai' : 'is-belum' ?>">
            <strong><?= (int) ($urutan + 1) ?>. <?= esc($step['label']) ?></strong>
            <small><?= esc($step['keterangan']) ?></small>
            <p class="a-tp-langkah__status" aria-label="<?= $step['terpenuhi'] ? 'Selesai' : 'Perlu dilengkapi' ?>"><?= $step['terpenuhi'] ? '✓ Selesai' : '○ Perlu dilengkapi' ?></p>
            <a class="a-btn a-btn--sekunder"
               href="<?= esc(site_url($step['tautan']), 'attr') ?>">
                <?= $step['terpenuhi'] ? 'Lihat' : 'Lengkapi' ?>
            </a>
        </li>
    <?php endforeach ?>
</ol>
