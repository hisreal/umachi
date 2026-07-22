<?php

declare(strict_types=1);

/**
 * Required variables:
 * $employeeName, $employeeId, $companyEmail, $password, $department,
 * $role, $dateJoined, $loginUrl, $companyLogo, $companyName,
 * $companyAddress, $companyPhone, $companyContactEmail.
 */
$emailTitle = $emailTitle ?? ('Welcome to ' . (string) $companyName);
$emailHeading = $emailHeading ?? ('Welcome to ' . (string) $companyName);
$emailPreheader = $emailPreheader ?? 'Your employee account has been created. Access your login details and welcome information.';
$emailIntro = $emailIntro ?? ('Congratulations and welcome to the ' . (string) $companyName . ' team! We are excited to have you join our organization. Your employee account has been successfully created, and you can now access the Staff Management System using the login credentials below.');
$emailCardTitle = $emailCardTitle ?? 'Login Information';
$isPasswordReset = (bool) ($isPasswordReset ?? false);
$securityTitle = $securityTitle ?? 'Important Security Notice';
$securityItems = $securityItems ?? [
    'This password is temporary.',
    'Change your password immediately after your first login.',
    'Never share your password with anyone.',
    'Keep your login details secure.',
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title><?php echo htmlspecialchars((string) $emailTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        @media only screen and (max-width: 620px) {
            .email-shell { width: 100% !important; }
            .email-padding { padding-left: 20px !important; padding-right: 20px !important; }
            .email-header { padding: 30px 20px !important; }
            .detail-label, .detail-value { display: block !important; width: 100% !important; }
            .detail-label { padding-bottom: 4px !important; }
            .detail-value { padding-top: 0 !important; text-align: left !important; }
            .login-button { display: block !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f3f6f4; color:#24312b; font-family:Arial, Helvetica, sans-serif; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent;">
        <?php echo htmlspecialchars((string) $emailPreheader, ENT_QUOTES, 'UTF-8'); ?>
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; border-collapse:collapse; background-color:#f3f6f4;">
        <tr>
            <td align="center" style="padding:32px 12px;">
                <table role="presentation" class="email-shell" width="600" cellspacing="0" cellpadding="0" border="0" style="width:600px; max-width:600px; border-collapse:separate; background-color:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 8px 28px rgba(20, 62, 43, 0.10);">
                    <tr>
                        <td class="email-header" align="center" style="padding:38px 36px; background-color:white;">
                            <img src="<?php echo htmlspecialchars((string) $companyLogo, ENT_QUOTES, 'UTF-8'); ?>" width="72" alt="<?php echo htmlspecialchars((string) $companyName, ENT_QUOTES, 'UTF-8'); ?> logo" style="display:block; width:72px; max-width:72px; height:auto; margin:0 auto 18px; border:0;">
                            <h1 style="margin:0; color:#f28c28; font-size:29px; line-height:38px; font-weight:700;"><?php echo htmlspecialchars((string) $emailHeading, ENT_QUOTES, 'UTF-8'); ?></h1>
                        </td>
                    </tr>

                    <tr>
                        <td class="email-padding" style="padding:36px 42px 18px;">
                            <p style="margin:0 0 18px; color:#24312b; font-size:17px; line-height:27px;">Dear <strong><?php echo htmlspecialchars((string) $employeeName, ENT_QUOTES, 'UTF-8'); ?></strong>,</p>
                            <p style="margin:0; color:#526159; font-size:15px; line-height:25px;"><?php echo htmlspecialchars((string) $emailIntro, ENT_QUOTES, 'UTF-8'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <td class="email-padding" style="padding:18px 42px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; border-collapse:separate; background-color:#f7faf8; border:1px solid #dce9e2; border-radius:14px;">
                                <tr>
                                    <td colspan="2" style="padding:20px 22px 12px; color:#f28c28; font-size:18px; line-height:25px; font-weight:700;"><?php echo htmlspecialchars((string) $emailCardTitle, ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <?php if ($isPasswordReset): ?>
                                <tr>
                                    <td class="detail-label" width="42%" style="padding:11px 22px; border-top:1px solid #e5eee9; color:#66766d; font-size:13px; line-height:20px; font-weight:700;">Employee Name</td>
                                    <td class="detail-value" width="58%" style="padding:11px 22px; border-top:1px solid #e5eee9; color:#24312b; font-size:14px; line-height:20px; font-weight:700; text-align:right;"><?php echo htmlspecialchars((string) $employeeName, ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td class="detail-label" width="42%" style="padding:11px 22px; border-top:1px solid #e5eee9; color:#66766d; font-size:13px; line-height:20px; font-weight:700;">Employee ID</td>
                                    <td class="detail-value" width="58%" style="padding:11px 22px; border-top:1px solid #e5eee9; color:#24312b; font-size:14px; line-height:20px; font-weight:700; text-align:right;"><?php echo htmlspecialchars((string) $employeeId, ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding:11px 22px; border-top:1px solid #e5eee9; color:#66766d; font-size:13px; line-height:20px; font-weight:700;">Company Email</td>
                                    <td class="detail-value" style="padding:11px 22px; border-top:1px solid #e5eee9; color:#24312b; font-size:14px; line-height:20px; font-weight:700; text-align:right; word-break:break-word;"><?php echo htmlspecialchars((string) $companyEmail, ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding:11px 22px; border-top:1px solid #e5eee9; color:#66766d; font-size:13px; line-height:20px; font-weight:700;">Temporary Password</td>
                                    <td class="detail-value" style="padding:11px 22px; border-top:1px solid #e5eee9; color:#24312b; font-family:'Courier New', Courier, monospace; font-size:15px; line-height:20px; font-weight:700; text-align:right; word-break:break-all;"><?php echo htmlspecialchars((string) $password, ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding:11px 22px; border-top:1px solid #e5eee9; color:#66766d; font-size:13px; line-height:20px; font-weight:700;">Role</td>
                                    <td class="detail-value" style="padding:11px 22px; border-top:1px solid #e5eee9; color:#24312b; font-size:14px; line-height:20px; font-weight:700; text-align:right;"><?php echo htmlspecialchars((string) $role, ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <tr>
                                    <td class="detail-label" style="padding:11px 22px; border-top:1px solid #e5eee9; color:#66766d; font-size:13px; line-height:20px; font-weight:700;">Department</td>
                                    <td class="detail-value" style="padding:11px 22px; border-top:1px solid #e5eee9; color:#24312b; font-size:14px; line-height:20px; font-weight:700; text-align:right;"><?php echo htmlspecialchars((string) $department, ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <?php if (!$isPasswordReset): ?>
                                <tr>
                                    <td class="detail-label" style="padding:11px 22px 18px; border-top:1px solid #e5eee9; color:#66766d; font-size:13px; line-height:20px; font-weight:700;">Date Joined</td>
                                    <td class="detail-value" style="padding:11px 22px 18px; border-top:1px solid #e5eee9; color:#24312b; font-size:14px; line-height:20px; font-weight:700; text-align:right;"><?php echo htmlspecialchars((string) $dateJoined, ENT_QUOTES, 'UTF-8'); ?></td>
                                <?php endif; ?>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="email-padding" style="padding:8px 42px 18px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; border-collapse:separate; background-color:#fff8e8; border:1px solid #f3d493; border-radius:12px;">
                                <tr>
                                    <td style="padding:18px 20px;">
                                        <p style="margin:0 0 10px; color:#7b4c00; font-size:15px; line-height:22px; font-weight:700;">&#128274; <?php echo htmlspecialchars((string) $securityTitle, ENT_QUOTES, 'UTF-8'); ?></p>
                                        <ul style="margin:0; padding-left:20px; color:#6c5731; font-size:13px; line-height:22px;">
                                            <?php foreach ($securityItems as $securityItem): ?>
                                                <li><?php echo htmlspecialchars((string) $securityItem, ENT_QUOTES, 'UTF-8'); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="email-padding" align="center" style="padding:10px 42px 28px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="border-collapse:separate;">
                                <tr>
                                    <td align="center" bgcolor="#f28c28" style="border-radius:10px;">
                                        <a class="login-button" href="<?php echo htmlspecialchars((string) $loginUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" style="display:inline-block; padding:15px 30px; color:#ffffff; font-size:16px; line-height:20px; font-weight:700; text-decoration:none; border-radius:10px;">Login to Employee Portal</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="email-padding" style="padding:0 42px 36px;">
                            <p style="margin:0 0 10px; color:#24312b; font-size:14px; line-height:22px; font-weight:700;">Before you sign in</p>
                            <ul style="margin:0; padding-left:20px; color:#526159; font-size:13px; line-height:22px;">
                                <li>Your Employee ID can also be used to log in.</li>
                                <li>Passwords are case-sensitive.</li>
                                <li>Contact the administrator if you are unable to access your account.</li>
                            </ul>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:28px 30px; background-color:#eaf1ed; border-top:1px solid #dce6e0;">
                            <p style="margin:0 0 7px; color:#f28c28; font-size:14px; line-height:20px; font-weight:700;"><?php echo htmlspecialchars((string) $companyName, ENT_QUOTES, 'UTF-8'); ?></p>
                            <p style="margin:0 0 5px; color:#637269; font-size:12px; line-height:19px;"><?php echo htmlspecialchars((string) $companyAddress, ENT_QUOTES, 'UTF-8'); ?></p>
                            <p style="margin:0 0 14px; color:#637269; font-size:12px; line-height:19px;">Phone: <?php echo htmlspecialchars((string) $companyPhone, ENT_QUOTES, 'UTF-8'); ?> &nbsp;|&nbsp; Email: <?php echo htmlspecialchars((string) $companyContactEmail, ENT_QUOTES, 'UTF-8'); ?></p>
                            <p style="margin:0; color:#7b8881; font-size:11px; line-height:18px;">This is an automated email. Please do not reply.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
