<?php

declare(strict_types=1);

namespace App\Utilities;

class Str
{
    public static function titleFromSlug(string $slug): string
    {
        return ucwords(str_replace('-', ' ', trim($slug, '/')));
    }
}