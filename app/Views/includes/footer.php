<?php

declare(strict_types=1);

require_once __DIR__ . '/view-helpers.php';

$extraScripts = $extraScripts ?? [];
?>
            </div>
        </div>
    </div>

    <script src="<?php echo e(asset_url('vendor/bootstrap/js/bootstrap.bundle.min.js')); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo e(asset_url('js/ajax.js')); ?>?v=<?php echo e((string) @filemtime(BASE_PATH . '/public/assets/js/ajax.js')); ?>"></script>
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

