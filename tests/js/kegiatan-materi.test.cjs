'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.resolve(__dirname,
    '../../public/assets/js/kegiatan-materi.js'), 'utf8');

function setup(confirmValue = true, minimum = 10) {
    const handlers = {};
    function item() {
        const listeners = {};
        return {
            value: '', defaultValue: '', textContent: '', attributes: {},
            disabled: false, focused: false,
            addEventListener(type, callback) { (listeners[type] ||= []).push(callback); },
            dispatch(type, event = {}) { for (const fn of listeners[type] || []) fn(event); },
            setAttribute(key, val) { this.attributes[key] = val; },
            focus() { this.focused = true; },
        };
    }
    const area = item();
    area.value = 'Jawaban awal'; area.defaultValue = 'Jawaban awal';
    const status = item();
    const validation = item();
    const save = item(); save.value = 'simpan';
    const finish = item(); finish.value = 'kirim';
    const form = item(); form.dataset = {minimal: String(minimum)};
    form.querySelector = selector => ({
        'textarea[name="jawaban"]': area,
        '[data-kegiatan-indikator]': status,
        '[data-kegiatan-validasi]': validation,
    })[selector] || null;
    form.querySelectorAll = () => [save, finish];
    let confirms = 0;
    const window = {
        confirm() { confirms++; return confirmValue; },
        addEventListener(type, fn) { handlers[type] = fn; },
    };
    vm.runInNewContext(source, {
        document: {querySelectorAll: () => [form]},
        window,
    });
    return {area, form, status, validation, save, finish, handlers,
        get confirms() {return confirms;}};
}

test('Jawaban berubah ditandai belum tersimpan dan browser memperingatkan', () => {
    const x = setup();
    x.area.value = 'Perubahan baru';
    x.area.dispatch('input');
    assert.match(x.status.textContent, /belum disimpan/i);
    let prevented = false;
    x.handlers.beforeunload({preventDefault() {prevented = true;}});
    assert.equal(prevented, true);
});

test('Kirim final sah memerlukan konfirmasi; submitter tidak di-disable', () => {
    const x = setup();
    x.area.value = 'Jawaban baru';
    let prevented = false;
    x.form.dispatch('submit', {submitter: x.finish,
        preventDefault() {prevented = true;}});
    assert.equal(prevented, false);
    assert.equal(x.confirms, 1);
    assert.equal(x.form.dataset.mengirim, 'ya');
    assert.equal(x.finish.disabled, false);
    assert.equal(x.save.disabled, false);
    assert.match(x.status.textContent, /mengirim/i);
    let duplicatePrevented = false;
    x.form.dispatch('submit', {submitter: x.finish,
        preventDefault() {duplicatePrevented = true;}});
    assert.equal(duplicatePrevented, true);
});

test('Batal konfirmasi final tidak mengirim dan tidak mengunci form', () => {
    const x = setup(false);
    let prevented = false;
    x.form.dispatch('submit', {submitter: x.finish,
        preventDefault() {prevented = true;}});
    assert.equal(prevented, true);
    assert.equal(x.form.dataset.mengirim, undefined);
    assert.equal(x.finish.disabled, false);
});

test('Final HOTS pendek ditolak sebelum POST; draf tetap boleh disimpan', () => {
    const x = setup(true, 200);
    x.area.value = 'HOTS pendek';
    let prevented = false;
    x.form.dispatch('submit', {submitter: x.finish,
        preventDefault() {prevented = true;}});
    assert.equal(prevented, true);
    assert.equal(x.confirms, 0);
    assert.equal(x.form.dataset.mengirim, undefined);
    assert.equal(x.area.focused, true);
    assert.match(x.validation.textContent, /Minimal 200/);
    let draftPrevented = false;
    x.form.dispatch('submit', {submitter: x.save,
        preventDefault() {draftPrevented = true;}});
    assert.equal(draftPrevented, false);
});

test('Final HOTS tepat 200 karakter berhasil melewati validasi klien', () => {
    const x = setup(true, 200);
    x.area.value = 'H'.repeat(200);
    let prevented = false;
    x.form.dispatch('submit', {submitter: x.finish,
        preventDefault() {prevented = true;}});
    assert.equal(prevented, false);
    assert.equal(x.confirms, 1);
});

test('Paste, copy, cut, drop, Ctrl+V/C/X, dan beforeinput tempel diblokir', () => {
    const x = setup();
    for (const type of ['paste', 'copy', 'cut', 'drop']) {
        let blocked = false;
        x.area.dispatch(type, {preventDefault() {blocked = true;}});
        assert.equal(blocked, true, type);
    }
    for (const inputType of ['insertFromPaste', 'insertFromDrop']) {
        let blocked = false;
        x.area.dispatch('beforeinput', {inputType, preventDefault() {blocked = true;}});
        assert.equal(blocked, true, inputType);
    }
    for (const key of ['v', 'c', 'x']) {
        let blocked = false;
        x.area.dispatch('keydown', {ctrlKey: true, key,
            preventDefault() {blocked = true;}});
        assert.equal(blocked, true, 'Ctrl+' + key);
    }
    assert.match(x.validation.textContent, /Salin\/tempel/);
});

test('Pengetikan manual tetap normal dan penghitung mendukung Unicode', () => {
    const x = setup(true, 200);
    x.area.value = 'مرحبا';
    x.area.dispatch('input');
    assert.match(x.validation.textContent, /5 karakter/);
    assert.equal(x.form.dataset.mengirim, undefined);
});


test('Endpoint final dan draf eksplisit serta controller memvalidasi aksi', () => {
    const route = fs.readFileSync(path.resolve(__dirname,
        '../../app/Config/Routes.php'), 'utf8');
    const view = fs.readFileSync(path.resolve(__dirname,
        '../../app/Views/siswa/materi.php'), 'utf8');
    const controller = fs.readFileSync(path.resolve(__dirname,
        '../../app/Controllers/SiswaKegiatanMateri.php'), 'utf8');
    assert.match(route, /kegiatan\/\(:segment\)\/\(:segment\)/);
    assert.match(view, /ruteKegiatan \. '\/simpan'/);
    assert.match(view, /ruteKegiatan \. '\/kirim'/);
    assert.match(controller, /\$aksi\s*=\s*\$aksiRute\s*\?\?\s*\$aksiForm/);
    assert.match(controller, /kegiatan_jenis/);
    assert.match(controller, /withInput\(\)/);
});
