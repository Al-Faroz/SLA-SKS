'use strict';

/**
 * S08 Model B: hitung lokal tiap 1 dtk, checkpoint terotorisasi per 180 dtk
 * atau segera saat media mencapai syarat. Offline tidak menghapus progres
 * lokal, tetapi hanya server boleh menandai media_selesai dan membuka Resume.
 * Bukti browser tetap best-effort, bukan pengawasan fisik.
 */
(() => {
    const root = document.querySelector('[data-media-progres]');
    if (!root) return;
    const jenis = root.dataset.mediaJenis;
    const statusEl = root.querySelector('[data-media-status]');
    const waktuEl = root.querySelector('[data-media-waktu]');
    const bar = root.querySelector('[data-media-bilah]');
    const tokenName = root.dataset.csrfName;
    let csrf = root.dataset.csrfHash;
    const key = 'sla-sks-s08-v3-' + root.dataset.mediaScope;
    const checkpointMs = 180000;
    let notice = '';
    let saved = false;
    let connected = navigator.onLine !== false;
    let anchored = false;
    let started = false;
    let finished = false;
    let busy = false;
    let reloading = false;
    let yt = null;
    let ytPlaying = false;
    let imageVisible = false;
    let pdfVisible = false;
    let previous = null;
    let previousTick = null;
    let lastCheckpointAt = Date.now();
    let lastData = null;

    const blank = () => ({total:0,pages:{},pos:0,dur:0,halaman:1});
    let local = blank();

    const num = (n, lo=0, hi=10800) =>
        Number.isInteger(Number(n)) && Number(n)>=lo && Number(n)<=hi
            ? Number(n) : null;

    const store = () => {
        try {
            localStorage.setItem(key, JSON.stringify({v:1,
                total:local.total,pages:local.pages,pos:local.pos,
                dur:local.dur,halaman:local.halaman,
                anchored,checkpoint:lastCheckpointAt}));
            saved = true;
        } catch (_) {
            saved = false;
        }
    };
    const restore = () => {
        try {
            const raw = localStorage.getItem(key);
            if (!raw) return;
            const obj = JSON.parse(raw);
            if (obj.v !== 1 || num(obj.total) === null) return;
            local.total = obj.total;
            local.pos = num(obj.pos) ?? 0;
            local.dur = num(obj.dur) ?? 0;
            local.halaman = num(obj.halaman,1,40) ?? 1;
            if (obj.pages && typeof obj.pages === 'object') {
                Object.entries(obj.pages).forEach(([page,time]) => {
                    if (num(page,1,40) !== null && num(time) !== null) {
                        local.pages[page] = Number(time);
                    }
                });
            }
            anchored = obj.anchored === true;
            lastCheckpointAt = Number(obj.checkpoint) || Date.now();
            saved = true;
        } catch (_) { /* storage mungkin diblokir; jangan pernah bypass server */ }
    };
    restore();

    const visible = () => document.visibilityState === 'visible'
        && document.hasFocus();
    const image = document.querySelector('[data-gambar-materi]');
    const audio = document.querySelector('[data-audio-materi]');
    const pdf = document.querySelector('[data-pdf-pembaca]');

    const identify = () => {
        if (!visible()) return null;
        if (jenis === 'gambar') {
            return image?.complete && image.naturalWidth > 0 && imageVisible
                ? {posisi:null,durasi:null,halaman:null} : null;
        }
        if (jenis === 'pdf') {
            const page = num(pdf?.dataset.pdfHalaman,1,40);
            const count = num(pdf?.dataset.pdfTotal,1,40);
            return pdfVisible && pdf?.dataset.pdfReady === 'ya'
                && page !== null && count !== null && page <= count
                ? {posisi:null,durasi:null,halaman:page} : null;
        }
        if (jenis === 'audio') {
            if (!audio || audio.paused || audio.ended || audio.seeking
                || Math.abs(audio.playbackRate-1)>0.001
                || !Number.isFinite(audio.duration) || audio.duration<5) return null;
            return {posisi:audio.currentTime,durasi:audio.duration,halaman:null};
        }
        if (jenis === 'youtube' && yt && ytPlaying) {
            try {
                if (Math.abs(yt.getPlaybackRate()-1)>0.001) return null;
                const p=yt.getCurrentTime(), d=yt.getDuration();
                return Number.isFinite(p) && Number.isFinite(d) && d>=5
                    ? {posisi:p,durasi:d,halaman:null} : null;
            } catch (_) { return null; }
        }
        return null;
    };

    async function api(url, data=null) {
        const opts={credentials:'same-origin',cache:'no-store',
            headers:{Accept:'application/json'}};
        if (data !== null) {
            const form=new FormData();
            form.append(tokenName,csrf);
            Object.entries(data).forEach(([k,v])=>form.append(k,String(v)));
            opts.method='POST';
            opts.body=form;
        }
        const response=await fetch(url,opts);
        const body=await response.json();
        if (typeof body.csrf === 'string') csrf=body.csrf;
        if (!response.ok || !body.ok) {
            throw new Error(body.pesan || 'Server belum menyimpan aktivitas.');
        }
        return body.data;
    }

    function mergeServer(data) {
        if (!data) return;
        lastData=data;
        const serverTotal = num(data.waktu_aktif_detik);
        if (serverTotal!==null) local.total=Math.max(local.total,serverTotal);
        const pages=data.waktu_per_halaman;
        if (jenis==='pdf' && pages && typeof pages==='object') {
            Object.entries(pages).forEach(([page,seconds])=>{
                if (num(page,1,40)!==null && num(seconds)!==null) {
                    local.pages[page]=Math.max(local.pages[page]||0,Number(seconds));
                }
            });
        }
        const d=num(data.durasi_media_detik);
        if (d!==null) local.dur=d;
        if (data.media_selesai===true) finished=true;
        store();
        if (finished && !reloading
            && document.querySelector('[data-kegiatan-tunggu-media]')) {
            reloading=true;
            window.location.reload();
        }
    }

    const target = () => {
        if (jenis==='gambar')return Number(lastData?.minimal_detik)||30;
        if (jenis==='pdf')return Number(lastData?.minimal_detik)
            || (Math.max(1,Number(pdf?.dataset.pdfTotal)||1)*15);
        const fromServer=Number(lastData?.batas_efektif_detik);
        if (fromServer>0)return fromServer;
        if (local.dur>=5 && Number(lastData?.minimal_detik)>0) {
            return Math.min(Number(lastData.minimal_detik),
                Math.ceil(local.dur*0.9));
        }
        return null;
    };
    const requirementsMet = () => {
        const minimum=target();
        if (!minimum || local.total<minimum) return false;
        if (jenis==='pdf') {
            const count=num(pdf?.dataset.pdfTotal,1,40);
            if (count===null)return false;
            for (let page=1;page<=count;page++) {
                if ((local.pages[page]||0)<15)return false;
            }
        }
        return true;
    };
    const display = () => {
        const required=target();
        const persisted=Number(lastData?.waktu_aktif_detik)||0;
        const pending=Math.max(0,local.total-persisted);
        const ratio=required?Math.min(99,Math.floor(100*local.total/required)):0;
        bar.value=finished?100:ratio;
        let info='Perkiraan aktif perangkat: '+local.total+' detik.'
            +' Diakui server: '+persisted+' detik.'
            +' Belum tersinkron: '+pending+' detik.';
        if (required)info+=' Batas: '+required+' detik.';
        if (jenis==='pdf') {
            const count=Number(pdf?.dataset.pdfTotal)||0;
            const parts=[];
            for(let page=1;page<=Math.min(count,40);page++){
                parts.push(page+':'+(local.pages[page]||0)+'/15');
            }
            info+=' Halaman PDF: '+parts.join(', ')+'.';
        }
        waktuEl.textContent=info;
        statusEl.textContent=finished
            ? 'Selesai dan disahkan server. Resume dan unduhan sudah tersedia.'
            : notice || (!connected
                ? 'Offline: waktu aktif dicatat sementara di perangkat. '
                    +'Lanjutkan belajar; sinkronisasi ketika online.'
                : pending>0
                    ? 'Aktivitas dicatat di perangkat, menunggu checkpoint server.'
                    : 'Aktivitas siap dicatat. Ikuti persyaratan media.');
        if (!saved && pending>0) statusEl.textContent
            ='Penyimpanan lokal browser tidak tersedia; jangan tutup halaman sebelum tersinkron.';
    };

    let starting=false;
    async function start(sample) {
        if (anchored || starting || busy || !connected || !sample) return;
        starting=true;
        try {
            const data=await api(root.dataset.mediaStart,{
                posisi:sample.posisi===null?'':Math.floor(sample.posisi),
                durasi:sample.durasi===null?'':Math.round(sample.durasi),
                halaman:sample.halaman===null?'':sample.halaman,
            });
            anchored=true;
            started=true;
            mergeServer(data);
            notice='';
        } catch (e) {notice='Belum bisa memulai sinkronisasi: '+e.message;}
        finally {starting=false;display();}
    }

    async function sync() {
        if (busy || !anchored || !connected || finished) return;
        if (!started)started=true;
        busy=true;
        try {
            const data=await api(root.dataset.mediaCheckpoint,{
                laporan:JSON.stringify({
                    total:local.total,halaman_detik:local.pages,
                    halaman:local.halaman,posisi:Math.floor(local.pos),
                    durasi:Math.round(local.dur),
                }),
            });
            mergeServer(data);
            notice='';
            lastCheckpointAt=Date.now();
            store();
        } catch (e) {
            notice='Checkpoint belum diterima server: '+e.message
                +' Progres lokal tidak dihapus.';
        } finally {busy=false;display();}
    }

    function tick() {
        if (finished)return;
        const sample=identify();
        if (sample && !anchored) start(sample);
        const now=performance.now();
        if (sample && anchored && started && previous && previousTick!==null) {
            const dt=(now-previousTick)/1000;
            let credible=dt>=0.7 && dt<=2.3
                && sample.halaman===previous.halaman;
            if (credible && sample.posisi!==null) {
                const motion=sample.posisi-previous.posisi;
                credible=motion>=0.55 && motion<=dt+0.35;
            }
            if (credible && local.total<10800) {
                local.total++;
                if (jenis==='pdf') {
                    local.pages[sample.halaman]=Math.min(10800,
                        (local.pages[sample.halaman]||0)+1);
                    local.halaman=sample.halaman;
                }
                store();
            }
        }
        if (sample) {
            if (sample.posisi!==null) local.pos=sample.posisi;
            if (sample.durasi!==null)local.dur=sample.durasi;
            if (sample.halaman!==null)local.halaman=sample.halaman;
        }
        previous=sample;
        previousTick=now;
        if (anchored && connected &&
            (requirementsMet() || Date.now()-lastCheckpointAt>=checkpointMs)) {
            sync();
        }
        display();
    }

    // Segmen visibilitas/fokus/halaman tidak boleh mengkredit waktu tersembunyi.
    function resetSegment() {previous=null;previousTick=null;store();}
    document.addEventListener('visibilitychange',()=>{
        resetSegment();
        if (visible() && connected)sync();
    });
    window.addEventListener('blur',resetSegment);
    window.addEventListener('focus',()=>{resetSegment();if(connected)sync();});
    window.addEventListener('offline',()=>{
        connected=false;resetSegment();notice='';display();
    });
    window.addEventListener('online',()=>{
        connected=true;resetSegment();notice='';sync();
    });
    if (jenis==='gambar' || jenis==='pdf') {
        const area=jenis==='gambar'
            ? document.querySelector('[data-gambar-observer]')
            : document.querySelector('[data-pdf-baca-area]');
        if (area && 'IntersectionObserver' in window) {
            const observer=new IntersectionObserver(entries=>{
                const on=entries.some(x=>x.isIntersecting&&x.intersectionRatio>=0.5);
                if (jenis==='gambar')imageVisible=on;
                else pdfVisible=on;
                resetSegment();
            },{threshold:[0,0.5,1]});
            observer.observe(area);
        } else {
            notice='Browser tidak mendukung pemantauan area materi.';
        }
    }
    if (jenis==='youtube') {
        const frame=document.getElementById('a-siswa-video-pemutar');
        if (frame) {
            const ready=()=>{
                if(!window.YT?.Player)return;
                yt=new window.YT.Player(frame,{events:{
                    onStateChange:e=>{
                        ytPlaying=e.data===window.YT.PlayerState.PLAYING;
                        resetSegment();
                    },
                    onError:()=>{notice='Pemutar YouTube gagal.';display();}
                }});
            };
            if(window.YT?.Player)ready();
            else {
                window.onYouTubeIframeAPIReady=ready;
                const script=document.createElement('script');
                script.src='https://www.youtube.com/iframe_api';
                script.async=true;
                script.onerror=()=>{notice='API YouTube tidak tersedia.';display();};
                document.head.appendChild(script);
            }
        }
    }
    if (jenis==='audio' && audio) {
        ['play','pause','seeking','seeked','ended','ratechange'].forEach(ev=>
            audio.addEventListener(ev,resetSegment));
    }
    if (jenis==='pdf' && pdf) {
        pdf.addEventListener('pdf-halaman-siap',resetSegment);
    }
    api(root.dataset.mediaUrl).then(data=>{
        mergeServer(data);
        if (data.status==='berjalan') {
            anchored=true;started=true;store();
        }
        display();
    }).catch(e=>{
        notice='Server belum terhubung: '+e.message
            +'. Progres lokal tetap tersimpan.';display();
    });
    window.setInterval(tick,1000);
    display();
})();
