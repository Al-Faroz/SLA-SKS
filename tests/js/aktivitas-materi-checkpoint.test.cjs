'use strict';
const test=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const vm=require('node:vm');
const src=fs.readFileSync(path.resolve(__dirname,
  '../../public/assets/js/aktivitas-materi-checkpoint.js'),'utf8');

function makeImage(minimum=30) {
  const events={};
  let observer=()=>{};
  let interval;
  let clock=0;
  let online=true;
  let reloads=0;
  const calls=[];
  const storage=new Map();
  const status={textContent:''}, waktu={textContent:''}, bar={value:0};
  const image={complete:true,naturalWidth:700};
  const root={
    dataset:{mediaJenis:'gambar',mediaUrl:'/status',
      mediaStart:'/mulai',mediaCheckpoint:'/checkpoint',
      mediaScope:'scoped-example',csrfName:'token',csrfHash:'old'},
    querySelector(selector){
      return {'[data-media-status]':status,'[data-media-waktu]':waktu,
        '[data-media-bilah]':bar}[selector]||null;
    }
  };
  const document={
    visibilityState:'visible',hasFocus:()=>true,
    querySelector(selector){
      return {'[data-media-progres]':root,'[data-gambar-materi]':image,
        '[data-gambar-observer]':{},'[data-kegiatan-tunggu-media]':{}}[selector]||null;
    },
    addEventListener(name,cb){events['d'+name]=cb;}
  };
  const window={
    addEventListener(name,cb){events[name]=cb;},
    setInterval(cb,ms){interval={cb,ms};},
    location:{reload(){reloads++;}}
  };
  function IntersectionObserver(callback){observer=callback;this.observe=()=>{};}
  window.IntersectionObserver=IntersectionObserver;
  const FormData=class{
    constructor(){this.fields={};}
    append(name,value){this.fields[name]=value;}
  };
  const localStorage={
    setItem(name,value){storage.set(name,value);},
    getItem(name){return storage.get(name)||null;},
  };
  const fetch=async(url,opts={})=>{
    if(!online)throw Error('offline');
    const fields=opts.body?.fields||{};
    calls.push({url,fields});
    let data={status:'belum_dimulai',media_selesai:false,
      waktu_aktif_detik:0,minimal_detik:minimum};
    if(url==='/mulai')data={...data,status:'berjalan'};
    if(url==='/checkpoint'){
      const report=JSON.parse(fields.laporan);
      data={...data,status:report.total>=minimum?'media_selesai':'berjalan',
        media_selesai:report.total>=minimum,waktu_aktif_detik:report.total};
    }
    return {ok:true,json:async()=>({csrf:'new',ok:true,data})};
  };
  vm.runInNewContext(src,{window,document,IntersectionObserver,
    performance:{now:()=>clock},Date:{now:()=>clock},navigator:{onLine:true},
    localStorage,FormData,fetch,Number,Math,Promise,console});
  const settle=()=>new Promise(resolve=>setImmediate(()=>setImmediate(resolve)));
  return {
    calls,events,storage,status,waktu,bar,get interval(){return interval;},
    get reloads(){return reloads;},
    observe(flag){observer([{isIntersecting:flag,intersectionRatio:flag?0.9:0}]);},
    async tick(seconds=1){
      for(let i=0;i<seconds;i++){clock+=1000;interval.cb();await settle();}
    },
    setOnline(flag){online=flag;},
    settle,
  };
}

test('checkpoint model B lokal satu detik dan unduh/form hanya sesudah server sah',async()=>{
  const x=makeImage();
  assert.equal(x.interval.ms,1000);
  await x.settle();
  x.observe(true);
  await x.tick(1); // kirim mulai
  assert.equal(x.calls.filter(c=>c.url==='/mulai').length,1);
  await x.tick(31);
  const ck=x.calls.filter(c=>c.url==='/checkpoint');
  assert.equal(ck.length,1,'checkpoint final segera sebelum 180 detik');
  assert.ok(JSON.parse(ck[0].fields.laporan).total>=30);
  assert.equal(x.reloads,1);
});

test('offline tidak menghapus progres lokal, pulih mengirim waktu kumulatif',async()=>{
  const x=makeImage();
  await x.settle();
  x.observe(true);
  await x.tick(1);
  await x.tick(10);
  x.setOnline(false);
  x.events.offline();
  await x.tick(25); // Pastikan melampaui batas media 30 detik saat offline
  assert.equal(x.calls.filter(c=>c.url==='/checkpoint').length,0);
  const local=JSON.parse(x.storage.get('sla-sks-s08-v3-scoped-example'));
  assert.ok(local.total>=25);
  x.setOnline(true);
  x.events.online();
  await x.settle();
  const ck=x.calls.filter(c=>c.url==='/checkpoint');
  assert.equal(ck.length,1);
  assert.equal(JSON.parse(ck[0].fields.laporan).total,local.total);
  assert.equal(x.reloads,1);
});

test('tab tidak terlihat tidak mengkredit durasi tambahan',async()=>{
  const x=makeImage();
  await x.settle();x.observe(true);await x.tick(1);await x.tick(8);
  const old=JSON.parse(x.storage.get('sla-sks-s08-v3-scoped-example')).total;
  x.observe(false);
  await x.tick(20);
  const now=JSON.parse(x.storage.get('sla-sks-s08-v3-scoped-example')).total;
  assert.equal(now,old);
});

test('UI menyediakan route unduh terpisah, gate Resume, dan CSRF checkpoint',()=>{
  const route=fs.readFileSync(path.resolve(__dirname,'../../app/Config/Routes.php'),'utf8');
  const view=fs.readFileSync(path.resolve(__dirname,'../../app/Views/siswa/materi.php'),'utf8');
  assert.match(route,/SiswaMateri::unduh/);
  assert.match(route,/SiswaCheckpointMateri::kirim/);
  assert.match(view,/\$kegiatan\['media_selesai'\]/);
  assert.match(view,/\/unduh/);
  assert.match(view,/data-media-scope/);
  assert.match(view,/aktivitas-materi-checkpoint\.js/);
  assert.match(src,/form\.append\(tokenName,csrf\)/);
});


test('media panjang hanya checkpoint berkala setiap 180 detik',async()=>{
  const x=makeImage(360);
  await x.settle();x.observe(true);
  await x.tick(1);
  await x.tick(170);
  assert.equal(x.calls.filter(c=>c.url==='/checkpoint').length,0);
  await x.tick(11);
  const ck=x.calls.filter(c=>c.url==='/checkpoint');
  assert.equal(ck.length,1);
  assert.ok(JSON.parse(ck[0].fields.laporan).total>=170);
  assert.equal(x.reloads,0,'belum syarat 360 sehingga Resume terkunci');
});
