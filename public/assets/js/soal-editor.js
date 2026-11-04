/* SLA-SKS: adaptor SunEditor v3 lokal untuk soal pilihan ganda A–E. */
(() => {
    'use strict';
    const form = document.getElementById('form-soal-guru');
    if (!form) return;
    const fields = Array.from(form.querySelectorAll('.a-rich-field'));
    const status = document.getElementById('status-gambar-soal');
    const script = document.currentScript;
    const uploadUrl = script?.dataset.uploadUrl || '';
    const previewUrl = script?.dataset.previewUrl || '';
    const csrfInput = () => form.querySelector('input[type="hidden"][name]:not([name="versi"]):not([name="jenis"]):not([name="soal_id"])');
    const syncEditors = () => {
        syncEditors();
    };
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
        const csrf = csrfInput();
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
                    image: {
                        createFileInput: true,
                        createUrlInput: false,
                        acceptedFormats: 'image/png,image/jpeg',
                        uploadSingleSizeLimit: 2 * 1024 * 1024,
                        allowMultiple: false,
                        canResize: true
                    },
                    events: {
                        onChange: ({ data }) => {
                            el.value = data;
                            const currentPreview = document.getElementById('hasil-pratinjau-soal');
                            if (currentPreview) currentPreview.hidden = true;
                        },
                        onImageUploadBefore: ({ $, info }) => {
                            if (info.url) {
                                if (!trusted(info.url)) {
                                    status.textContent = 'Gunakan gambar yang diunggah ke SLA-SKS.';
                                    return false;
                                }
                                return true;
                            }
                            if (uploadsActive > 0) {
                                status.textContent = 'Selesaikan unggahan sebelumnya terlebih dahulu.';
                                return false;
                            }
                            void uploadNative($, info.files);
                            return false;
                        }
                    }
                });
                editors.set(el.id, editor);
            } catch (error) {
                console.error('Editor soal gagal diaktifkan', error);
            }
        });
    }

    form.addEventListener('submit', (event) => {
        if (uploadsActive > 0) {
            event.preventDefault();
            status.textContent = 'Gambar masih diunggah. Simpan setelah gambar selesai disisipkan.';
            return;
        }
        editors.forEach((instance, id) => {
            const textarea = document.getElementById(id);
            if (textarea) textarea.value = instance.$.html.get();
        });
    });

    // HTML pratinjau datang dari sanitizer server yang sama dengan simpan soal.
    const previewButton = document.getElementById('pratinjau-soal');
    const previewPanel = document.getElementById('hasil-pratinjau-soal');
    const previewContents = document.getElementById('pratinjau-soal-isi');
    if (previewButton && previewPanel && previewContents && previewUrl) {
        previewButton.addEventListener('click', async () => {
            if (uploadsActive > 0) {
                status.textContent = 'Selesaikan unggahan gambar sebelum pratinjau.';
                return;
            }
            syncEditors();
            previewButton.disabled = true;
            previewButton.textContent = 'Memeriksa soal...';
            previewPanel.hidden = true;
            status.textContent = 'Menyiapkan pratinjau...';
            try {
                const response = await fetch(previewUrl, {
                    method: 'POST', body: new FormData(form), credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json();
                const csrf = csrfInput();
                if (csrf && result.csrfHash) {
                    csrf.value = result.csrfHash;
                    if (result.csrfName) csrf.name = result.csrfName;
                }
                if (!response.ok || !result.pertanyaan || !result.opsi) {
                    throw new Error(result.errorMessage || 'Periksa isi soal dan koneksi.');
                }
                previewContents.replaceChildren();
                const question = document.createElement('div');
                question.className = 'a-word-preview';
                question.innerHTML = result.pertanyaan;
                previewContents.appendChild(question);

                const tableContainer = document.createElement('div');
                tableContainer.className = 'a-tabel-gulir a-jarak-atas';
                const table = document.createElement('table');
                table.className = 'a-tbl';
                const tbody = document.createElement('tbody');
                ['A', 'B', 'C', 'D', 'E'].forEach((letter) => {
                    const tr = document.createElement('tr');
                    const th = document.createElement('th');
                    th.scope = 'row';
                    th.textContent = letter;
                    const td = document.createElement('td');
                    const content = document.createElement('div');
                    content.className = 'a-word-preview';
                    content.innerHTML = result.opsi[letter] || '';
                    td.appendChild(content);
                    const answer = document.createElement('td');
                    answer.textContent = result.kunci === letter ? '✓ Jawaban benar' : '—';
                    tr.append(th, td, answer);
                    tbody.appendChild(tr);
                });
                table.appendChild(tbody);
                tableContainer.appendChild(table);
                previewContents.appendChild(tableContainer);
                previewPanel.hidden = false;
                status.textContent = 'Pratinjau siap. Periksa lalu klik Simpan Soal jika sudah benar.';
                previewPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } catch (error) {
                status.textContent = error.message || 'Pratinjau tidak dapat ditampilkan.';
            } finally {
                previewButton.disabled = false;
                previewButton.textContent = 'Pratinjau Soal';
            }
        });
        form.addEventListener('input', () => {
            if (!previewPanel.hidden) previewPanel.hidden = true;
        });
    }

})();
