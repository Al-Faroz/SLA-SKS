'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.resolve(__dirname,
    '../../public/assets/js/kegiatan-materi.js'), 'utf8');

function setup(confirmValue = true) {
    const handlers = {};
    function item() {
        const listeners = {};
        return {
            value: '', defaultValue: '', textContent: '', attributes: {}, disabled: false,
            addEventListener(type, callback) { (listeners[type] ||= []).push(callback); },
            dispatch(type, event = {}) { for (const fn of listeners[type] || []) fn(event); },
            setAttribute(key, val) { this.attributes[key] = val; },
        };
    }
    const area = item();
    area.value = 'Jawaban awal'; area.defaultValue = 'Jawaban awal';
    const status = item();
    const save = item(); save.value = 'simpan';
    const finish = item(); finish.value = 'kirim';
    const form = item(); form.dataset = {};
    form.querySelector = selector => ({
        'textarea[name="jawaban"]': area,
        '[data-kegiatan-indikator]': status,
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
    return {area, form, status, save, finish, handlers, get confirms() {return confirms;}};
}

test('Jawaban berubah ditandai tidak tersimpan dan browser memperingatkan', () => {
    const x = setup();
    x.area.value = 'Perubahan baru';
    x.area.dispatch('input');
    assert.match(x.status.textContent, /belum disimpan/i);
    let prevented = false;
    x.handlers.beforeunload({preventDefault() {prevented = true;}});
    assert.equal(prevented, true);
});

test('Kirim final memerlukan konfirmasi, aksi POST tidak hilang karena disabled', () => {
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
    x.form.dispatch('submit', {submitter:x.finish,
        preventDefault() {duplicatePrevented = true;}});
    assert.equal(duplicatePrevented, true);
});

test('Pembatalan konfirmasi final tidak mengirim dan tidak mengunci form', () => {
    const x = setup(false);
    let prevented = false;
    x.form.dispatch('submit', {submitter: x.finish,
        preventDefault() {prevented = true;}});
    assert.equal(prevented, true);
    assert.equal(x.form.dataset.mengirim, undefined);
    assert.equal(x.finish.disabled, false);
});
