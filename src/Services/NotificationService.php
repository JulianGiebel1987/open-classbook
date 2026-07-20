<?php

namespace OpenClassbook\Services;

use OpenClassbook\App;
use OpenClassbook\Database;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class NotificationService
{
    /**
     * E-Mail-Benachrichtigung bei Lehrer-Krankmeldung an Schulleitung und Sekretariat senden
     */
    public static function notifyTeacherAbsence(array $teacher, array $absenceData): void
    {
        if (!App::config('mail.enabled')) {
            Logger::info('Mail deaktiviert - Krankmeldung nicht versendet', [
                'teacher' => $teacher['firstname'] . ' ' . $teacher['lastname'],
            ]);
            return;
        }

        $recipients = self::getNotificationRecipients();
        if (empty($recipients)) {
            Logger::warning('Keine Empfänger für Krankmeldungs-Benachrichtigung gefunden.');
            return;
        }

        $typeLabels = [
            'krank' => 'Krankheit',
            'fortbildung' => 'Fortbildung',
            'sonstiges' => 'Sonstiges',
        ];

        $typeLabel = $typeLabels[$absenceData['type']] ?? $absenceData['type'];
        $teacherName = $teacher['firstname'] . ' ' . $teacher['lastname'] . ' (' . $teacher['abbreviation'] . ')';
        $dateFrom = date('d.m.Y', strtotime($absenceData['date_from']));
        $dateTo = date('d.m.Y', strtotime($absenceData['date_to']));

        $subject = 'Abwesenheitsmeldung: ' . $teacherName . ' - ' . $typeLabel;

        $body = "Sehr geehrte Damen und Herren,\n\n";
        $body .= "folgende Abwesenheit wurde gemeldet:\n\n";
        $body .= "Lehrkraft: {$teacherName}\n";
        $body .= "Art: {$typeLabel}\n";
        $body .= "Zeitraum: {$dateFrom} bis {$dateTo}\n";

        if (!empty($absenceData['reason'])) {
            $body .= "Grund: {$absenceData['reason']}\n";
        }

        if (!empty($absenceData['notes'])) {
            $body .= "Anmerkungen: {$absenceData['notes']}\n";
        }

        $body .= "\n--\nDiese Nachricht wurde automatisch von Open-Classbook versendet.";

        foreach ($recipients as $recipient) {
            self::sendMail($recipient['email'], $subject, $body);
        }
    }

    /**
     * Empfänger für Abwesenheits-Benachrichtigungen: Schulleitung und Sekretariat
     */
    private static function getNotificationRecipients(): array
    {
        return Database::query(
            "SELECT email FROM users WHERE role IN ('schulleitung', 'sekretariat') AND active = 1 AND email IS NOT NULL AND email != ''"
        );
    }

    /**
     * 2FA-Verifizierungscode per E-Mail senden
     */
    public static function sendTwoFactorCode(string $to, string $code): bool
    {
        if (!App::config('mail.enabled')) {
            Logger::warning('Mail deaktiviert - 2FA-Code nicht per E-Mail versendet', ['to' => $to]);
            return false;
        }

        $subject = 'Ihr Anmeldecode für Open-Classbook';

        $body  = "Sehr geehrte/r Nutzer/in,\n\n";
        $body .= "Ihr Verifizierungscode für die Anmeldung bei Open-Classbook lautet:\n\n";
        $body .= "    " . $code . "\n\n";
        $body .= "Dieser Code ist 10 Minuten gültig.\n\n";
        $body .= "Falls Sie diese Anmeldung nicht selbst durchgeführt haben, ignorieren Sie diese E-Mail.\n\n";
        $body .= "--\nDiese Nachricht wurde automatisch von Open-Classbook versendet.\n";
        $body .= "Bitte antworten Sie nicht auf diese E-Mail.";

        return self::sendMail($to, $subject, $body);
    }

    /**
     * Link zum Zurücksetzen des Passworts per E-Mail senden
     */
    public static function sendPasswordResetMail(string $to, string $username, string $resetUrl): bool
    {
        if (!App::config('mail.enabled')) {
            Logger::warning('Mail deaktiviert - Passwort-Reset-Link nicht per E-Mail versendet', ['to' => $to]);
            return false;
        }

        $subject = 'Passwort zurücksetzen – Open-Classbook';

        $body  = "Sehr geehrte/r " . $username . ",\n\n";
        $body .= "Sie haben die Zurücksetzung Ihres Passworts für Open-Classbook angefordert.\n\n";
        $body .= "Öffnen Sie den folgenden Link innerhalb von 60 Minuten, um ein neues Passwort festzulegen:\n\n";
        $body .= "    " . $resetUrl . "\n\n";
        $body .= "Sollten Sie diese Anfrage nicht gestellt haben, ignorieren Sie diese E-Mail.\n";
        $body .= "Ihr bisheriges Passwort bleibt in diesem Fall unverändert.\n\n";
        $body .= "--\nDiese Nachricht wurde automatisch von Open-Classbook versendet.\n";
        $body .= "Bitte antworten Sie nicht auf diese E-Mail.";

        return self::sendMail($to, $subject, $body);
    }

    /**
     * Einladung fuer ein neu angelegtes/importiertes Konto: Link zum Festlegen
     * des ersten Passworts. Ersetzt die frueheren (nie sichtbaren) Zufalls-
     * passwoerter. Das Oeffnen des Links verifiziert zugleich die E-Mail-Adresse.
     */
    public static function sendInvitationMail(string $to, string $name, string $inviteUrl): bool
    {
        if (!App::config('mail.enabled')) {
            Logger::warning('Mail deaktiviert - Einladung nicht per E-Mail versendet', ['to' => $to]);
            return false;
        }

        $appName = App::config('app.name') ?? 'Open-Classbook';
        $subject = 'Ihr Zugang zu ' . $appName;

        $greeting = trim($name) !== '' ? $name : 'Nutzer/in';

        $body  = "Sehr geehrte/r " . $greeting . ",\n\n";
        $body .= "für Sie wurde ein Konto bei " . $appName . " eingerichtet.\n";
        $body .= "Ihr Anmeldename ist Ihre E-Mail-Adresse: " . $to . "\n\n";
        $body .= "Bitte legen Sie über den folgenden Link innerhalb von 7 Tagen Ihr persönliches Passwort fest:\n\n";
        $body .= "    " . $inviteUrl . "\n\n";
        $body .= "Nachdem Sie ein Passwort gesetzt haben, können Sie sich mit Ihrer E-Mail-Adresse anmelden.\n\n";
        $body .= "Falls Sie diese Einladung nicht erwartet haben, ignorieren Sie diese E-Mail.\n\n";
        $body .= "--\nDiese Nachricht wurde automatisch von " . $appName . " versendet.\n";
        $body .= "Bitte antworten Sie nicht auf diese E-Mail.";

        return self::sendMail($to, $subject, $body);
    }

    /**
     * Bestaetigungslink bei Self-Service-E-Mail-Aenderung. Wird an die NEUE
     * Adresse gesendet (Double-Opt-in); erst nach Klick wird die Adresse aktiv.
     */
    public static function sendEmailChangeConfirmationMail(string $to, string $name, string $confirmUrl): bool
    {
        if (!App::config('mail.enabled')) {
            Logger::warning('Mail deaktiviert - E-Mail-Bestätigung nicht versendet', ['to' => $to]);
            return false;
        }

        $appName = App::config('app.name') ?? 'Open-Classbook';
        $subject = 'E-Mail-Adresse bestätigen – ' . $appName;

        $greeting = trim($name) !== '' ? $name : 'Nutzer/in';

        $body  = "Sehr geehrte/r " . $greeting . ",\n\n";
        $body .= "Sie haben angefragt, Ihre E-Mail-Adresse (und damit Ihren Anmeldenamen) bei " . $appName . " auf diese Adresse zu ändern.\n\n";
        $body .= "Bitte bestätigen Sie die Änderung über den folgenden Link:\n\n";
        $body .= "    " . $confirmUrl . "\n\n";
        $body .= "Erst nach der Bestätigung wird die neue Adresse aktiv. Bis dahin gilt Ihre bisherige Adresse.\n\n";
        $body .= "Falls Sie diese Änderung nicht angefordert haben, ignorieren Sie diese E-Mail.\n\n";
        $body .= "--\nDiese Nachricht wurde automatisch von " . $appName . " versendet.\n";
        $body .= "Bitte antworten Sie nicht auf diese E-Mail.";

        return self::sendMail($to, $subject, $body);
    }

    /**
     * Hinweis an die ALTE Adresse, dass eine E-Mail-Aenderung angestossen wurde
     * (Sicherheits-Benachrichtigung). Fehlschlag ist unkritisch.
     */
    public static function sendEmailChangeNoticeMail(string $to, string $name, string $newEmail): bool
    {
        if (!App::config('mail.enabled')) {
            return false;
        }

        $appName = App::config('app.name') ?? 'Open-Classbook';
        $subject = 'Änderung Ihrer E-Mail-Adresse angefordert – ' . $appName;

        $greeting = trim($name) !== '' ? $name : 'Nutzer/in';

        $body  = "Sehr geehrte/r " . $greeting . ",\n\n";
        $body .= "für Ihr Konto bei " . $appName . " wurde eine Änderung der E-Mail-Adresse auf\n";
        $body .= "    " . $newEmail . "\n";
        $body .= "angefordert. Die Änderung wird erst nach Bestätigung über die neue Adresse wirksam.\n\n";
        $body .= "Falls Sie diese Änderung nicht veranlasst haben, wenden Sie sich bitte umgehend an die Administration.\n\n";
        $body .= "--\nDiese Nachricht wurde automatisch von " . $appName . " versendet.\n";
        $body .= "Bitte antworten Sie nicht auf diese E-Mail.";

        return self::sendMail($to, $subject, $body);
    }

    /**
     * E-Mail senden. Ist ein SMTP-Host konfiguriert (mail.host) und PHPMailer
     * verfuegbar, wird ueber authentifiziertes SMTP versendet; andernfalls faellt
     * der Versand auf die native PHP-Funktion mail() zurueck.
     */
    private static function sendMail(string $to, string $subject, string $body): bool
    {
        $fromAddress = App::config('mail.from_address') ?? 'noreply@schule.de';
        $fromName = App::config('mail.from_name') ?? 'Open-Classbook';
        $host = trim((string) (App::config('mail.host') ?? ''));

        // Bevorzugt SMTP via PHPMailer, sofern konfiguriert und verfuegbar
        if ($host !== '' && class_exists(PHPMailer::class)) {
            if (self::sendViaSmtp($to, $subject, $body, $fromAddress, $fromName, $host)) {
                Logger::info('E-Mail via SMTP gesendet', ['to' => $to, 'subject' => $subject]);
                return true;
            }
            // Fehlgeschlagenes SMTP -> als Fallback mail() versuchen
            Logger::warning('SMTP-Versand fehlgeschlagen, Fallback auf mail()', ['to' => $to]);
        }

        return self::sendViaMailFunction($to, $subject, $body, $fromAddress, $fromName);
    }

    /**
     * Versand ueber authentifiziertes SMTP mit PHPMailer.
     */
    private static function sendViaSmtp(string $to, string $subject, string $body, string $fromAddress, string $fromName, string $host): bool
    {
        $mailer = new PHPMailer(true);

        try {
            $mailer->isSMTP();
            $mailer->Host = $host;
            $mailer->Port = (int) (App::config('mail.port') ?? 587);
            $mailer->CharSet = PHPMailer::CHARSET_UTF8;

            $username = (string) (App::config('mail.username') ?? '');
            $password = (string) (App::config('mail.password') ?? '');
            if ($username !== '') {
                $mailer->SMTPAuth = true;
                $mailer->Username = $username;
                $mailer->Password = $password;
            }

            $encryption = strtolower((string) (App::config('mail.encryption') ?? 'tls'));
            if ($encryption === 'ssl') {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mailer->setFrom($fromAddress, $fromName);
            $mailer->addReplyTo($fromAddress, $fromName);
            $mailer->addAddress($to);
            $mailer->Subject = $subject;
            $mailer->Body = $body;
            $mailer->isHTML(false);

            $mailer->send();
            return true;
        } catch (PHPMailerException $e) {
            Logger::error('SMTP-Versand Fehler: ' . $mailer->ErrorInfo, ['to' => $to]);
            return false;
        } catch (\Throwable $e) {
            Logger::error('SMTP-Versand Fehler: ' . $e->getMessage(), ['to' => $to]);
            return false;
        }
    }

    /**
     * Versand ueber die native PHP-Funktion mail() (Fallback).
     */
    private static function sendViaMailFunction(string $to, string $subject, string $body, string $fromAddress, string $fromName): bool
    {
        try {
            $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            $headers = "From: {$fromName} <{$fromAddress}>\r\n";
            $headers .= "Reply-To: {$fromAddress}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            $result = mail($to, $encodedSubject, $body, $headers);

            if ($result) {
                Logger::info('Benachrichtigungs-E-Mail gesendet (mail())', ['to' => $to, 'subject' => $subject]);
            } else {
                Logger::error('E-Mail-Versand fehlgeschlagen', ['to' => $to, 'subject' => $subject]);
            }

            return $result;
        } catch (\Exception $e) {
            Logger::error('E-Mail-Versand Fehler: ' . $e->getMessage(), ['to' => $to]);
            return false;
        }
    }
}
