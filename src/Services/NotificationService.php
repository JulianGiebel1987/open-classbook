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
     * Empfaenger fuer Abwesenheits-Benachrichtigungen: Schulleitung und Sekretariat
     */
    private static function getNotificationRecipients(): array
    {
        return Database::query(
            "SELECT email FROM users WHERE role IN ('schulleitung', 'sekretariat') AND active = 1 AND email IS NOT NULL AND email != ''"
        );
    }

    /**
     * E-Mail senden via PHPMailer oder mail()
     */
    private static function sendMail(string $to, string $subject, string $body): void
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
        } catch (\Exception $e) {
            Logger::error('E-Mail-Versand Fehler: ' . $e->getMessage(), ['to' => $to]);
        }
    }
}
