<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;
use Throwable;

final class MailService
{
    private array $settings;
    private string $basePath;

    public function __construct(?array $settings = null)
    {
        $this->basePath = dirname(__DIR__, 2);
        $this->settings = $settings ?? (array) (new Config($this->basePath . '/config'))->get('mail', []);
        $this->loadPhpMailer();
    }
    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }


    public function sendTemplate(string $recipient, string $recipientName, string $subject, string $template, array $data): void
    {
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('The welcome email recipient address is invalid.');
        }
        if (trim($subject) === '') {
            throw new RuntimeException('The email subject is required.');
        }

        $html = $this->renderTemplate($template, $data);
        $mailer = $this->mailer();

        try {
            $mailer->addAddress($recipient, $recipientName);
            $mailer->Subject = $subject;
            $mailer->isHTML(true);
            $mailer->Body = $html;
            $mailer->AltBody = $this->plainText($html);
            if (!$mailer->send()) {
                throw new RuntimeException($mailer->ErrorInfo !== '' ? $mailer->ErrorInfo : 'SMTP delivery failed.');
            }
        } catch (Throwable $exception) {
            throw new RuntimeException('Welcome email delivery failed: ' . $exception->getMessage(), 0, $exception);
        }
    }

    private function mailer(): PHPMailer
    {
        foreach (['host', 'from_address'] as $required) {
            if (trim((string) ($this->settings[$required] ?? '')) === '') {
                throw new RuntimeException('SMTP configuration is incomplete: ' . $required . ' is required.');
            }
        }
        if (filter_var((string) $this->settings['from_address'], FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('The configured sender email address is invalid.');
        }

        $mailer = new PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = (string) $this->settings['host'];
        $mailer->Port = (int) ($this->settings['port'] ?? 587);
        $mailer->SMTPAuth = (bool) ($this->settings['smtp_auth'] ?? true);
        $mailer->Username = (string) ($this->settings['username'] ?? '');
        $mailer->Password = (string) ($this->settings['password'] ?? '');
        $mailer->Timeout = max(1, (int) ($this->settings['timeout'] ?? 15));
        $encryption = strtolower((string) ($this->settings['encryption'] ?? 'tls'));
        if ($encryption === 'tls') {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption === 'ssl' || $encryption === 'smtps') {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mailer->SMTPSecure = '';
            $mailer->SMTPAutoTLS = false;
        }
        $mailer->CharSet = PHPMailer::CHARSET_UTF8;
        $mailer->setFrom((string) $this->settings['from_address'], (string) ($this->settings['from_name'] ?? ''));
        return $mailer;
    }

    public function renderTemplate(string $template, array $data): string
    {
        $path = $this->basePath . '/app/Views/emails/' . basename($template) . '.php';
        if (!is_file($path)) {
            throw new RuntimeException('Email template not found.');
        }

        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $path;
            return (string) ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }
    }

    private function plainText(string $html): string
    {
        return trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>'], "\n", $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function loadPhpMailer(): void
    {
        foreach (['Exception.php', 'PHPMailer.php', 'SMTP.php'] as $file) {
            $path = $this->basePath . '/PHPMailer/src/' . $file;
            if (!is_file($path)) {
                throw new RuntimeException('PHPMailer source file is missing: ' . $file);
            }
            require_once $path;
        }
    }
}
