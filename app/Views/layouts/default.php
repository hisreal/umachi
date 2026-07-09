<?php

declare(strict_types=1);

$contentView = $contentView ?? null;
$contentData = $contentData ?? [];

require view_path('includes/header.php');

if (is_string($contentView) && $contentView !== '') {
    extract($contentData, EXTR_SKIP);
    require view_path($contentView);
}

require view_path('includes/footer.php');
