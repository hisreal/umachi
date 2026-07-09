<?php

declare(strict_types=1);

return [
    ['label' => 'Dashboard', 'route' => 'admin/dashboard', 'icon' => 'fa-solid fa-gauge-high'],
    [
        'label' => 'Employee Management',
        'icon' => 'fa-solid fa-users',
        'active_routes' => ['admin/edit-employee', 'admin/employee-profile', 'admin/employee-documents'],
        'children' => [
            ['label' => 'Employee List', 'route' => 'admin/employees', 'icon' => 'fa-solid fa-user-group'],
            ['label' => 'Add Employee', 'route' => 'admin/add-employee', 'icon' => 'fa-solid fa-user-plus'],
        ],
    ],
    [
        'label' => 'Attendance',
        'icon' => 'fa-solid fa-calendar-check',
        'children' => [
            ['label' => 'Dashboard', 'route' => 'admin/attendance-dashboard', 'icon' => 'fa-solid fa-chart-line'],
            ['label' => 'Attendance History', 'route' => 'admin/attendance-history', 'icon' => 'fa-solid fa-clock-rotate-left'],
            ['label' => 'Attendance Settings', 'route' => 'admin/attendance-settings', 'icon' => 'fa-solid fa-sliders'],
        ],
    ],
    [
        'label' => 'Fuel Sales',
        'icon' => 'fa-solid fa-gas-pump',
        'children' => [
            ['label' => 'Sales Dashboard', 'route' => 'admin/fuel-sales-dashboard', 'icon' => 'fa-solid fa-chart-column'],
            ['label' => 'Sales History', 'route' => 'admin/fuel-sales-history', 'icon' => 'fa-solid fa-file-invoice-dollar'],
            ['label' => 'Verify Sales', 'route' => 'admin/verify-sales', 'icon' => 'fa-solid fa-circle-check'],
            ['label' => 'Fuel Inventory', 'route' => 'admin/fuel-inventory', 'icon' => 'fa-solid fa-warehouse'],
        ],
    ],
    [
        'label' => 'Pump Management',
        'icon' => 'fa-solid fa-oil-can',
        'active_routes' => ['admin/edit-pump'],
        'children' => [
            ['label' => 'Pumps', 'route' => 'admin/pumps', 'icon' => 'fa-solid fa-gas-pump'],
            ['label' => 'Add Pump', 'route' => 'admin/add-pump', 'icon' => 'fa-solid fa-plus'],
        ],
    ],
    [
        'label' => 'Duty Roster',
        'icon' => 'fa-solid fa-calendar-days',
        'active_routes' => ['admin/manage-duty-roster', 'admin/calendar'],
        'children' => [
            ['label' => 'Duty Dashboard', 'route' => 'admin/duty-roster', 'icon' => 'fa-solid fa-clipboard-list'],
            ['label' => 'Duty Calendar', 'route' => 'admin/duty-calendar', 'icon' => 'fa-solid fa-calendar'],
            ['label' => 'Shift Management', 'route' => 'admin/shift-management', 'icon' => 'fa-solid fa-business-time'],
            ['label' => 'Pump Allocation', 'route' => 'admin/pump-allocation', 'icon' => 'fa-solid fa-map-location-dot'],
        ],
    ],
    [
        'label' => 'Leave Management',
        'icon' => 'fa-solid fa-person-walking-arrow-right',
        'children' => [
            ['label' => 'Leave Dashboard', 'route' => 'admin/leave-dashboard', 'icon' => 'fa-solid fa-chart-pie'],
            ['label' => 'Leave Requests', 'route' => 'admin/leave-requests', 'icon' => 'fa-solid fa-envelope-open-text'],
            ['label' => 'Leave History', 'route' => 'admin/leave-history', 'icon' => 'fa-solid fa-folder-open'],
            ['label' => 'Leave Types', 'route' => 'admin/leave-types', 'icon' => 'fa-solid fa-list-check'],
            ['label' => 'Approval Settings', 'route' => 'admin/leave-approval-settings', 'icon' => 'fa-solid fa-user-shield'],
        ],
    ],
   
    ['label' => 'Announcements', 'route' => 'admin/announcements', 'icon' => 'fa-solid fa-bullhorn'],
    [
        'label' => 'Reports(Future Enhancement)',
        'icon' => 'fa-solid fa-chart-bar',
        'children' => [
            ['label' => 'Attendance', 'route' => 'admin/reports-attendance', 'icon' => 'fa-solid fa-calendar-check'],
            ['label' => 'Fuel Sales', 'route' => 'admin/reports-fuel-sales', 'icon' => 'fa-solid fa-gas-pump'],
            ['label' => 'Employees', 'route' => 'admin/reports-employees', 'icon' => 'fa-solid fa-users'],
            ['label' => 'Duty Roster', 'route' => 'admin/reports-duty-roster', 'icon' => 'fa-solid fa-calendar-days'],
            ['label' => 'Leave', 'route' => 'admin/reports-leave', 'icon' => 'fa-solid fa-person-walking-arrow-right'],
            ['label' => 'Payroll', 'route' => 'admin/reports-payroll', 'icon' => 'fa-solid fa-money-check-dollar'],
            ['label' => 'Performance', 'route' => 'admin/reports-performance', 'icon' => 'fa-solid fa-chart-line'],
        ],
    ],
  
    [
        'label' => 'Settings',
        'icon' => 'fa-solid fa-gears',
        'children' => [
        
            ['label' => 'Fuel Pricing', 'route' => 'admin/fuel-pricing', 'icon' => 'fa-solid fa-tags'],
            ['label' => 'Profile', 'route' => 'admin/profile', 'icon' => 'fa-solid fa-user-circle', 'active_routes' => ['admin/edit-profile', 'admin/change-password']],
            ['label' => 'Logout', 'route' => 'logout', 'icon' => 'fa-solid fa-right-from-bracket', 'logout' => true],
    
        ],
    ],
];