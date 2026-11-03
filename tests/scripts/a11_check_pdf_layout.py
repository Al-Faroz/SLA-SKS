#!/usr/bin/env python3
"""QA geometri PDF sintetis dari pdftotext -bbox-layout (tanpa OCR/data siswa)."""

import sys
import xml.etree.ElementTree as ET

if len(sys.argv) != 2:
    raise SystemExit("Gunakan: python a11_check_pdf_layout.py <bbox-layout.html>")

pages = [el for el in ET.parse(sys.argv[1]).getroot().iter()
         if el.tag.endswith("page")]
if len(pages) != 1:
    raise SystemExit(f"GAGAL: pratinjau empat TP harus 1 halaman, bukan {len(pages)}.")

words = []
for el in pages[0].iter():
    if el.tag.endswith("word") and el.text:
        words.append((
            el.text.strip(),
            float(el.attrib["yMin"]),
            float(el.attrib["yMax"]),
        ))

# Teks terakhir pada baris TP keempat memiliki judul sintetis 'realistis'.
akhir_tp = [w[2] for w in words if w[0] == "realistis"]
# Kota Contoh = bagian tanda tangan; Pratinjau = area QR placeholder.
awal_footer = [w[1] for w in words if w[0] in ("Kota", "Pratinjau")]
if len(akhir_tp) != 4 or not awal_footer:
    raise SystemExit("GAGAL: sampel atau label footer PDF tidak lengkap.")

celah = min(awal_footer) - max(akhir_tp)
if celah < 8.0:
    raise SystemExit(
        f"GAGAL: footer bertumpuk/terlalu dekat dengan baris TP terakhir "
        f"(jarak {celah:.1f} pt, minimum 8 pt)."
    )

print(f"PASS: 4 TP dan footer terpisah {celah:.1f} pt, tanpa overlap.")
