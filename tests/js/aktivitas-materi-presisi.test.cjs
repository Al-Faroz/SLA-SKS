'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const script = fs.readFileSync(path.resolve(__dirname,
    '../../public/assets/js/aktivitas-materi.js'), 'utf8');
const viewer = fs.readFileSync(path.resolve(__dirname,
    '../../public/assets/js/pembaca-pdf-materi.js'), 'utf8');

function setupImage() {
    const sent = [];
    const events = {};
    let observe = null;
    let interval = null;
    const items = {status:{textContent:''}, waktu:{textContent:''}, bar:{value:0}};
    const image = {complete:true,naturalWidth:800,addEventListener(){}};
    const viewport = {};
    const root = {
        dataset: {mediaUrl:'/aktivitas',mediaJenis:'gambar',
            csrfName:'csrf_test',csrfHash:'token'},
        querySelector(key) {
            return ({
                '[data-media-status]':items.status,
                '[data-media-waktu]':items.waktu,
                '[data-media-bilah]':items.bar,
            })[key] || null;
        },
    };
    const document = {
        visibilityState:'visible',
        hasFocus:()=>true,
        querySelector(key) {
            return ({
                '[data-media-progres]':root,
                '[data-gambar-materi]':image,
                '[data-gambar-observer]':viewport,
            })[key] || null;
        },
        addEventListener(key,handler) {events['doc_'+key]=handler;},
    };
    const window = {
        IntersectionObserver: function(callback) {observe=callback;this.observe=()=>{};},
        addEventListener(key,handler) {events['win_'+key]=handler;},
        setInterval(callback,ms) {interval={callback,ms};},
        location:{reload(){}},
    };
    const FormData = class {
        constructor(){this.fields={};}
        append(key,value){this.fields[key]=value;}
    };
    const fetch = async (url,options={})=>{
        if (options.method === 'POST') sent.push({...options.body.fields});
        return {ok:true,json:async()=>({ok:true,csrf:'token',data:{
            media_selesai:false, waktu_aktif_detik:0,minimal_detik:30,
        }})};
    };
    vm.runInNewContext(script,{window,document,navigator:{onLine:true},
        fetch,FormData,console,Number,Math,Promise});
    const settle=()=>new Promise(resolve=>setImmediate(resolve));
    return {sent,events,interval,document,
        observer(visible) {observe([{isIntersecting:visible,
            intersectionRatio:visible?0.9:0.1}]);},
        settle};
}

test('gambar mengirim reset pada masuk viewport, lalu menghitung tiap 5 detik',async()=>{
    const x=setupImage();
    assert.equal(x.interval.ms,5000);
    await x.settle();
    x.observer(true);
    await x.settle();
    assert.equal(x.sent.length,1);
    assert.equal(x.sent[0].reset,'ya');
    await x.interval.callback();
    await x.settle();
    assert.equal(x.sent[1].reset,'tidak');
    x.observer(false);
    await x.interval.callback();
    await x.settle();
    assert.equal(x.sent.length,2); // tersembunyi tidak mengirim
    x.observer(true);
    await x.settle();
    assert.equal(x.sent[2].reset,'ya'); // tidak mengkredit jeda tersembunyi
});

test('gambar kehilangan fokus lalu kembali harus memulai sesi baru',async()=>{
    const x=setupImage();
    await x.settle();
    x.observer(true);
    await x.settle();
    x.events.win_blur();
    x.events.win_focus();
    await x.settle();
    assert.equal(x.sent.at(-1).reset,'ya');
});

test('pembaca PDF hanya reset sesi ketika nomor halaman berubah',()=>{
    assert.match(viewer,/halamanTerakhirSiap\s*!==\s*current/);
    assert.match(viewer,/pdf-halaman-siap/);
    assert.match(viewer,/detail:\s*\{halaman:\s*current\}/);
});

test('detak PDF 5 detik dan status menunjukkan rincian per halaman',()=>{
    assert.match(script,/includes\(jenis\)\s*\?\s*5000\s*:\s*10000/);
    assert.match(script,/waktu_per_halaman/);
    assert.match(script,/\/15 detik tercatat/);
});

test('pembukaan Resume tidak melewati izin server',()=>{
    assert.match(script,/data\.media_selesai\s*===\s*true/);
    assert.match(script,/window\.location\.reload\(\)/);
});


test('PDF tanpa area pembaca terlihat tidak mengirim detak, kembali terlihat reset',async()=>{
    const sent=[];
    let observer;
    let interval;
    const pdfHandlers={};
    const area={};
    const pdf={dataset:{pdfReady:'ya',pdfHalaman:'1',pdfTotal:'2'},
        addEventListener(key,handler){pdfHandlers[key]=handler;}};
    const root={
        dataset:{mediaUrl:'/aktivitas',mediaJenis:'pdf',csrfName:'csrf',csrfHash:'token'},
        querySelector(key) {
            return ({
                '[data-media-status]':{textContent:''},
                '[data-media-waktu]':{textContent:''},
                '[data-media-bilah]':{value:0},
            })[key]||null;
        },
    };
    // Reuse persistent DOM objects for assertions and progressive output.
    const items={status:{textContent:''},waktu:{textContent:''},bar:{value:0}};
    root.querySelector=key=>({
        '[data-media-status]':items.status,
        '[data-media-waktu]':items.waktu,
        '[data-media-bilah]':items.bar,
    })[key]||null;
    const document={
        visibilityState:'visible', hasFocus:()=>true,
        addEventListener(){},
        querySelector(key) {
            return ({
                '[data-media-progres]':root,
                '[data-pdf-pembaca]':pdf,
                '[data-pdf-baca-area]':area,
            })[key]||null;
        },
    };
    const window={
        IntersectionObserver:function(fn){observer=fn;this.observe=()=>{};},
        addEventListener(){},
        setInterval(fn,ms){interval={fn,ms};},
        location:{reload(){}},
    };
    const FormData=class{
        constructor(){this.fields={};}
        append(k,v){this.fields[k]=v;}
    };
    const fetch=async(url,opts={})=>{
        if(opts.method==='POST')sent.push({...opts.body.fields});
        return {ok:true,json:async()=>({ok:true,csrf:'token',data:{
            media_selesai:false,minimal_detik:60,halaman_total:2,
            halaman_selesai:0,halaman_belum_selesai:[1,2],
            waktu_per_halaman:{1:0,2:0},
        }})};
    };
    vm.runInNewContext(script,{window,document,fetch,
        navigator:{onLine:true},FormData,console,Number,Math,Promise});
    const settle=()=>new Promise(resolve=>setImmediate(resolve));
    await settle();
    assert.equal(interval.ms,5000);
    await interval.fn();
    await settle();
    assert.equal(sent.length,0);
    observer([{isIntersecting:true,intersectionRatio:0.85}]);
    await settle();
    assert.equal(sent.length,1);
    assert.equal(sent[0].reset,'ya');
    assert.equal(sent[0].halaman,'1');
    await interval.fn();
    await settle();
    assert.equal(sent[1].reset,'tidak');
    observer([{isIntersecting:false,intersectionRatio:0.1}]);
    await interval.fn();
    await settle();
    assert.equal(sent.length,2);
    observer([{isIntersecting:true,intersectionRatio:0.9}]);
    await settle();
    assert.equal(sent[2].reset,'ya');
    pdf.dataset.pdfHalaman='2';
    pdfHandlers['pdf-halaman-siap']();
    await settle();
    assert.equal(sent[3].reset,'ya');
    assert.equal(sent[3].halaman,'2');
});
