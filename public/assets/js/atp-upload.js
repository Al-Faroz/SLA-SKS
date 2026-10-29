/* SLA-SKS G05: progressive enhancement only; without JS normal POST works. */
(function () {
    "use strict";

    var form = document.querySelector('[data-atp-upload-form]');
    if (!form) {
        return;
    }
    var input = form.querySelector('input[name="atp_excel"]');
    var button = form.querySelector('[data-atp-upload-submit]');
    var status = form.querySelector('[data-atp-upload-status]');
    var maxSize = 5 * 1024 * 1024;
    if (!input || !button || !status) {
        return;
    }

    function setStatus(message) {
        status.textContent = message;
    }

    function validate() {
        input.setCustomValidity('');
        var file = input.files && input.files[0];
        if (!file) {
            setStatus('Belum ada file yang dipilih.');
            return false;
        }
        if (!/\.xlsx$/i.test(file.name)) {
            input.setCustomValidity('Pilih file template ATP .xlsx, bukan .xls atau .xlsm.');
        } else if (file.size === 0 || file.size > maxSize) {
            input.setCustomValidity('Ukuran Excel harus lebih dari 0 dan maksimal 5 MB.');
        }
        if (input.validationMessage) {
            setStatus(input.validationMessage);
            return false;
        }
        setStatus('File: ' + file.name + ' (' +
            (file.size / 1024).toFixed(1) + ' KB). Siap diperiksa.');
        return true;
    }

    input.addEventListener('change', validate);
    form.addEventListener('submit', function (event) {
        if (!validate()) {
            event.preventDefault();
            input.reportValidity();
            return;
        }
        if (form.dataset.atpSending === 'true') {
            event.preventDefault();
            return;
        }
        form.dataset.atpSending = 'true';
        form.setAttribute('aria-busy', 'true');
        button.disabled = true;
        button.textContent = 'Mengunggah & memeriksa Excel…';
        setStatus('File sedang diunggah dan diperiksa. Belum ada BAB/TP yang disimpan.');
    });
    // Mengembalikan keadaan jika browser memulihkan halaman melalui back/forward cache.
    window.addEventListener('pageshow', function () {
        form.dataset.atpSending = 'false';
        form.removeAttribute('aria-busy');
        button.disabled = false;
        button.textContent = 'Periksa & Pratinjau Excel';
    });
})();
