<div class="modal fade" id="imageCropperModal" tabindex="-1" aria-labelledby="imageCropperModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div><span class="eyebrow">Image Editor</span><h5 class="modal-title" id="imageCropperModalTitle">Crop Image</h5></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="image-cropper-layout">
                    <div class="image-cropper-stage"><img id="imageCropperSource" alt="Selected image to crop"></div>
                    <aside class="image-cropper-sidebar">
                        <div><span class="form-label d-block">Preview</span><div class="image-cropper-preview"></div></div>
                        <div class="image-cropper-tools" role="group" aria-label="Image crop controls">
                            <button class="btn btn-light" type="button" data-crop-action="zoom-in"><i class="fa-solid fa-magnifying-glass-plus"></i><span>Zoom In</span></button>
                            <button class="btn btn-light" type="button" data-crop-action="zoom-out"><i class="fa-solid fa-magnifying-glass-minus"></i><span>Zoom Out</span></button>
                            <button class="btn btn-light" type="button" data-crop-action="rotate-left"><i class="fa-solid fa-rotate-left"></i><span>Rotate Left</span></button>
                            <button class="btn btn-light" type="button" data-crop-action="rotate-right"><i class="fa-solid fa-rotate-right"></i><span>Rotate Right</span></button>
                            <button class="btn btn-light" type="button" data-crop-action="move-left"><i class="fa-solid fa-arrow-left"></i><span>Move Left</span></button>
                            <button class="btn btn-light" type="button" data-crop-action="move-right"><i class="fa-solid fa-arrow-right"></i><span>Move Right</span></button>
                            <button class="btn btn-light" type="button" data-crop-action="move-up"><i class="fa-solid fa-arrow-up"></i><span>Move Up</span></button>
                            <button class="btn btn-light" type="button" data-crop-action="move-down"><i class="fa-solid fa-arrow-down"></i><span>Move Down</span></button>
                            <button class="btn btn-outline-brand image-cropper-reset" type="button" data-crop-action="reset"><i class="fa-solid fa-arrows-rotate"></i><span>Reset Crop</span></button>
                        </div>
                        <p class="text-muted small mb-0" id="imageCropperRatioLabel">Crop ratio: Free</p>
                        <div class="image-compression-stats" id="imageCompressionStats" hidden aria-live="polite">
                            <div><span>Original</span><strong id="imageOriginalSize">0 KB</strong></div>
                            <i class="fa-solid fa-arrow-down"></i>
                            <div><span>Compressed</span><strong id="imageCompressedSize">0 KB</strong></div>
                            <i class="fa-solid fa-arrow-down"></i>
                            <div><span>Saved</span><strong id="imageSavedPercentage">0%</strong></div>
                        </div>
                        <div class="image-compression-progress" id="imageCompressionProgress" hidden>
                            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                            <span>Optimizing image quality...</span>
                        </div>
                    </aside>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="imageCropperApply" type="button"><i class="fa-solid fa-crop-simple"></i>Apply Crop</button>
            </div>
        </div>
    </div>
</div>
