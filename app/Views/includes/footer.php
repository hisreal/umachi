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
    <?php foreach ($extraScripts as $scriptPath): ?>
        <script src="<?php echo e(str_starts_with($scriptPath, 'http') ? $scriptPath : asset_url($scriptPath)); ?>"></script>
    <?php endforeach; ?>
</body>
</html>
