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
test('380/360 detik tidak memalsukan 100% jika video 90% belum selesai',async()=>{
    const state=await renderStatus({
        media_selesai:false,waktu_aktif_detik:380,minimal_detik:360,
        persen_putar_minimal:90,durasi_media_detik:3600,
        pemutaran_minimal_detik:3240,waktu_putar_detik:380,
        posisi_terjauh_detik:380,
    });
    assert.ok(state.progress<100);
    assert.match(state.status,/belum terpenuhi/);
    assert.match(state.waktu,/380\/360/);
    assert.match(state.waktu,/380\/3240/);
    assert.equal(state.refresh,0);
});
test('saat server mengonfirmasi selesai form dibuka melalui reload sekali',async()=>{
    const state=await renderStatus({
        media_selesai:true,waktu_aktif_detik:3240,minimal_detik:360,
        persen_putar_minimal:90,durasi_media_detik:3600,
        pemutaran_minimal_detik:3240,waktu_putar_detik:3240,
        posisi_terjauh_detik:3240,
    });
    assert.equal(state.progress,100);
    assert.equal(state.refresh,1);
});
