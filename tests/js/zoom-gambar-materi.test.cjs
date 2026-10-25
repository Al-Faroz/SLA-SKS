'use strict';

/* Pengujian perilaku JS pembaca gambar tanpa jaringan/server dan data siswa. */
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.resolve(__dirname,
    '../../public/assets/js/pembaca-materi.js'), 'utf8');

function element() {
    const listeners = {};
    return {
        style: {}, disabled: false, textContent: '', scrollLeft: 0, scrollTop: 0,
        attributes: {},
        listeners,
        addEventListener(name, fn) { (listeners[name] ||= []).push(fn); },
        dispatch(name, event = {}) {
            for (const fn of listeners[name] || []) fn(event);
        },
        setAttribute(name, value) { this.attributes[name] = value; },
        classList: {
            classes: new Set(),
            toggle(name, on) { if (on) this.classes.add(name); else this.classes.delete(name); },
            contains(name) { return this.classes.has(name); }
        },
        setPointerCapture() {},
    };
}
function harness(naturalWidth, naturalHeight, viewportWidth = 800, viewportHeight = 500) {
    const selectors = [
        '[data-pembaca-toggle]', '[data-pembaca-pesan]',
        '[data-gambar-materi]', '[data-gambar-observer]', '[data-gambar-stage]',
        '[data-zoom-tambah]', '[data-zoom-kurang]', '[data-zoom-reset]',
        '[data-zoom-nilai]'
    ];
    const elements = Object.fromEntries(selectors.map(name => [name, element()]));
    const root = element();
    root.querySelector = key => elements[key] || null;
    root.requestFullscreen = async () => {
        doc.fullscreenElement = root;
        area.clientWidth = 1200; area.clientHeight = 750;
        doc.dispatch('fullscreenchange');
    };
    const image = elements['[data-gambar-materi]'];
    image.complete = true;
    image.naturalWidth = naturalWidth;
    image.naturalHeight = naturalHeight;
    const area = elements['[data-gambar-observer]'];
    area.clientWidth = viewportWidth;
    area.clientHeight = viewportHeight;
    const doc = element();
    doc.fullscreenEnabled = true;
    doc.fullscreenElement = null;
    doc.querySelectorAll = () => [root];
    doc.exitFullscreen = async () => {
        doc.fullscreenElement = null;
        area.clientWidth = viewportWidth; area.clientHeight = viewportHeight;
        doc.dispatch('fullscreenchange');
    };
    const resizeCallbacks = [];
    const win = element();
    win.ResizeObserver = class {
        constructor(callback) { resizeCallbacks.push(callback); }
        observe() {}
    };
    win.requestAnimationFrame = fn => fn();
    vm.runInNewContext(source, {window: win, document: doc, console}, {timeout: 2000});
    const click = key => elements[key].dispatch('click');
    const size = () => ({
        w: Number.parseFloat(image.style.width),
        h: Number.parseFloat(image.style.height),
        stageW: Number.parseFloat(elements['[data-gambar-stage]'].style.width),
        stageH: Number.parseFloat(elements['[data-gambar-stage]'].style.height),
    });
    const zoom = () => elements['[data-zoom-nilai]'].textContent;
    return {elements, root, doc, area, image, win, click, size, zoom,
        resized() { resizeCallbacks.forEach(fn => fn()); }};
}
const near = (value, expected) => assert.ok(Math.abs(value - expected) < 0.05,
    `expected ${expected}, got ${value}`);

test('gambar lanskap: 100% muat layar, perbesar 125%, reset dan scroll horizontal', () => {
    const env = harness(1600, 900);
    near(env.size().w, 800);
    near(env.size().h, 450);
    env.click('[data-zoom-tambah]');
    assert.equal(env.zoom(), '125%');
    near(env.size().w, 1000);
    near(env.size().h, 562.5);
    assert.ok(env.size().stageW > env.area.clientWidth);
    assert.ok(env.area.scrollLeft > 0);
    env.click('[data-zoom-reset]');
    assert.equal(env.zoom(), '100%');
    near(env.size().w, 800);
    near(env.area.scrollLeft, 0);
});

