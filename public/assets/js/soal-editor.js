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
