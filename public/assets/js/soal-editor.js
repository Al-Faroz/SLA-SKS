/* SLA-SKS: adaptor SunEditor v3 lokal untuk soal pilihan ganda A–E. */
(() => {
    'use strict';
    const form = document.getElementById('form-soal-guru');
    if (!form) return;
    const fields = Array.from(form.querySelectorAll('.a-rich-field'));
    const status = document.getElementById('status-gambar-soal');
    const uploadBtn = document.getElementById('unggah-gambar-soal');
    const fileInput = document.getElementById('gambar-editor');
    const script = document.currentScript;
    const uploadUrl = script?.dataset.uploadUrl || '';
    const editors = new Map();
    let active = fields[0]?.id;

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
                        ['align', 'list'], '|', ['table', 'link'], '|',
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

    uploadBtn?.addEventListener('click', async () => {
        const gambar = fileInput?.files?.[0];
        if (!gambar) {
            status.textContent = 'Pilih gambar terlebih dahulu.';
            return;
        }
        const editor = editors.get(active);
        if (!editor) {
            status.textContent = 'Aktifkan SunEditor dan klik area soal/pilihan sebelum menyisipkan gambar.';
            return;
        }
        const csrf = form.querySelector('input[type="hidden"][name]:not([name="versi"])');
        const data = new FormData();
        data.append('gambar', gambar);
        if (csrf) data.append(csrf.name, csrf.value);
        uploadBtn.disabled = true;
        status.textContent = 'Mengunggah gambar...';
        try {
            const response = await fetch(uploadUrl, {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            });
            const result = await response.json();
            if (csrf && result.csrfHash) {
                csrf.value = result.csrfHash;
                if (result.csrfName) csrf.name = result.csrfName;
            }
            if (!response.ok || !result.result?.[0]?.url) {
                throw new Error(result.errorMessage || 'Unggahan gagal.');
            }
            const src = result.result[0].url.replaceAll('&', '&amp;').replaceAll('"', '&quot;');
            editor.$.html.insert('<img src="' + src + '" alt="Ilustrasi soal">');
            status.textContent = 'Gambar berhasil disisipkan. Simpan soal setelah selesai.';
            fileInput.value = '';
        } catch (error) {
            status.textContent = error.message || 'Gambar belum berhasil disisipkan.';
        } finally {
            uploadBtn.disabled = false;
        }
    });
})();
