(() => {
    'use strict';

    const modalElement = document.getElementById('imageCropperModal');
    const sourceImage = document.getElementById('imageCropperSource');
    const applyButton = document.getElementById('imageCropperApply');
    const ratioLabel = document.getElementById('imageCropperRatioLabel');
    const stats = document.getElementById('imageCompressionStats');
    const progress = document.getElementById('imageCompressionProgress');
    const originalSizeLabel = document.getElementById('imageOriginalSize');
    const compressedSizeLabel = document.getElementById('imageCompressedSize');
    const savedLabel = document.getElementById('imageSavedPercentage');
    if (!modalElement || !sourceImage || !applyButton || !window.Cropper || !window.Compressor || !window.bootstrap) return;

    const compressionProfiles = Object.freeze({
        profile: { maxWidth: 600, quality: 0.82, maxBytes: 200 * 1024, minQuality: 0.62, minWidth: 400 },
        passport: { maxWidth: 600, quality: 0.82, maxBytes: 200 * 1024, minQuality: 0.62, minWidth: 400 },
        selfie: { maxWidth: 900, quality: 0.80, maxBytes: 300 * 1024, minQuality: 0.60, minWidth: 600 },
        'opening-meter': { maxWidth: 1400, quality: 0.85, maxBytes: 200 * 1024, minQuality: 0.72, minWidth: 900 },
        'closing-meter': { maxWidth: 1400, quality: 0.85, maxBytes: 200 * 1024, minQuality: 0.72, minWidth: 900 },
        'national-id': { maxWidth: 1200, quality: 0.85, maxBytes: 300 * 1024, minQuality: 0.68, minWidth: 700 },
        'drivers-license': { maxWidth: 1200, quality: 0.85, maxBytes: 400 * 1024, minQuality: 0.68, minWidth: 700 },
        document: { maxWidth: 1200, quality: 0.82, maxBytes: 400 * 1024, minQuality: 0.62, minWidth: 600 },
    });

    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const bypass = new WeakSet();
    let cropper = null;
    let activeInput = null;
    let originalFile = null;
    let preparedFile = null;
    let objectUrl = '';
    let finalized = false;

    const notify = (title, message) => {
        if (window.Swal) window.Swal.fire({ icon: 'warning', title, text: message, confirmButtonColor: '#F68B34' });
        else window.alert(`${title}\n${message}`);
    };

    const formatBytes = (bytes) => {
        if (bytes >= 1024 * 1024) return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
        return `${Math.max(1, Math.round(bytes / 1024))} KB`;
    };

    const ratioFor = (input) => {
        let ratio = String(input.dataset.cropRatio || 'free').toLowerCase();
        const sourceSelector = input.dataset.cropRatioSource;
        if (sourceSelector) {
            const value = String(document.querySelector(sourceSelector)?.value || '').toLowerCase();
            ratio = value.includes('passport') || value.includes('profile') ? '1:1' : 'free';
        }
        if (ratio === '1:1') return { value: 1, label: '1:1' };
        if (ratio === '4:3') return { value: 4 / 3, label: '4:3' };
        return { value: Number.NaN, label: 'Free' };
    };

    const compressionProfileFor = (input) => {
        let type = String(input.dataset.compressType || 'document').toLowerCase();
        const sourceSelector = input.dataset.compressTypeSource;
        if (sourceSelector) {
            const value = String(document.querySelector(sourceSelector)?.value || '').toLowerCase();
            if (value.includes('passport')) type = 'passport';
            else if (value.includes('national')) type = 'national-id';
            else if (value.includes('driver')) type = 'drivers-license';
            else type = 'document';
        }
        return compressionProfiles[type] || compressionProfiles.document;
    };

    const clearObjectUrl = () => {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = '';
    };

    const destroyCropper = () => {
        cropper?.destroy();
        cropper = null;
        clearObjectUrl();
        sourceImage.removeAttribute('src');
    };

    const resetCompressionPreview = () => {
        preparedFile = null;
        stats.hidden = true;
        progress.hidden = true;
        applyButton.innerHTML = '<i class="fa-solid fa-crop-simple"></i>Apply Crop';
    };

    const canvasToBlob = (canvas, quality) => new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));

    const compressOnce = (file, options) => new Promise((resolve, reject) => {
        new Compressor(file, {
            strict: false,
            checkOrientation: false,
            retainExif: false,
            mimeType: 'image/jpeg',
            convertSize: 0,
            maxWidth: options.maxWidth,
            maxHeight: options.maxWidth,
            quality: options.quality,
            success: resolve,
            error: reject,
        });
    });

    const compressToProfile = async (file, profile) => {
        let quality = profile.quality;
        let maxWidth = profile.maxWidth;
        for (let attempt = 0; attempt < 14; attempt += 1) {
            const result = await compressOnce(file, { quality, maxWidth });
            if (result.size <= profile.maxBytes) return result;
            if (quality > profile.minQuality) quality = Math.max(profile.minQuality, quality - 0.04);
            else maxWidth = Math.max(profile.minWidth, Math.round(maxWidth * 0.88));
        }
        throw new Error(`The image could not be compressed below ${formatBytes(profile.maxBytes)} while preserving acceptable quality.`);
    };

    const updateConfiguredPreview = (input, file) => {
        const selector = input.dataset.imagePreview;
        let preview = selector ? document.querySelector(selector) : input.parentElement?.querySelector('[data-image-upload-preview]');
        if (!preview) {
            const wrapper = document.createElement('div');
            wrapper.className = 'image-upload-preview mt-2';
            wrapper.innerHTML = '<img class="img-thumbnail" data-image-upload-preview alt="Compressed image preview">';
            input.insertAdjacentElement('afterend', wrapper);
            preview = wrapper.querySelector('[data-image-upload-preview]');
        }
        const previewUrl = URL.createObjectURL(file);
        preview.src = previewUrl;
        preview.addEventListener('load', () => URL.revokeObjectURL(previewUrl), { once: true });
    };

    const showCompressionStats = (originalBytes, compressedBytes) => {
        const saved = originalBytes > 0 ? Math.max(0, Math.round((1 - (compressedBytes / originalBytes)) * 100)) : 0;
        originalSizeLabel.textContent = formatBytes(originalBytes);
        compressedSizeLabel.textContent = formatBytes(compressedBytes);
        savedLabel.textContent = `${saved}%`;
        stats.hidden = false;
    };

    const openCropper = (input, file) => {
        activeInput = input;
        originalFile = file;
        finalized = false;
        resetCompressionPreview();
        const ratio = ratioFor(input);
        ratioLabel.textContent = `Crop ratio: ${ratio.label}`;
        modalElement.addEventListener('shown.bs.modal', () => {
            destroyCropper();
            objectUrl = URL.createObjectURL(file);
            sourceImage.src = objectUrl;
            cropper = new Cropper(sourceImage, {
                aspectRatio: ratio.value,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.9,
                responsive: true,
                restore: false,
                movable: true,
                rotatable: true,
                scalable: true,
                zoomable: true,
                zoomOnTouch: true,
                zoomOnWheel: true,
                cropBoxMovable: true,
                cropBoxResizable: true,
                preview: '.image-cropper-preview',
            });
        }, { once: true });
        modal.show();
    };

    document.addEventListener('change', (event) => {
        const input = event.target.closest?.('input[type="file"][data-image-crop]');
        if (!input) return;
        if (bypass.has(input)) {
            bypass.delete(input);
            return;
        }
        const file = input.files?.[0];
        if (!file || !file.type.startsWith('image/')) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            input.value = '';
            notify('Unsupported Image', 'Choose a JPG, JPEG, PNG, or WEBP image.');
            return;
        }
        event.stopImmediatePropagation();
        openCropper(input, file);
    }, true);

    modalElement.addEventListener('hidden.bs.modal', () => {
        destroyCropper();
        if (!finalized && activeInput) activeInput.value = '';
        activeInput = null;
        originalFile = null;
        preparedFile = null;
    });

    modalElement.addEventListener('click', (event) => {
        const action = event.target.closest('[data-crop-action]')?.dataset.cropAction;
        if (!cropper || !action) return;
        resetCompressionPreview();
        const actions = {
            'zoom-in': () => cropper.zoom(0.1),
            'zoom-out': () => cropper.zoom(-0.1),
            'rotate-left': () => cropper.rotate(-90),
            'rotate-right': () => cropper.rotate(90),
            'move-left': () => cropper.move(-10, 0),
            'move-right': () => cropper.move(10, 0),
            'move-up': () => cropper.move(0, -10),
            'move-down': () => cropper.move(0, 10),
            reset: () => cropper.reset(),
        };
        actions[action]?.();
    });

    const finalizePreparedFile = () => {
        const transfer = new DataTransfer();
        transfer.items.add(preparedFile);
        activeInput.files = transfer.files;
        updateConfiguredPreview(activeInput, preparedFile);
        finalized = true;
        bypass.add(activeInput);
        activeInput.dispatchEvent(new Event('change', { bubbles: true }));
        activeInput.dispatchEvent(new CustomEvent('imagecrop:complete', { bubbles: true, detail: { file: preparedFile } }));
        modal.hide();
    };

    applyButton.addEventListener('click', async () => {
        if (!cropper || !activeInput || !originalFile) return;
        if (preparedFile) {
            finalizePreparedFile();
            return;
        }
        applyButton.disabled = true;
        progress.hidden = false;
        stats.hidden = true;
        try {
            const profile = compressionProfileFor(activeInput);
            const canvas = cropper.getCroppedCanvas({ maxWidth: 2400, maxHeight: 2400, fillColor: '#fff', imageSmoothingEnabled: true, imageSmoothingQuality: 'high' });
            if (!canvas) throw new Error('Unable to create the cropped image.');
            const croppedBlob = await canvasToBlob(canvas, 0.98);
            if (!croppedBlob) throw new Error('Unable to prepare the cropped image.');
            const compressedBlob = await compressToProfile(croppedBlob, profile);
            const originalName = originalFile.name?.replace(/\.[^.]+$/, '') || 'compressed-image';
            preparedFile = new File([compressedBlob], `${originalName}.jpg`, { type: 'image/jpeg', lastModified: Date.now() });
            showCompressionStats(originalFile.size, preparedFile.size);
            applyButton.innerHTML = '<i class="fa-solid fa-check"></i>Use Compressed Image';
        } catch (error) {
            notify('Compression Failed', error.message || 'The image could not be compressed.');
        } finally {
            progress.hidden = true;
            applyButton.disabled = false;
        }
    });

    window.FuelOpsImageCropper = Object.freeze({ version: '2.0.0', compressionProfiles });
    document.addEventListener('submit', async (event) => {
        const form = event.target.closest?.('form[data-image-ajax-form]');
        if (!form || event.defaultPrevented) return;
        event.preventDefault();
        form.classList.add('was-validated');
        if (!form.checkValidity()) return;
        if (!window.FuelOpsAjax) {
            notify('Upload Failed', 'The AJAX upload service is unavailable. Please try again.');
            return;
        }
        try {
            await window.FuelOpsAjax.submitForm(form, {
                button: event.submitter || form.querySelector('[type="submit"]'),
                redirect: false,
                loadingText: 'Uploading image...'
            });
        } catch (error) {
            // The shared helper preserves the selected file and all entered values.
        }
    });
})();
