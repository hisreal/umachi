(() => {
    'use strict';
    const element = document.getElementById('imageViewerModal');
    const image = element?.querySelector('[data-image-viewer-image]');
    const stage = element?.querySelector('[data-image-viewer-stage]');
    const title = document.getElementById('imageViewerTitle');
    const download = element?.querySelector('[data-image-viewer-download]');
    if (!element || !image || !stage || !download || !window.bootstrap) return;
    const modal = window.bootstrap.Modal.getOrCreateInstance(element);
    let scale = 1, rotation = 0, offsetX = 0, offsetY = 0, dragging = false, startX = 0, startY = 0;
    const render = () => { image.style.transform = `translate(${offsetX}px, ${offsetY}px) scale(${scale}) rotate(${rotation}deg)`; };
    const reset = () => { scale = 1; rotation = 0; offsetX = 0; offsetY = 0; render(); };
    const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character]));
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-image-view]');
        if (!trigger) return;
        event.preventDefault();
        const url = trigger.dataset.imageView || trigger.getAttribute('href');
        if (!url) return;
        reset(); stage.classList.remove('is-ready'); image.src = url;
        image.alt = trigger.dataset.imageAlt || trigger.dataset.imageTitle || 'Uploaded image preview';
        title.innerHTML = `<i class="fa-solid fa-image me-2"></i>${escapeHtml(trigger.dataset.imageTitle || 'Image Preview')}`;
        download.href = url; download.download = trigger.dataset.downloadName || 'uploaded-image'; modal.show();
    });
    image.addEventListener('load', () => stage.classList.add('is-ready'));
    image.addEventListener('error', () => { stage.classList.remove('is-ready'); window.FuelOpsAjax?.notify?.('Image Unavailable', 'The image could not be loaded.', 'error'); modal.hide(); });
    element.addEventListener('click', (event) => {
        const action = event.target.closest('[data-image-viewer-action]')?.dataset.imageViewerAction;
        if (!action) return;
        if (action === 'zoom-in') scale = Math.min(5, scale + .25);
        if (action === 'zoom-out') scale = Math.max(.25, scale - .25);
        if (action === 'rotate') rotation = (rotation + 90) % 360;
        if (action === 'reset') reset();
        if (action === 'fullscreen') { if (!document.fullscreenElement) element.requestFullscreen?.(); else document.exitFullscreen?.(); }
        render();
    });
    stage.addEventListener('wheel', (event) => { event.preventDefault(); scale = Math.max(.25, Math.min(5, scale + (event.deltaY < 0 ? .15 : -.15))); render(); }, { passive: false });
    stage.addEventListener('pointerdown', (event) => { dragging = true; startX = event.clientX - offsetX; startY = event.clientY - offsetY; stage.setPointerCapture?.(event.pointerId); });
    stage.addEventListener('pointermove', (event) => { if (!dragging) return; offsetX = event.clientX - startX; offsetY = event.clientY - startY; render(); });
    stage.addEventListener('pointerup', () => { dragging = false; }); stage.addEventListener('pointercancel', () => { dragging = false; });
    element.addEventListener('hidden.bs.modal', () => { image.removeAttribute('src'); stage.classList.remove('is-ready'); reset(); if (document.fullscreenElement === element) document.exitFullscreen?.(); });
})();
