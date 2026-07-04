<?php

declare(strict_types=1);

namespace App\Models;

class Attendance
{
    /**
     * Return static staff details until database integration is added.
     */
    public function getEmployee(): array
    {
        // DATABASE PLACEHOLDER
        // Replace this sample data with values retrieved from the database during backend integration.
        return [
            'name' => 'Chinedu Okafor',
            'employee_id' => 'EMP-FS-0017',
            'role' => 'Pump Attendant',
            'assigned_pump' => 'Pump 03 - PMS Lane',
            'shift' => 'Morning Shift (06:00 AM - 02:00 PM)',
            'department' => 'Forecourt Operations',
        ];
    }

    /**
     * Return the current static attendance status for the clock-in workflow.
     */
    public function getAttendanceStatus(): array
    {
        // DATABASE PLACEHOLDER
        // Replace this sample status with the employee's current attendance state from the database.
        return [
            'label' => 'Awaiting Clock In',
            'detail' => 'Take a fresh selfie to start the assigned morning shift.',
            'shift_date' => 'Saturday, July 4, 2026',
            'current_time' => '05:54 AM',
            'expected_start' => '06:00 AM',
            'station' => 'Umachi Main Filling Station',
            'status_type' => 'waiting',
            'photo_status' => 'Waiting for Selfie',
        ];
    }

    /**
     * Return static attendance records for the current frontend prototype.
     */
    public function getAttendanceHistory(): array
    {
        // DATABASE PLACEHOLDER
        // Replace this sample history with attendance records retrieved from the database.
        return [
            [
                'date' => '2026-07-04',
                'clock_in' => 'Not yet clocked in',
                'clock_out' => 'Not yet clocked out',
                'status' => 'Awaiting Clock In',
                'photo_status' => 'Waiting for Selfie',
            ],
            [
                'date' => '2026-07-03',
                'clock_in' => '06:03 AM',
                'clock_out' => '02:06 PM',
                'status' => 'Present',
                'photo_status' => 'Captured',
            ],
            [
                'date' => '2026-07-02',
                'clock_in' => '06:12 AM',
                'clock_out' => '02:04 PM',
                'status' => 'Late',
                'photo_status' => 'Captured',
            ],
            [
                'date' => '2026-07-01',
                'clock_in' => '05:58 AM',
                'clock_out' => '02:00 PM',
                'status' => 'Present',
                'photo_status' => 'Captured',
            ],
            [
                'date' => '2026-06-30',
                'clock_in' => '06:05 AM',
                'clock_out' => '02:08 PM',
                'status' => 'Present',
                'photo_status' => 'Captured',
            ],
            [
                'date' => '2026-06-29',
                'clock_in' => '06:18 AM',
                'clock_out' => '02:10 PM',
                'status' => 'Late',
                'photo_status' => 'Supervisor Review',
            ],
        ];
    }

    /**
     * Return static form options for the clock-out fuel sales workflow.
     */
    public function getClockOutOptions(): array
    {
        // DATABASE PLACEHOLDER
        // Replace these option lists with pumps and fuel products retrieved from the database.
        return [
            'pumps' => ['Pump 1', 'Pump 2', 'Pump 3', 'Pump 4'],
            'fuel_types' => ['PMS', 'AGO', 'DPK', 'LPG'],
        ];
    }

    /**
     * Return static fuel sales values for the current shift summary.
     */
    public function getFuelSalesSummary(): array
    {
        // DATABASE PLACEHOLDER
        // Replace this sample sales summary with meter and payment records from the database.
        return [
            'assigned_pump' => 'Pump 3',
            'fuel_type' => 'PMS',
            'opening_meter' => '18,450.00',
            'closing_meter' => '18,725.50',
            'liters_sold' => '275.50',
            'amount_collected' => '₦234,175',
            'shift' => 'Morning Shift (06:00 AM - 02:00 PM)',
            'date' => 'Saturday, July 4, 2026',
            'remarks' => 'Shift completed successfully. Pump balanced with cashier record.',
        ];
    }

    /**
     * Return previous static clock-out and fuel sales records.
     */
    public function getPreviousShiftHistory(): array
    {
        // DATABASE PLACEHOLDER
        // Replace these sample rows with completed shift records from the database.
        return [
            [
                'date' => '2026-07-03',
                'shift' => 'Morning',
                'pump' => 'Pump 3',
                'fuel_type' => 'PMS',
                'liters_sold' => '268.40',
                'amount' => '₦228,140',
                'clock_out_time' => '02:06 PM',
                'status' => 'Submitted',
            ],
            [
                'date' => '2026-07-02',
                'shift' => 'Morning',
                'pump' => 'Pump 3',
                'fuel_type' => 'PMS',
                'liters_sold' => '251.80',
                'amount' => '₦214,030',
                'clock_out_time' => '02:04 PM',
                'status' => 'Submitted',
            ],
            [
                'date' => '2026-07-01',
                'shift' => 'Morning',
                'pump' => 'Pump 2',
                'fuel_type' => 'AGO',
                'liters_sold' => '192.35',
                'amount' => '₦198,120',
                'clock_out_time' => '02:00 PM',
                'status' => 'Submitted',
            ],
            [
                'date' => '2026-06-30',
                'shift' => 'Morning',
                'pump' => 'Pump 4',
                'fuel_type' => 'PMS',
                'liters_sold' => '284.10',
                'amount' => '₦241,485',
                'clock_out_time' => '02:08 PM',
                'status' => 'Reviewed',
            ],
        ];
    }
}
