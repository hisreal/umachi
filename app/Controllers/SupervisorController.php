<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;

class SupervisorController extends Controller
{
    /**
     * Load the manager/supervisor duty roster management page.
     */
    public function manageDutyRoster(): void
    {
        $this->render('supervisor/manage-duty-roster.php', [
            'currentRoute' => Request::capture()->route(),
        ]);
    }

    /**
     * Load the manager/supervisor fuel sales dashboard using live database records.
     */
    public function fuelSales(): void
    {
        $this->render('admin/fuel-sales.php', [
            'currentRoute' => Request::capture()->route(),
        ]);
    }
}
