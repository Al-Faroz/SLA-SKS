/* G05: enhance only; server independently enforces confirmation, token and transaction. */
(function () {
    "use strict";
    var form = document.querySelector('[data-atp-confirm-form]');
    if (!form) {
        return;
    }
    var button = form.querySelector('[data-atp-confirm-submit]');
    var status = form.querySelector('[data-atp-confirm-status]');
    if (!button || !status) {
        return;
    }
    form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
            return;
        }
        if (form.dataset.sending === 'true') {
            event.preventDefault();
            return;
        }
        form.dataset.sending = 'true';
        form.setAttribute('aria-busy', 'true');
        button.disabled = true;
        button.textContent = 'Menyimpan BAB dan TP…';
        status.textContent = 'Konfirmasi sedang diperiksa ulang di server. ' +
            'Perubahan hanya disimpan jika seluruh semester sasaran valid.';
    });
    window.addEventListener('pageshow', function () {
        form.dataset.sending = 'false';
        form.removeAttribute('aria-busy');
        button.disabled = false;
        button.textContent = 'Konfirmasi dan Simpan BAB / TP';
    });
})();
