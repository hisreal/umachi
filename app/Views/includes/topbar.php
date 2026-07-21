<?php

declare(strict_types=1);

$topbarTitle = $topbarTitle ?? 'Filling Station Staff & Activity Management';
$topbarSubtitle = $topbarSubtitle ?? 'Pump Attendant Dashboard';
$administratorQuickLinks = is_array($administratorQuickLinks ?? null) ? $administratorQuickLinks : [];
?>
<header class="attendant-topbar">
    <label class="attendant-menu-button" for="attendantSidebarControl" role="button" tabindex="0" aria-label="Open navigation">
        <i class="fa-solid fa-bars"></i>
    </label>
    <div>
        <span><?php echo e($topbarTitle); ?></span>
        <strong><?php echo e($topbarSubtitle); ?></strong>
    </div>
    <?php if ($administratorQuickLinks !== []): ?>
    <div class="topbar-quick-actions">
        <?php foreach ($administratorQuickLinks as $quickLink): ?>
            <a class="topbar-external-action topbar-external-action--<?php echo e($quickLink['variant']); ?>" href="<?php echo e($quickLink['url']); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo e($quickLink['tooltip']); ?>" data-bs-toggle="tooltip" data-bs-placement="bottom" aria-label="<?php echo e($quickLink['tooltip']); ?>">
                <i class="<?php echo e($quickLink['icon']); ?>" aria-hidden="true"></i>
                <span class="topbar-action-label"><?php echo e($quickLink['label']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</header>
