<?php

declare(strict_types=1);

// ===========================================
// DATABASE PLACEHOLDER
// Replace with MySQL announcement records.
// ===========================================
$announcements = [
    ['id' => 1, 'title' => 'Monthly Safety Meeting', 'category' => 'Safety Notice', 'audience' => 'Everyone', 'priority' => 'High', 'status' => 'Published', 'publish_date' => '2026-07-12', 'publish_time' => '09:00', 'expiry_date' => '2026-07-20', 'created_by' => 'Administrator', 'pinned' => true, 'attachment' => 'safety-meeting-agenda.pdf'],
    ['id' => 2, 'title' => 'Pump Maintenance Schedule', 'category' => 'Maintenance', 'audience' => 'Pump Attendants', 'priority' => 'Medium', 'status' => 'Scheduled', 'publish_date' => '2026-07-15', 'publish_time' => '07:30', 'expiry_date' => '2026-07-18', 'created_by' => 'Administrator', 'pinned' => false, 'attachment' => 'pump-maintenance.xlsx'],
    ['id' => 3, 'title' => 'New Cash Handling Policy', 'category' => 'Company Policy', 'audience' => 'Cashiers', 'priority' => 'Urgent', 'status' => 'Draft', 'publish_date' => '2026-07-16', 'publish_time' => '08:00', 'expiry_date' => '2026-08-01', 'created_by' => 'Administrator', 'pinned' => true, 'attachment' => 'cash-policy.docx'],
    ['id' => 4, 'title' => 'Public Holiday Shift Reminder', 'category' => 'Holiday', 'audience' => 'Everyone', 'priority' => 'Low', 'status' => 'Expired', 'publish_date' => '2026-06-28', 'publish_time' => '10:00', 'expiry_date' => '2026-07-02', 'created_by' => 'Station Manager', 'pinned' => false, 'attachment' => ''],
    ['id' => 5, 'title' => 'Emergency Fire Drill', 'category' => 'Emergency', 'audience' => 'Security', 'priority' => 'Urgent', 'status' => 'Archived', 'publish_date' => '2026-06-22', 'publish_time' => '14:00', 'expiry_date' => '2026-06-30', 'created_by' => 'Safety Officer', 'pinned' => false, 'attachment' => 'fire-drill-map.png'],
];

$categories = ['General Notice', 'Staff Meeting', 'Safety Notice', 'Maintenance', 'Company Policy', 'Emergency', 'Holiday', 'Training', 'Promotion', 'Other'];
$priorities = ['Low', 'Medium', 'High', 'Urgent'];
$statuses = ['Draft', 'Published', 'Scheduled', 'Expired', 'Archived'];
$audienceGroups = ['Everyone', 'Managers', 'Supervisors', 'Pump Attendants', 'Cashiers', 'Accountants', 'Security'];
$notificationGroups = ['Notify All Employees', 'Notify Managers', 'Notify Supervisors'];
$announcementStats = ['views' => 148, 'acknowledged' => 96, 'unread' => 22, 'comments' => 0];
$summaryCards = [
    ['label' => 'Total Announcements', 'value' => count($announcements), 'icon' => 'fa-solid fa-bullhorn', 'tone' => 'primary'],
    ['label' => 'Published', 'value' => count(array_filter($announcements, static fn (array $item): bool => $item['status'] === 'Published')), 'icon' => 'fa-solid fa-circle-check', 'tone' => 'success'],
    ['label' => 'Scheduled', 'value' => count(array_filter($announcements, static fn (array $item): bool => $item['status'] === 'Scheduled')), 'icon' => 'fa-solid fa-calendar-clock', 'tone' => 'info'],
    ['label' => 'Drafts', 'value' => count(array_filter($announcements, static fn (array $item): bool => $item['status'] === 'Draft')), 'icon' => 'fa-solid fa-pen', 'tone' => 'warning'],
    ['label' => 'Expired', 'value' => count(array_filter($announcements, static fn (array $item): bool => $item['status'] === 'Expired')), 'icon' => 'fa-solid fa-hourglass-end', 'tone' => 'danger'],
    ['label' => 'Archived', 'value' => count(array_filter($announcements, static fn (array $item): bool => $item['status'] === 'Archived')), 'icon' => 'fa-solid fa-box-archive', 'tone' => 'muted'],
];
$selectedAnnouncement = $announcements[0];
$announcementContent = '<p>All staff are required to attend the monthly safety meeting at the Station Office. Attendance is compulsory for all departments.</p><ul><li>Review fuel handling procedures.</li><li>Discuss emergency response steps.</li><li>Confirm pump safety checklist ownership.</li></ul>';

if (!function_exists('announcement_badge_class')) {
    function announcement_badge_class(string $type, string $value): string
    {
        $priority = ['Low' => 'announcement-badge--low', 'Medium' => 'announcement-badge--medium', 'High' => 'announcement-badge--high', 'Urgent' => 'announcement-badge--urgent'];
        $status = ['Draft' => 'announcement-status--draft', 'Published' => 'announcement-status--published', 'Scheduled' => 'announcement-status--scheduled', 'Expired' => 'announcement-status--expired', 'Archived' => 'announcement-status--archived'];
        return $type === 'priority' ? ($priority[$value] ?? 'announcement-badge--medium') : ($status[$value] ?? 'announcement-status--draft');
    }
}