<?php

declare(strict_types=1);

use App\Models\Announcement;

try {
    $announcementModel = new Announcement();
    $announcementData = $announcementModel->adminData(isset($_GET['id']) ? (int) $_GET['id'] : null);
} catch (Throwable $exception) {
    error_log('[Announcement] Data load failed: ' . $exception->getMessage());
    $announcementData = [
        'announcements' => [],
        'categories' => ['General Notice', 'Staff Meeting', 'Safety Notice', 'Maintenance', 'Company Policy', 'Emergency', 'Holiday', 'Training', 'Promotion', 'Other'],
        'priorities' => ['Low', 'Normal', 'High', 'Urgent'],
        'statuses' => ['Draft', 'Published', 'Scheduled', 'Expired', 'Archived'],
        'audienceGroups' => ['Everyone', 'Admin', 'Manager', 'Supervisor', 'Pump Attendant', 'Cashier', 'Security', 'Driver', 'Accountant'],
        'notificationGroups' => ['Notify All Employees', 'Notify Managers', 'Notify Supervisors'],
        'announcementStats' => ['views' => 0, 'acknowledged' => 0, 'unread' => 0, 'comments' => 0],
        'summaryCards' => [],
        'selectedAnnouncement' => ['db_id' => 0, 'id' => '', 'title' => 'Announcement unavailable', 'message' => '', 'category' => 'General Notice', 'audience' => 'Everyone', 'selected_roles' => [], 'priority' => 'Normal', 'status' => 'Draft', 'raw_status' => 'Draft', 'publish_date' => date('Y-m-d'), 'publish_time' => date('H:i'), 'expiry_date' => '', 'created_by' => 'System', 'pinned' => false, 'attachment' => ''],
        'announcementContent' => '<p>Announcement data could not be loaded.</p>',
        'announcementSuccess' => null,
        'announcementError' => 'Announcement data could not be loaded. Please verify the database schema.',
    ];
}

$announcements = $announcementData['announcements'];
$categories = $announcementData['categories'];
$priorities = $announcementData['priorities'];
$statuses = $announcementData['statuses'];
$audienceGroups = $announcementData['audienceGroups'];
$notificationGroups = $announcementData['notificationGroups'];
$announcementStats = $announcementData['announcementStats'];
$summaryCards = $announcementData['summaryCards'];
$selectedAnnouncement = $announcementData['selectedAnnouncement'];
$announcementContent = $announcementData['announcementContent'];
$announcementSuccess = $announcementData['announcementSuccess'];
$announcementError = $announcementData['announcementError'];

if (!function_exists('announcement_badge_class')) {
    function announcement_badge_class(string $type, string $value): string
    {
        $priority = ['Low' => 'announcement-badge--low', 'Normal' => 'announcement-badge--medium', 'Medium' => 'announcement-badge--medium', 'High' => 'announcement-badge--high', 'Urgent' => 'announcement-badge--urgent'];
        $status = ['Draft' => 'announcement-status--draft', 'Published' => 'announcement-status--published', 'Scheduled' => 'announcement-status--scheduled', 'Expired' => 'announcement-status--expired', 'Archived' => 'announcement-status--archived'];

        return $type === 'priority' ? ($priority[$value] ?? 'announcement-badge--medium') : ($status[$value] ?? 'announcement-status--draft');
    }
}
