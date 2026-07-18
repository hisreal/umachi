<?php

declare(strict_types=1);

$topbarTitle = $topbarTitle ?? 'Filling Station Staff & Activity Management';
$topbarSubtitle = $topbarSubtitle ?? 'Pump Attendant Dashboard';
?>
<header class="attendant-topbar">
    <label class="attendant-menu-button" for="attendantSidebarControl" role="button" tabindex="0" aria-label="Open navigation">
        <i class="fa-solid fa-bars"></i>
    </label>
    <div>
        <span><?php echo e($topbarTitle); ?></span>
        <strong><?php echo e($topbarSubtitle); ?></strong>
    </div>
    <div class="attendant-topbar__status">
        <i class="fa-solid fa-circle"></i>
        Testing Mode
    </div>
</header>
