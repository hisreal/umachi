<?php

declare(strict_types=1);

namespace App\Services;

class AuthService
{
    public function check(): bool
    {
        // Authentication will be implemented during backend development.
        return false;
    }

    public function user(): ?array
    {
        // DATABASE PLACEHOLDER: Retrieve authenticated user profile.
        return null;
    }
}