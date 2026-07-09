<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class SupervisorController extends Controller
{
    /**
     * Load the manager/supervisor duty roster management page.
     */
    public function manageDutyRoster(): void
    {
        $this->render('supervisor/manage-duty-roster.php', [
            'currentRoute' => 'supervisor/manage-duty-roster',
        ]);
    }
}