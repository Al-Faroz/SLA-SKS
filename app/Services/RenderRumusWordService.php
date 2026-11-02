<?php

declare(strict_types=1);

namespace App\Services;

use DOMNode;

/**
 * Renderer Equation Word (OMML) ke MathML untuk konstruksi umum.
 * Berbasis DOM, bukan interpretasi HTML dari dokumen; semua teks di-escape.
 */
final class RenderRumusWordService
{
    public static function html(DOMNode $node): string
    {
        return '<span class="a-word-math" title="Equation Word; periksa juga berkas asli">'
            . '<math xmlns="http://www.w3.org/1998/Math/MathML" display="inline">'
            . '<mrow>' . self::render($node, 0) . '</mrow></math></span>';
    }

    private static function anak(DOMNode $node, string $nama): ?DOMNode
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === $nama) {
                return $child;
            }
        }
        return null;
    }

    private static function isi(?DOMNode $node, int $depth): string
    {
        return $node === null ? '<mrow></mrow>' : '<mrow>' . self::semua($node, $depth + 1) . '</mrow>';
    }

    private static function semua(DOMNode $node, int $depth): string
    {
        $html = '';
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $html .= self::render($child, $depth + 1);
            }
        }
        return $html;
    }

    private static function render(DOMNode $node, int $depth): string
    {
        if ($depth > 48) {
            return '<mtext>[Rumus terlalu kompleks]</mtext>';
        }
        $tag = $node->localName;
        if (str_ends_with($tag, 'Pr') || in_array($tag, ['ctrlPr', 'argPr'], true)) {
            return '';
        }
        if ($tag === 't') {
            $text = htmlspecialchars($node->textContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return '<mtext>' . $text . '</mtext>';
        }
        if ($tag === 'f') {
            return '<mfrac>' . self::isi(self::anak($node, 'num'), $depth)
                . self::isi(self::anak($node, 'den'), $depth) . '</mfrac>';
        }
        if ($tag === 'sSup') {
            return '<msup>' . self::isi(self::anak($node, 'e'), $depth)
                . self::isi(self::anak($node, 'sup'), $depth) . '</msup>';
        }
        if ($tag === 'sSub') {
            return '<msub>' . self::isi(self::anak($node, 'e'), $depth)
                . self::isi(self::anak($node, 'sub'), $depth) . '</msub>';
        }
        if ($tag === 'sSubSup') {
            return '<msubsup>' . self::isi(self::anak($node, 'e'), $depth)
                . self::isi(self::anak($node, 'sub'), $depth)
                . self::isi(self::anak($node, 'sup'), $depth) . '</msubsup>';
        }
        if ($tag === 'rad') {
            $e = self::isi(self::anak($node, 'e'), $depth);
            $deg = self::anak($node, 'deg');
            if ($deg === null || trim($deg->textContent) === '') {
                return '<msqrt>' . $e . '</msqrt>';
            }
            return '<mroot>' . $e . self::isi($deg, $depth) . '</mroot>';
        }
        if ($tag === 'limLow') {
            return '<munder>' . self::isi(self::anak($node, 'e'), $depth)
                . self::isi(self::anak($node, 'lim'), $depth) . '</munder>';
        }
        if ($tag === 'limUpp') {
            return '<mover>' . self::isi(self::anak($node, 'e'), $depth)
                . self::isi(self::anak($node, 'lim'), $depth) . '</mover>';
        }
        if ($tag === 'func') {
            return '<mrow>' . self::isi(self::anak($node, 'fName'), $depth)
                . self::isi(self::anak($node, 'e'), $depth) . '</mrow>';
        }
        if ($tag === 'eqArr' || $tag === 'm') {
            $rows = '';
            foreach ($node->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE && in_array($child->localName, ['e', 'mr'], true)) {
                    $rows .= '<mtr><mtd>' . self::isi($child, $depth) . '</mtd></mtr>';
                }
            }
            return $rows !== '' ? '<mtable>' . $rows . '</mtable>' : self::semua($node, $depth);
        }
        if ($tag === 'd') {
            return '<mrow><mo>(</mo>' . self::isi(self::anak($node, 'e'), $depth) . '<mo>)</mo></mrow>';
        }
        if ($tag === 'nary') {
            $naryPr = self::anak($node, 'naryPr');
            $chr = $naryPr === null ? null : self::anak($naryPr, 'chr');
            $symbol = '∑';
            if ($chr !== null && $chr->attributes !== null) {
                foreach ($chr->attributes as $attribute) {
                    if ($attribute->localName === 'val') {
                        $symbol = $attribute->nodeValue;
                    }
                }
            }
            return '<mrow><munderover><mo>' . htmlspecialchars($symbol, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</mo>' . self::isi(self::anak($node, 'sub'), $depth)
                . self::isi(self::anak($node, 'sup'), $depth) . '</munderover>'
                . self::isi(self::anak($node, 'e'), $depth) . '</mrow>';
        }
        // Wrapper OOXML lain tetap diteruskan, supaya semua m:t terjaga
        // meskipun tata letak konstruksi langka belum dapat direplikasi.
        return self::semua($node, $depth);
    }
}
