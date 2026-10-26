'use strict';
const test=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const path=require('node:path');
const source=fs.readFileSync(path.resolve(__dirname,'../../public/assets/js/aktivitas-materi.js'),'utf8');
function renderStatus(payload) {
    const status={textContent:''},waktu={textContent:''},bilah={value:0};
    let refresh=0;
    const root={
        dataset:{mediaUrl:'/api',mediaJenis:'youtube',csrfName:'csrf',csrfHash:'tok'},
        querySelector(selector) {
            return {'[data-media-status]':status,'[data-media-waktu]':waktu,
                '[data-media-bilah]':bilah}[selector]||null;
        }
    };
    const document={
        querySelector(selector) {
            if(selector==='[data-media-progres]')return root;
            if(selector==='[data-kegiatan-tunggu-media]')return {};
            return null;
        },
        visibilityState:'visible',hasFocus:()=>true,
        getElementById:()=>null
    };
    const window={setInterval:()=>0,location:{reload:()=>{refresh++;}}};
    const context={document,window,navigator:{onLine:true},
        FormData:class{append(){}},
        fetch:async()=>({ok:true,json:async()=>({ok:true,data:payload,csrf:'tok'})}),
        console,Number,Math,Promise};
    vm.runInNewContext(source,context);
    return new Promise(resolve=>setImmediate(()=>resolve({
        status:status.textContent,waktu:waktu.textContent,progress:bilah.value,refresh
    })));
}
test('380 detik terverifikasi membuka Resume saat server mengonfirmasi selesai',async()=>{
    const state=await renderStatus({
        media_selesai:true,waktu_aktif_detik:380,minimal_detik:360,
        persen_putar_minimal:90,durasi_media_detik:3600,
        pemutaran_minimal_detik:3240,batas_efektif_detik:360,
        waktu_putar_detik:380,posisi_terjauh_detik:380,
    });
    assert.equal(state.progress,100);
    assert.match(state.waktu,/380\/360/);
    assert.match(state.waktu,/Batas yang berlaku: 360/);
    assert.equal(state.refresh,1);
});
test('saat server mengonfirmasi selesai form dibuka melalui reload sekali',async()=>{
    const state=await renderStatus({
        media_selesai:true,waktu_aktif_detik:3240,minimal_detik:360,
        persen_putar_minimal:90,durasi_media_detik:3600,
        pemutaran_minimal_detik:3240,batas_efektif_detik:360,
        waktu_putar_detik:3240,
        posisi_terjauh_detik:3240,
    });
    assert.equal(state.progress,100);
    assert.equal(state.refresh,1);
});

test('video pendek menggunakan 90% bila lebih cepat dari waktu Guru',async()=>{
    const state=await renderStatus({
        media_selesai:false,waktu_aktif_detik:200,minimal_detik:600,
        persen_putar_minimal:90,durasi_media_detik:420,
        pemutaran_minimal_detik:378,batas_efektif_detik:378,
        waktu_putar_detik:200,posisi_terjauh_detik:400,
    });
    assert.equal(state.progress,Math.floor(100*200/378));
    assert.match(state.waktu,/200\/378/);
    assert.match(state.waktu,/90% durasi media/);
    assert.equal(state.refresh,0);
});
test('video panjang menggunakan minimum Guru tanpa menunggu 90%',async()=>{
    const state=await renderStatus({
        media_selesai:false,waktu_aktif_detik:350,minimal_detik:600,
        persen_putar_minimal:90,durasi_media_detik:900,
        pemutaran_minimal_detik:810,batas_efektif_detik:600,
        waktu_putar_detik:350,posisi_terjauh_detik:900,
    });
    assert.equal(state.progress,Math.floor(100*350/600));
    assert.match(state.waktu,/350\/600/);
    assert.match(state.waktu,/waktu minimum Guru/);
    assert.equal(state.refresh,0);
});
