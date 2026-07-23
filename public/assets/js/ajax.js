(function (window, document) {
    'use strict';

    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const normalizeErrors = (errors) => errors && typeof errors === 'object' ? errors : {};

    class AjaxError extends Error {
        constructor(message, response, payload) {
            super(message);
            this.name = 'AjaxError';
            this.status = response?.status || 0;
            this.payload = payload || {};
            this.errors = normalizeErrors(payload?.errors);
        }
    }

    const notify = (type, message, title) => {
        const heading = title || (type === 'success' ? 'Success' : 'Unable to complete request');
        if (window.Swal) {
            return window.Swal.fire({ icon: type, title: heading, text: message, confirmButtonColor: '#f68b34' });
        }
        window.alert(`${heading}\n${message}`);
        return Promise.resolve();
    };

    const loading = {
        start(button, label) {
            if (!button || button.dataset.ajaxLoading === 'true') return;
            button.dataset.ajaxLoading = 'true';
            button.dataset.ajaxOriginalHtml = button.innerHTML;
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.innerHTML = `<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>${label || 'Please wait...'}`;
        },
        stop(button) {
            if (!button || button.dataset.ajaxLoading !== 'true') return;
            button.innerHTML = button.dataset.ajaxOriginalHtml || button.innerHTML;
            button.disabled = false;
            button.removeAttribute('aria-busy');
            delete button.dataset.ajaxLoading;
            delete button.dataset.ajaxOriginalHtml;
        }
    };

    const validation = {
        clear(form) {
            if (!form) return;
            form.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
            form.querySelectorAll('[data-ajax-validation-error]').forEach((node) => node.remove());
        },
        render(form, errors) {
            this.clear(form);
            Object.entries(normalizeErrors(errors)).forEach(([name, messages]) => {
                const field = form?.elements?.namedItem(name);
                if (!field) return;
                const target = field instanceof RadioNodeList ? field[0] : field;
                const message = Array.isArray(messages) ? messages[0] : messages;
                target.classList.add('is-invalid');
                let feedback = target.parentElement?.querySelector('.invalid-feedback');
                if (!feedback) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.dataset.ajaxValidationError = 'true';
                    target.insertAdjacentElement('afterend', feedback);
                }
                feedback.textContent = String(message || 'This value is invalid.');
            });
            form?.querySelector('.is-invalid')?.focus();
        }
    };

    const uploadProgress = (form) => {
        let wrapper = form.nextElementSibling?.matches('[data-global-upload-progress]') ? form.nextElementSibling : null;
        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'mt-3';
            wrapper.dataset.globalUploadProgress = 'true';
            wrapper.innerHTML = '<div class="d-flex justify-content-between small mb-1"><span data-upload-status>Preparing upload...</span><strong data-upload-percentage>0%</strong></div><div class="progress" role="progressbar" aria-label="Image upload progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%"></div></div>';
            form.insertAdjacentElement('afterend', wrapper);
        }
        const bar = wrapper.querySelector('.progress-bar');
        const percentage = wrapper.querySelector('[data-upload-percentage]');
        const status = wrapper.querySelector('[data-upload-status]');
        wrapper.hidden = false;
        wrapper.classList.remove('text-danger', 'text-success');
        bar.classList.remove('bg-danger', 'bg-success');
        bar.classList.add('progress-bar-striped', 'progress-bar-animated');
        const reporter = (percent) => {
            const value = Math.max(0, Math.min(100, Number(percent) || 0));
            wrapper.querySelector('.progress').setAttribute('aria-valuenow', String(value));
            bar.style.width = `${value}%`;
            percentage.textContent = `${value}%`;
            status.textContent = value >= 100 ? 'Upload complete' : 'Uploading image...';
        };
        reporter.success = () => {
            reporter(100);
            bar.classList.remove('progress-bar-striped', 'progress-bar-animated');
            bar.classList.add('bg-success');
            wrapper.classList.add('text-success');
            status.textContent = 'Upload successful';
        };
        reporter.failure = () => {
            bar.classList.remove('progress-bar-striped', 'progress-bar-animated');
            bar.classList.add('bg-danger');
            wrapper.classList.add('text-danger');
            status.textContent = 'Upload failed';
        };
        reporter(0);
        return reporter;
    };

    async function request(url, options = {}) {
        const headers = new Headers(options.headers || {});
        headers.set('Accept', 'application/json');
        headers.set('X-Requested-With', 'XMLHttpRequest');
        if (csrfToken()) headers.set('X-CSRF-TOKEN', csrfToken());

        let body = options.body;
        if (body && !(body instanceof FormData) && typeof body === 'object') {
            headers.set('Content-Type', 'application/json');
            body = JSON.stringify(body);
        }

        let response;
        try {
            response = await fetch(url, { ...options, body, headers, credentials: 'same-origin' });
        } catch (error) {
            throw new AjaxError('Network connection failed. Please check your connection and try again.', null, {});
        }

        const contentType = response.headers.get('content-type') || '';
        const payload = contentType.includes('application/json') ? await response.json() : null;
        if (!response.ok || !payload || payload.success !== true) {
            throw new AjaxError(payload?.message || `Request failed (${response.status}).`, response, payload || {});
        }
        return payload;
    }

    function upload(url, formData, options = {}) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open(options.method || 'POST', url, true);
            xhr.withCredentials = true;
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            if (csrfToken()) xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken());

            xhr.upload.addEventListener('progress', (event) => {
                if (event.lengthComputable && typeof options.onProgress === 'function') {
                    options.onProgress(Math.round((event.loaded / event.total) * 100), event);
                }
            });
            xhr.addEventListener('load', () => {
                let payload = null;
                try { payload = JSON.parse(xhr.responseText); } catch (error) { payload = null; }
                const response = { status: xhr.status };
                if (xhr.status < 200 || xhr.status >= 300 || !payload || payload.success !== true) {
                    reject(new AjaxError(payload?.message || `Request failed (${xhr.status}).`, response, payload || {}));
                    return;
                }
                if (typeof options.onProgress === 'function') options.onProgress(100);
                resolve(payload);
            });
            xhr.addEventListener('error', () => reject(new AjaxError('Network connection failed. Please check your connection and try again.', null, {})));
            xhr.addEventListener('abort', () => reject(new AjaxError('Upload was cancelled.', null, {})));
            if (options.signal) options.signal.addEventListener('abort', () => xhr.abort(), { once: true });
            xhr.send(formData);
        });
    }

    async function refresh(selectors, url = window.location.href) {
        const list = Array.isArray(selectors) ? selectors : String(selectors || '').split(',').filter(Boolean);
        if (!list.length) return;
        const response = await fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!response.ok) throw new AjaxError('The updated page section could not be loaded.', response, {});
        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
        list.forEach((selector) => {
            const current = document.querySelector(selector);
            const updated = page.querySelector(selector);
            if (current && updated) current.replaceWith(updated);
        });
        document.dispatchEvent(new CustomEvent('fuelops:refreshed', { detail: { selectors: list } }));
    }

    async function submitForm(form, options = {}) {
        const button = options.button || form.querySelector('[type="submit"]');
        validation.clear(form);
        loading.start(button, options.loadingText || form.dataset.ajaxLoadingText);
        const hasFile = Array.from(form.querySelectorAll('input[type="file"]')).some((input) => input.files?.length);
        const progress = hasFile ? uploadProgress(form) : null;
        const reportProgress = progress || options.onProgress;
        try {
            const formData = new FormData(form);
            const payload = reportProgress
                ? await upload(form.action, formData, { method: (form.method || 'POST').toUpperCase(), onProgress: (percent, event) => { reportProgress(percent, event); if (options.onProgress && options.onProgress !== reportProgress) options.onProgress(percent, event); } })
                : await request(form.action, { method: (form.method || 'POST').toUpperCase(), body: formData });
            if (options.notify !== false) await notify(payload.meta?.notification || 'success', payload.message);
            progress?.success();
            const selectors = options.refresh || form.dataset.ajaxRefresh;
            if (selectors) await refresh(selectors, options.refreshUrl);
            const redirect = payload.meta?.redirect || form.dataset.ajaxRedirect;
            if (redirect && options.redirect !== false && !selectors) window.location.assign(redirect);
            form.dispatchEvent(new CustomEvent('ajax:success', { bubbles: true, detail: payload }));
            return payload;
        } catch (error) {
            if (error instanceof AjaxError && Object.keys(error.errors).length) validation.render(form, error.errors);
            progress?.failure();
            if (options.notify !== false) await notify('error', error.message);
            form.dispatchEvent(new CustomEvent('ajax:error', { bubbles: true, detail: error }));
            throw error;
        } finally {
            loading.stop(button);
        }
    }

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form[data-ajax-form]');
        if (!form) return;
        event.preventDefault();
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }
        submitForm(form, { button: event.submitter }).catch(() => {});
    });

    window.addEventListener('unhandledrejection', (event) => {
        if (!(event.reason instanceof AjaxError)) return;
        event.preventDefault();
        notify('error', event.reason.message);
    });

    window.FuelOpsAjax = { request, upload, submitForm, refresh, notify, validation, loading, uploadProgress, AjaxError, csrfToken };
}(window, document));
