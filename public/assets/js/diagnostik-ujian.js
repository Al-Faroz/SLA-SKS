'use strict';

/* S06B: klien ujian Ketat. Timer, izin jawab dan skor SELALU otoritas server. */
(() => {
    const root = document.querySelector('[data-ujian-tp]');
    if (!root) return;
    const $ = sel => root.querySelector(sel);
    const url = root.dataset.ujianUrl;
    const tp = root.dataset.ujianTp;
    const csrfName = root.dataset.csrfName;
    let csrf = root.dataset.csrfHash;
    let token = root.dataset.ujianToken || '';
    let data = null, soal = [], jawaban = {}, index = 0, aktif = false;
    let selesai = false, exiting = false, insidenTertunda = false, memintaFinal = false;
    let rantai = Promise.resolve();

    const setText = (sel, value) => {
        const el = $(sel);
        if (el) el.textContent = String(value);
    };
    const show = (sel, yes) => { const el = $(sel); if (el) el.hidden = !yes; };
    const format = value => {
        const n = Math.max(0, Number(value) || 0);
        return Math.floor(n / 60).toString().padStart(2,'0')
            + ':' + Math.floor(n % 60).toString().padStart(2,'0');
    };
    const antre = fn => {
        const next = rantai.then(fn, fn);
        rantai = next.catch(() => {});
        return next;
    };
    const full = () => document.fullscreenElement === document.documentElement;
    const didukung = () => document.fullscreenEnabled === true
        && typeof document.documentElement.requestFullscreen === 'function';

    async function request(method, path, payload = {}) {
        const options = { method, credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }, cache: 'no-store' };
        if (method === 'POST') {
            const form = new FormData();
            form.append(csrfName, csrf);
            for (const [key,val] of Object.entries(payload)) form.append(key, String(val));
            options.body = form;
        }
        const response = await fetch(url + path, options);
        let decoded;
        try { decoded = await response.json(); }
        catch (_) { throw new Error('Koneksi atau sesi gagal. Muat ulang ruang ujian jika diperlukan.'); }
        if (typeof decoded.csrf === 'string') csrf = decoded.csrf;
        if (!response.ok || !decoded.ok) throw new Error(decoded.pesan || 'Permintaan tidak diterima server.');
        return decoded.data;
    }
    const req = (method, path, payload) => antre(() => request(method,path,payload));

    function statusLayar(state) {
        if (!state) return;
        data = state;
        setText('[data-ujian-timer]', format(state.sisa_detik));
        setText('[data-ujian-insiden]', 'Insiden fullscreen: ' + state.pelanggaran + '/3');
        if (state.status === 'selesai_sah' || state.status === 'dibatalkan_menunggu_guru') {
            selesai = true;
            aktif = false;
            show('[data-ujian-kerja]', false);
            show('[data-ujian-jeda]', false);
            show('[data-ujian-sambutan]', false);
            show('[data-ujian-hasil]', true);
            setText('[data-ujian-hasil-judul]',
                state.status === 'selesai_sah' ? 'Hasil Diagnostik' : 'Menunggu keputusan Guru');
            setText('[data-ujian-hasil-pesan]', state.status === 'selesai_sah'
                ? (state.skor >= state.kkm ? 'KKM tercapai: TP tuntas.'
                    : 'KKM belum tercapai: lanjutkan jalur Materi/Resume/HOTS ketika tersedia.')
                : 'Percobaan dibatalkan tanpa skor. Hubungi Guru pengampu.');
            setText('[data-ujian-hasil-skor]',
                state.status === 'selesai_sah' ? 'Skor: ' + state.skor
                    + ' · KKM: ' + state.kkm + ' · Benar: ' + state.jumlah_benar : '');
            if (full() && document.exitFullscreen) {
                exiting = true;
                document.exitFullscreen().catch(() => {});
            }
            return;
        }
        if (state.status === 'jeda_fullscreen' || state.status === 'jeda_koneksi') {
            aktif = false;
            show('[data-ujian-kerja]', false);
            show('[data-ujian-jeda]', true);
            setText('[data-ujian-jeda-pesan]', state.status === 'jeda_fullscreen'
                ? 'Fullscreen terputus. Waktu berhenti. Batas jeda: 90 detik per kejadian, 180 detik total.'
                : 'Koneksi terputus. Waktu dijeda oleh server setelah 20 detik deteksi; total maksimal 300 detik.');
            return;
        }
        if (state.status === 'berjalan' && full()) {
            aktif = true;
            show('[data-ujian-sambutan]', false);
            show('[data-ujian-jeda]', false);
            show('[data-ujian-hasil]', false);
            show('[data-ujian-kerja]', true);
        }
    }

    function tampilSoal() {
        if (!aktif || !soal.length) return;
        const item = soal[index];
        setText('[data-ujian-posisi]', 'Soal ' + (index+1) + ' dari ' + soal.length);
        setText('[data-ujian-nomor]', index + 1);
        const stem = $('[data-ujian-pertanyaan]');
        stem.textContent = item.pertanyaan;
        if (item.html_pertanyaan) stem.innerHTML = item.html_pertanyaan; // sanitasi dilakukan server
        const opsiRoot = $('[data-ujian-opsi]');
        opsiRoot.textContent = '';
        const legend = document.createElement('legend');
        legend.textContent = 'Pilih satu jawaban';
        opsiRoot.appendChild(legend);
        for (const huruf of ['A','B','C','D','E']) {
            const label = document.createElement('label');
            label.className = 'a-ujian-pilihan';
            const input = document.createElement('input');
            input.type = 'radio';
            input.name = 'jawab-soal-' + item.id;
            input.value = huruf;
            input.checked = jawaban[item.id] === huruf;
            const bold = document.createElement('strong');
            bold.textContent = huruf + '.';
            const teks = document.createElement('span');
            teks.textContent = item.opsi[huruf];
            if (item.html_opsi && item.html_opsi[huruf]) {
                teks.innerHTML = item.html_opsi[huruf]; // HTML dari sanitizer server
            }
            label.append(input,bold,teks);
            opsiRoot.appendChild(label);
            input.addEventListener('change', () => {
                if (!aktif || !full()) return;
                setText('[data-ujian-simpan]', 'Menyimpan jawaban…');
                input.disabled = true;
                req('POST','/' + token + '/jawab',{butir_id:item.id,pilihan:huruf})
                    .then(result => {
                        statusLayar(result);
                        if (result.tersimpan === true) {
                            jawaban[item.id] = huruf;
                            setText('[data-ujian-simpan]', 'Jawaban tersimpan di server.');
                            isiNavigator();
                        } else {
                            setText('[data-ujian-simpan]',result.pesan||'Jawaban ditolak.');
                        }
                    })
                    .catch(err => {
                        aktif = false;
                        setText('[data-ujian-simpan]', 'Belum tersimpan: ' + err.message);
                        tampilJedaLokal('Gagal menghubungi server. Jawaban belum tersimpan.');
                    })
                    .finally(() => { input.disabled = false; });
            });
        }
        $('[data-ujian-sebelum]').disabled = index === 0;
        $('[data-ujian-sesudah]').disabled = index >= soal.length - 1;
        isiNavigator();
    }
    function isiNavigator() {
        const nav = $('[data-ujian-nav]');
        nav.textContent = '';
        soal.forEach((q,i) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'a-ujian-navbutir';
            btn.textContent = String(i+1);
            btn.setAttribute('aria-label','Soal ' + (i+1) + (jawaban[q.id] ? ', terjawab' : ''));
            btn.classList.toggle('is-aktif',index===i);
            btn.classList.toggle('is-terjawab',Boolean(jawaban[q.id]));
            btn.addEventListener('click', () => {
                if (!aktif) return;
                index=i; tampilSoal();
            });
            nav.appendChild(btn);
        });
    }
    function tampilJedaLokal(text) {
        aktif=false;
        show('[data-ujian-kerja]',false);
        show('[data-ujian-jeda]',true);
        setText('[data-ujian-jeda-pesan]',text);
    }
    async function muatStatus() {
        const result = await req('GET','/' + token + '/status');
        if (Array.isArray(result.soal)) {
            soal = result.soal;
            jawaban = result.jawaban || {};
        }
        statusLayar(result);
        if (aktif) tampilSoal();
        return result;
    }
    async function gestureFullscreen() {
        if (!didukung()) throw new Error('Perangkat/browser tidak mendukung fullscreen. Gunakan desktop/laptop yang mendukungnya.');
        if (navigator.onLine === false) throw new Error('Perangkat offline. Sambungkan internet dahulu.');
        if (!full()) await document.documentElement.requestFullscreen();
        if (!full()) throw new Error('Browser menolak fullscreen.');
    }
    async function mulai() {
        const btn = $('[data-ujian-mulai]');
        btn.disabled = true;
        try {
            await gestureFullscreen();
            if (!token) {
                const info = await req('POST','/mulai',{fullscreen:'ya'});
                token = info.token;
            }
            await muatStatus();
            if (!aktif && data && data.status === 'berjalan') {
                statusLayar(data);
                tampilSoal();
            }
        } catch (err) {
            setText('[data-ujian-awal-pesan]', err.message);
            if (full() && !token) document.exitFullscreen().catch(()=>{});
        } finally { btn.disabled = false; }
    }
    async function pulih() {
        try {
            await gestureFullscreen();
            const result = await req('POST','/' + token + '/tindakan',{aksi:'pulih'});
            statusLayar(result);
            if (result.status === 'berjalan') await muatStatus();
        } catch (err) {
            tampilJedaLokal(err.message);
        }
    }
    let terakhirKeluar=0;
    function laporKeluar() {
        if (!token || selesai || !aktif || insidenTertunda) return;
        if (Date.now()-terakhirKeluar < 750) return;
        terakhirKeluar=Date.now();
        insidenTertunda=true;
        tampilJedaLokal('Fullscreen atau fokus tab terputus. Menghubungi server…');
        req('POST','/' + token + '/tindakan',{aksi:'keluar_fullscreen'})
            .then(statusLayar)
            .catch(err => tampilJedaLokal('Insiden belum terkonfirmasi. ' + err.message))
            .finally(()=>{insidenTertunda=false;});
    }
    $('[data-ujian-mulai]')?.addEventListener('click',mulai);
    $('[data-ujian-lihat-hasil]')?.addEventListener('click',async () => {
        if (!token) return;
        try { await muatStatus(); } catch (err) { setText('[data-ujian-awal-pesan]',err.message); }
    });
    $('[data-ujian-pulih]')?.addEventListener('click',pulih);
    $('[data-ujian-sebelum]')?.addEventListener('click',()=>{
        if (index>0 && aktif) {index--;tampilSoal();}
    });
    $('[data-ujian-sesudah]')?.addEventListener('click',()=>{
        if (index<soal.length-1 && aktif) {index++;tampilSoal();}
    });
    $('[data-ujian-kirim]')?.addEventListener('click',async ()=>{
        if (!aktif || !full() || memintaFinal) return;
        if (!window.confirm('Kirim semua jawaban tersimpan dan akhiri Diagnostik?')) return;
        memintaFinal = true;
        try {
            const result=await req('POST','/'+token+'/tindakan',{aksi:'kirim'});
            statusLayar(result);
        } catch(err) { setText('[data-ujian-simpan]',err.message); }
        finally { memintaFinal=false; }
    });
    document.addEventListener('fullscreenchange',()=>{
        if (!full() && !exiting) laporKeluar();
    });
    document.addEventListener('visibilitychange',()=>{
        if (document.visibilityState==='hidden') laporKeluar();
    });
    window.addEventListener('blur',()=>{
        if (!full() || document.visibilityState!=='visible') laporKeluar();
    });
    setInterval(() => {
        if (!aktif || !data || data.status!=='berjalan') return;
        if (!full()) {laporKeluar();return;}
        data.sisa_detik=Math.max(0,data.sisa_detik-1);
        setText('[data-ujian-timer]',format(data.sisa_detik));
        if (data.sisa_detik===0) {
            aktif=false;
            tampilJedaLokal('Waktu habis. Menunggu server menghitung hasil sah…');
            muatStatus().catch(err=>setText('[data-ujian-jeda-pesan]',err.message));
        }
    },1000);
    setInterval(()=>{
        if (!token || !aktif || selesai || !full() || document.visibilityState!=='visible') return;
        req('POST','/'+token+'/tindakan',{aksi:'heartbeat'})
            .then(statusLayar).catch(err => tampilJedaLokal('Koneksi server terputus: '+err.message));
    },10000);
})();