test('gambar potret: seluruh gambar muat saat 100%, zoom membuat scroll vertikal', () => {
    const env = harness(800, 1600);
    near(env.size().w, 250);
    near(env.size().h, 500);
    for (let i = 0; i < 4; i++) env.click('[data-zoom-tambah]');
    assert.equal(env.zoom(), '200%');
    near(env.size().w, 500);
    near(env.size().h, 1000);
    assert.ok(env.area.scrollTop > 0);
});

test('gambar kecil tidak diperbesar paksa pada skala awal', () => {
    const env = harness(200, 100);
    near(env.size().w, 200);
    near(env.size().h, 100);
    env.click('[data-zoom-tambah]');
    near(env.size().w, 250);
    near(env.size().h, 125);
});

test('zoom dikunci 50%-300%, reset dan tombol aktif sesuai batas', () => {
    const env = harness(1500, 1000);
    for (let i = 0; i < 30; i++) env.click('[data-zoom-tambah]');
    assert.equal(env.zoom(), '300%');
    assert.equal(env.elements['[data-zoom-tambah]'].disabled, true);
    for (let i = 0; i < 30; i++) env.click('[data-zoom-kurang]');
    assert.equal(env.zoom(), '50%');
    assert.equal(env.elements['[data-zoom-kurang]'].disabled, true);
    env.click('[data-zoom-reset]');
    assert.equal(env.zoom(), '100%');
    assert.equal(env.elements['[data-zoom-reset]'].disabled, true);
});

test('fullscreen dan keluar memicu penghitungan ukuran baru, zoom tetap konsisten', async () => {
    const env = harness(1600, 900);
    env.click('[data-zoom-tambah]');
    near(env.size().w, 1000);
    await env.elements['[data-pembaca-toggle]'].listeners.click[0]();
    assert.equal(env.doc.fullscreenElement, env.root);
    assert.equal(env.zoom(), '125%');
    near(env.size().w, 1500);
    near(env.size().h, 843.75);
    await env.elements['[data-pembaca-toggle]'].listeners.click[0]();
    assert.equal(env.doc.fullscreenElement, null);
    near(env.size().w, 1000);
});

test('titik tengah gambar tidak berpindah saat zoom dan drag dapat dipakai', () => {
    const env = harness(1600, 900);
    for (let i = 0; i < 8; i++) env.click('[data-zoom-tambah]');
    assert.equal(env.zoom(), '300%');
    env.area.scrollLeft = 1100;
    env.area.scrollTop = 300;
    const oldX = (env.area.scrollLeft + env.area.clientWidth / 2) / env.size().w;
    const oldY = (env.area.scrollTop + env.area.clientHeight / 2) / env.size().h;
    env.click('[data-zoom-kurang]');
    const newX = (env.area.scrollLeft + env.area.clientWidth / 2) / env.size().w;
    const newY = (env.area.scrollTop + env.area.clientHeight / 2) / env.size().h;
    near(newX, oldX);
    near(newY, oldY);
    const left = env.area.scrollLeft;
    env.area.dispatch('pointerdown', {pointerType:'mouse', button:0, pointerId:9,
        clientX:100,clientY:100});
    env.area.dispatch('pointermove', {pointerId:9,clientX:70,clientY:100});
    near(env.area.scrollLeft, left + 30);
    env.area.dispatch('pointerup', {pointerId:9});
});

test('Ctrl+wheel dan keyboard zoom; scroll biasa bukan zoom', () => {
    const env = harness(1600, 900);
    env.area.dispatch('wheel', {ctrlKey:false, deltaY:-100});
    assert.equal(env.zoom(), '100%');
    let prevented = false;
    env.area.dispatch('wheel', {ctrlKey:true, deltaY:-100,
        preventDefault(){prevented=true;}});
    assert.equal(prevented, true);
    assert.equal(env.zoom(), '125%');
    env.area.dispatch('keydown', {key:'-', preventDefault(){}});
    assert.equal(env.zoom(), '100%');
});

test('gambar gagal muat menonaktifkan zoom dan menampilkan status', () => {
    const env = harness(1600, 900);
    env.image.complete = true;
    env.image.naturalWidth = 0;
    env.image.dispatch('error');
    assert.equal(env.elements['[data-zoom-tambah]'].disabled, true);
    assert.match(env.elements['[data-pembaca-pesan]'].textContent, /gagal dimuat/i);
});
