'use strict';

const assert = require('node:assert/strict');
const test = require('node:test');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const file = fs.readFileSync(path.resolve(__dirname,
    '../../public/assets/js/pembaca-pdf-materi.js'), 'utf8');
const activity = fs.readFileSync(path.resolve(__dirname,
    '../../public/assets/js/aktivitas-materi.js'), 'utf8');

test('PDF.js lokal dan bukan CDN, worker serta halaman tervalidasi', () => {
    assert.match(file, /pdfjsLib\.getDocument/);
    assert.match(file, /GlobalWorkerOptions\.workerSrc/);
    assert.match(file, /pdf\.numPages !== expected/);
    assert.match(file, /disableRange: true/);
    assert.doesNotMatch(file, /https:\/\/(?:cdn|unpkg|cdnjs)/);
});
test('Perenderan gagal tidak menghasilkan status halaman siap', () => {
    assert.match(file, /ready\(false\)/);
    assert.match(file, /await task\.promise/);
    assert.match(file, /ready\(true\)/);
    assert.match(file, /RenderingCancelledException/);
});
test('Pencatatan bukan berdasarkan detik palsu dari browser', () => {
    assert.match(activity, /halaman:String\(halaman\)/);
    assert.match(activity, /jumlah_halaman:String\(total\)/);
    assert.match(activity, /resetPdf/);
    assert.doesNotMatch(activity, /aktif_detik:\s*(?:Date|Math)/);
});
test('Skrip mempunyai sintaks yang dapat dieksekusi', () => {
    // Tanpa DOM hanya cek closure script valid, eksekusi segera berakhir.
    vm.runInNewContext(file, {document: {querySelector: () => null}});
});
