/* SLA-SKS: adaptor SunEditor v3 lokal untuk soal pilihan ganda A–E. */
(() => {
    'use strict';
    const form = document.getElementById('form-soal-guru');
    if (!form) return;
    const fields = Array.from(form.querySelectorAll('.a-rich-field'));
    const status = document.getElementById('status-gambar-soal');
    const script = document.currentScript;
    const uploadUrl = script?.dataset.uploadUrl || '';
    const editors = new Map();
    let uploadsActive = 0;

    const uploadPath = new URL(uploadUrl, location.href).pathname;
    const assetPrefix = uploadPath.replace(/\/gambar\/?$/, '/aset/');
    const trusted = (value) => {
        try {
            const url = new URL(value, location.href);
            return url.origin === location.origin && url.pathname.startsWith(assetPrefix)
                && /^[0-9a-f]{32}$/.test(url.pathname.slice(assetPrefix.length));
        } catch { return false; }
    };
    async function uploadNative(core, files) {
        const file = Array.from(files || [])[0];
        if (!file || !['image/png', 'image/jpeg'].includes(file.type) ||
            file.size > 2 * 1024 * 1024) {
            status.textContent = 'Pilih satu gambar PNG/JPEG maksimal 2 MB.';
            return;
        }
        const csrf = form.querySelector('input[type="hidden"][name]:not([name="versi"])');
        const payload = new FormData();
        payload.append('gambar', file);
        if (csrf) payload.append(csrf.name, csrf.value);
        uploadsActive++;
        status.textContent = 'Mengunggah gambar...';
        try {
            const response = await fetch(uploadUrl, {
                method: 'POST', body: payload, credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await response.json();
            if (csrf && result.csrfHash) {
                csrf.value = result.csrfHash;
                if (result.csrfName) csrf.name = result.csrfName;
            }
            const url = result.result?.[0]?.url;
            if (!response.ok || !url || !trusted(url)) {
                throw new Error(result.errorMessage || 'Gambar gagal diunggah.');
            }
            await core.plugins.image.submitURL(url);
            status.textContent = 'Gambar telah disisipkan di editor.';
        } catch (error) {
            status.textContent = error.message || 'Gambar gagal disisipkan.';
        } finally {
            uploadsActive--;
        }
    }

    if (!window.SUNEDITOR || !window.SUNEDITOR.create) {
        const message = document.createElement('p');
        message.className = 'a-alert a-alert--bahaya';
        message.setAttribute('role', 'alert');
        message.textContent = 'Editor visual belum terpasang. Area teks biasa tetap dapat dipakai. Jalankan script instalasi SunEditor lokal dalam folder proyek untuk mengaktifkan toolbar.';
        form.prepend(message);
    } else {
        fields.forEach((el) => {
            try {
                const editor = window.SUNEDITOR.create(el, {
                    plugins: window.SUNEDITOR.plugins,
                    value: el.value,
                    height: el.id === 'isi-pertanyaan' ? '250px' : '140px',
                    buttonList: [
                        ['undo', 'redo'], '|',
                        ['bold', 'italic', 'underline', 'strike'], '|',
                        ['subscript', 'superscript'], '|',
                        ['align', 'list'], '|', ['table', 'image', 'link'], '|',
                        ['removeFormat']
                    ],
                    events: {
                        onFocus: () => { active = el.id; },
                        onChange: ({ data }) => { el.value = data; }
                    }
                });
                editors.set(el.id, editor);
            } catch (error) {
                console.error('Editor soal gagal diaktifkan', error);
            }
        });
    }

    form.addEventListener('submit', () => {
        editors.forEach((instance, id) => {
            const textarea = document.getElementById(id);
            if (textarea) textarea.value = instance.$.html.get();
        });
    });

})();
