<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class AuthController extends Controller
{
    /**
     * Load the standalone login page with no dashboard shell.
     */
    public function login(): void
    {
        $this->render('auth/login.php');
    }
}