<?php

namespace OpenClassbook\Services;

use OpenClassbook\App;
use OpenClassbook\Database;

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
            Logger::warning('Keine Empfaenger fuer Krankmeldungs-Benachrichtigung gefunden.');
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
     * Temporaeres Passwort per E-Mail an den betroffenen Nutzer senden
     */
    public static function sendTemporaryPasswordMail(string $to, string $username, string $tempPassword): bool
    {
        if (!App::config('mail.enabled')) {
            Logger::warning('Mail deaktiviert - temporaeres Passwort nicht per E-Mail versendet', ['to' => $to]);
            return false;
        }

        $subject = 'Ihre Zugangsdaten fuer Open-Classbook';

        $body  = "Sehr geehrte/r " . $username . ",\n\n";
        $body .= "Ihr Passwort in Open-Classbook wurde zurueckgesetzt.\n\n";
        $body .= "Ihre neuen Zugangsdaten:\n";
        $body .= "  Benutzername: " . $username . "\n";
        $body .= "  Passwort:     " . $tempPassword . "\n\n";
        $body .= "Bitte melden Sie sich an und aendern Sie das Passwort sofort beim ersten Login.\n\n";
        $body .= "--\nDiese Nachricht wurde automatisch von Open-Classbook versendet.\n";
        $body .= "Bitte antworten Sie nicht auf diese E-Mail.";

        return self::sendMail($to, $subject, $body);
    }

    /**
     * Empfaenger fuer Abwesenheits-Benachrichtigungen: Schulleitung und Sekretariat
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

        $subject = 'Ihr Anmeldecode fuer Open-Classbook';

        $body  = "Sehr geehrte/r Nutzer/in,\n\n";
        $body .= "Ihr Verifizierungscode fuer die Anmeldung bei Open-Classbook lautet:\n\n";
        $body .= "    " . $code . "\n\n";
        $body .= "Dieser Code ist 10 Minuten gueltig.\n\n";
        $body .= "Falls Sie diese Anmeldung nicht selbst durchgefuehrt haben, ignorieren Sie diese E-Mail.\n\n";
        $body .= "--\nDiese Nachricht wurde automatisch von Open-Classbook versendet.\n";
        $body .= "Bitte antworten Sie nicht auf diese E-Mail.";

        return self::sendMail($to, $subject, $body);
    }

    /**
     * E-Mail senden via PHPMailer oder mail()
     */
    private static function sendMail(string $to, string $subject, string $body): bool
    {
        try {
            $fromAddress = App::config('mail.from_address') ?? 'noreply@schule.de';
            $fromName = App::config('mail.from_name') ?? 'Open-Classbook';

            $headers = "From: {$fromName} <{$fromAddress}>\r\n";
            $headers .= "Reply-To: {$fromAddress}\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            $result = mail($to, $subject, $body, $headers);

            if ($result) {
                Logger::info('Benachrichtigungs-E-Mail gesendet', ['to' => $to, 'subject' => $subject]);
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
