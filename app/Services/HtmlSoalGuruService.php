<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DomainException;

/**
 * Normalisasi HTML terbatas dari editor soal Guru.
 * HTML Word yang sebelumnya dibuat oleh RenderWordSoalService tetap dikelola terpisah.
 */
final class HtmlSoalGuruService
{
    private const TAG = ['p', 'div', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup',
        'ul', 'ol', 'li', 'blockquote', 'table', 'thead', 'tbody', 'tr', 'td', 'th',
        'span', 'a'];

    public static function bersihkan(string $html): array
    {
        if (strlen($html) > 100000) {
            throw new DomainException('Isi soal atau pilihan terlalu panjang (maksimal 100 KB).');
        }
        if (preg_match('~<\\s*(script|style|iframe|object|embed|svg|math|img|form|input|video|audio|canvas|link|meta)\\b~i', $html)) {
            throw new DomainException('Konten aktif/gambar belum didukung lewat editor. Gunakan Word untuk gambar atau sisipkan teks/tabel biasa.');
        }
        $old = libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        try {
            $ok = $dom->loadHTML('<?xml encoding="utf-8"?><div id="editor-soal">' . $html . '</div>',
                LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            if (!$ok) {
                throw new DomainException('Format isi soal tidak dapat dibaca.');
            }
            $xp = new \DOMXPath($dom);
            $root = $xp->query('//*[@id="editor-soal"]')->item(0);
            if (!$root) {
                throw new DomainException('Isi soal tidak dikenali.');
            }
            $out = '';
            foreach ($root->childNodes as $node) {
                $out .= self::node($node);
            }
            $teks = trim(preg_replace('/\\s+/u', ' ', (string) $root->textContent) ?? '');
            if ($teks === '') {
                throw new DomainException('Pertanyaan dan semua pilihan A–E harus memiliki isi.');
            }
            return ['html' => $out, 'teks' => $teks];
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($old);
        }
    }

    private static function node(DOMNode $n): string
    {
        if ($n->nodeType === XML_TEXT_NODE) {
            return htmlspecialchars($n->nodeValue ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        if (!($n instanceof DOMElement)) {
            return '';
        }
        $tag = strtolower($n->tagName);
        if (!in_array($tag, self::TAG, true)) {
            // Tidak izinkan isi script/style dan elemen yang dapat memuat kode.
            if (in_array($tag, ['script', 'style', 'svg', 'iframe', 'object', 'form', 'textarea'], true)) {
                throw new DomainException('Konten editor mengandung elemen yang tidak diizinkan.');
            }
            $inner = '';
            foreach ($n->childNodes as $child) {
                $inner .= self::node($child);
            }
            return $inner;
        }
        $attr = '';
        if ($n->hasAttribute('dir') && in_array($n->getAttribute('dir'), ['rtl','ltr'], true)) {
            $attr .= ' dir="' . $n->getAttribute('dir') . '"';
        }
        if (in_array($tag, ['td', 'th'], true)) {
            foreach (['colspan', 'rowspan'] as $name) {
                $nval = (int) $n->getAttribute($name);
                if ($nval > 1 && $nval <= 8) {
                    $attr .= ' ' . $name . '="' . $nval . '"';
                }
            }
        }
        if ($tag === 'a') {
            $href = $n->getAttribute('href');
            if (preg_match('~^https://drive\\.google\\.com/(?:file/d/|open\\?id=)[A-Za-z0-9_/?=&-]+$~D', $href)) {
                $attr .= ' href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" rel="noopener noreferrer"';
            } else {
                throw new DomainException('Untuk media soal gunakan tautan Google Drive HTTPS yang valid.');
            }
        }
        if ($tag === 'br') {
            return '<br>';
        }
        $inner = '';
        foreach ($n->childNodes as $child) {
            $inner .= self::node($child);
        }
        return '<' . $tag . $attr . '>' . $inner . '</' . $tag . '>';
    }
}
