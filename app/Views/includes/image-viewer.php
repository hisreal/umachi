<?php

declare(strict_types=1);
?>
<div class="modal fade image-viewer-modal" id="imageViewerModal" tabindex="-1" aria-labelledby="imageViewerTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="imageViewerTitle"><i class="fa-solid fa-image me-2"></i>Image Preview</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body"><div class="image-viewer-stage" data-image-viewer-stage><span class="spinner-border text-light" data-image-viewer-loading aria-hidden="true"></span><img data-image-viewer-image alt="Uploaded image preview" draggable="false"></div></div>
        <div class="modal-footer image-viewer-controls">
            <button class="btn btn-light" type="button" data-image-viewer-action="zoom-out" title="Zoom out"><i class="fa-solid fa-magnifying-glass-minus"></i><span>Zoom Out</span></button>
            <button class="btn btn-light" type="button" data-image-viewer-action="zoom-in" title="Zoom in"><i class="fa-solid fa-magnifying-glass-plus"></i><span>Zoom In</span></button>
            <button class="btn btn-light" type="button" data-image-viewer-action="rotate" title="Rotate image"><i class="fa-solid fa-rotate-right"></i><span>Rotate</span></button>
            <button class="btn btn-light" type="button" data-image-viewer-action="reset" title="Reset image"><i class="fa-solid fa-arrows-rotate"></i><span>Reset</span></button>
            <button class="btn btn-light" type="button" data-image-viewer-action="fullscreen" title="View full screen"><i class="fa-solid fa-expand"></i><span>Full Screen</span></button>
            <a class="btn btn-primary" href="#" data-image-viewer-download download><i class="fa-solid fa-download"></i><span>Download</span></a>
        </div>
    </div></div>
</div>
