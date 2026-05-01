<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;

final class MailService
{
    /**
     * @throws MailerException
     */
    public function send(string $toEmail, string $toName, string $subject, string $html, string $text, array $options = []): array
    {
        $mail = new PHPMailer(true);
        $transport = strtolower(trim((string) envget('MAIL_TRANSPORT', 'smtp')));
        if (!in_array($transport, ['smtp', 'mail'], true)) {
            $transport = 'smtp';
        }

        $smtpHost = (string) envget('SMTP_HOST', 'mail.novaweb.ro');
        $smtpPort = (int) envget('SMTP_PORT', '465');
        $smtpSecure = (string) envget('SMTP_SECURE', 'ssl');
        $smtpUser = (string) envget('SMTP_USER', 'noreply@novaweb.ro');

        if ($transport === 'mail') {
            $mail->isMail();
        } else {
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->Port = $smtpPort;
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = $smtpSecure;
            $mail->Username = $smtpUser;
            $mail->Password = (string) envget('SMTP_PASS');
        }

        $mail->Timeout = max(5, (int) envget('SMTP_TIMEOUT', '20'));
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->XMailer = 'NovaWeb Audit SEO';

        $fromEmail = (string) envget('MAIL_FROM', 'noreply@novaweb.ro');
        $fromName = (string) envget('MAIL_FROM_NAME', 'NovaWeb Audit SEO');
        $messageId = self::newMessageId($fromEmail);
        $mail->MessageID = $messageId;
        $mail->Sender = $fromEmail;
        $mail->setFrom(
            $fromEmail,
            $fromName
        );
        $mail->addCustomHeader('X-Nova-Audit-Message-ID', trim($messageId, '<>'));

        $replyToEmail = trim((string)($options['reply_to_email'] ?? envget('MAIL_REPLY_TO', 'contact@novaweb.ro')));
        $replyToName = trim((string)($options['reply_to_name'] ?? envget('MAIL_REPLY_TO_NAME', 'NovaWeb')));
        if ($replyToEmail !== '' && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyToEmail, $replyToName !== '' ? $replyToName : $replyToEmail);
        }

        $smtpCallback = [];
        $mail->action_function = static function (
            bool $result,
            array $to,
            array $cc,
            array $bcc,
            string $subject,
            string $body,
            string $from,
            array $extra
        ) use (&$smtpCallback): void {
            $smtpCallback = [
                'accepted' => $result,
                'smtp_transaction_id' => (string)($extra['smtp_transaction_id'] ?? ''),
            ];
        };

        $mail->addAddress($toEmail, $toName);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $html;
        $mail->AltBody = $text;

        try {
            $mail->send();
        } catch (MailerException $e) {
            self::logEvent('send_failed', array_merge([
                'debug_id' => (string)($options['debug_id'] ?? ''),
                'transport' => $transport,
                'message_id' => $messageId,
                'to' => self::maskEmail($toEmail),
                'from' => $fromEmail,
                'smtp_host' => $smtpHost,
                'smtp_port' => $smtpPort,
                'smtp_secure' => $smtpSecure,
                'smtp_user' => self::maskEmail($smtpUser),
                'error' => $e->getMessage(),
                'error_info' => $mail->ErrorInfo,
            ], $transport === 'smtp' ? self::smtpDiagnostics($mail) : self::mailDiagnostics()));
            throw $e;
        }

        $meta = [
            'debug_id' => (string)($options['debug_id'] ?? ''),
            'transport' => $transport,
            'accepted_by_transport' => true,
            'accepted_by_smtp' => $transport === 'smtp',
            'message_id' => $messageId,
            'smtp_transaction_id' => (string)($smtpCallback['smtp_transaction_id'] ?? ''),
            'to' => self::maskEmail($toEmail),
            'from' => $fromEmail,
            'smtp_host' => $smtpHost,
            'smtp_port' => $smtpPort,
            'smtp_secure' => $smtpSecure,
            'sendmail_path' => $transport === 'mail' ? (string) ini_get('sendmail_path') : '',
        ];

        self::logEvent('send_accepted', $meta);

        return $meta;
    }

    private static function newMessageId(string $fromEmail): string
    {
        $domain = 'novaweb.ro';
        if (str_contains($fromEmail, '@')) {
            $candidate = substr(strrchr($fromEmail, '@') ?: '', 1);
            if ($candidate !== '') {
                $domain = strtolower($candidate);
            }
        }

        try {
            $id = bin2hex(random_bytes(12));
        } catch (\Throwable $e) {
            $id = str_replace('.', '', uniqid('', true));
        }

        return sprintf('<nova-audit-%s@%s>', $id, $domain);
    }

    private static function maskEmail(string $email): string
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '[invalid-email]';
        }

        [$local, $domain] = explode('@', $email, 2);
        $prefix = substr($local, 0, 1);
        return ($prefix !== '' ? $prefix : '*') . '***@' . $domain;
    }

    private static function smtpDiagnostics(PHPMailer $mail): array
    {
        try {
            $smtp = $mail->getSMTPInstance();
            return [
                'smtp_error' => $smtp->getError(),
                'smtp_last_reply' => $smtp->getLastReply(),
            ];
        } catch (\Throwable $e) {
            return [
                'smtp_diagnostics_error' => $e->getMessage(),
            ];
        }
    }

    private static function mailDiagnostics(): array
    {
        return [
            'sendmail_path' => (string) ini_get('sendmail_path'),
            'smtp_ini' => (string) ini_get('SMTP'),
            'smtp_port_ini' => (string) ini_get('smtp_port'),
        ];
    }

    private static function logEvent(string $event, array $data): void
    {
        $json = json_encode(
            $data,
            JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR
        );
        if ($json === false) {
            $json = '{"log_error":"' . addslashes(json_last_error_msg()) . '"}';
        }

        error_log('MailService ' . $event . ': ' . $json);

        $dir = class_exists('Config') ? Config::storagePath('logs') : dirname(__DIR__) . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $line = '[' . date('Y-m-d H:i:s') . '] ' . $event . ' ' . $json . PHP_EOL;
        @file_put_contents($dir . '/email.log', $line, FILE_APPEND | LOCK_EX);
    }
}
