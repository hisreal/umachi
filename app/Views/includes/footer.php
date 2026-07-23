<?php

declare(strict_types=1);

require_once __DIR__ . '/view-helpers.php';

$extraScripts = $extraScripts ?? [];
?>
            </div>
        </div>
    </div>

    <?php require __DIR__ . '/image-viewer.php'; ?>
    <script src="<?php echo e(asset_url('vendor/bootstrap/js/bootstrap.bundle.min.js')); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php require __DIR__ . '/image-cropper.php'; ?>
    <script src="<?php echo e(asset_url('js/ajax.js')); ?>?v=<?php echo e((string) @filemtime(BASE_PATH . '/public/assets/js/ajax.js')); ?>"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.jsdelivr.net/npm/compressorjs@1.3.0/dist/compressor.min.js"></script>
    <script src="<?php echo e(asset_url('js/image-cropper.js')); ?>?v=<?php echo e((string) @filemtime(BASE_PATH . '/public/assets/js/image-cropper.js')); ?>"></script>
    <script src="<?php echo e(asset_url('js/image-viewer.js')); ?>?v=<?php echo e((string) @filemtime(BASE_PATH . '/public/assets/js/image-viewer.js')); ?>"></script>
    <?php foreach ($extraScripts as $scriptPath): ?>
        <?php
        $scriptUrl = str_starts_with($scriptPath, 'http') ? $scriptPath : asset_url($scriptPath);
        $scriptFile = BASE_PATH . '/public/assets/' . ltrim((string) $scriptPath, '/');

        if (!str_starts_with($scriptPath, 'http') && is_file($scriptFile)) {
            $scriptUrl .= '?v=' . filemtime($scriptFile);
        }
        ?>
        <script src="<?php echo e($scriptUrl); ?>"></script>
    <?php endforeach; ?>
</body>
</html>
