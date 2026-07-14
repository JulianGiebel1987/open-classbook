<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Models\Setting;
use OpenClassbook\Services\ModuleSettings;
use OpenClassbook\Services\RetentionService;
use OpenClassbook\Services\Logger;
use OpenClassbook\Middleware\CsrfMiddleware;

class SettingsController
{
    /**
     * Aufbewahrungsfristen mit Default-Werten (Fallback auf config('security.*')).
     */
    private const RETENTION_KEYS = [
        'retention_messages_days'       => 730,
        'retention_audit_days'          => 90,
        'retention_login_attempts_days' => 30,
    ];

    public function index(): void
    {
        if (App::currentUserRole() !== 'admin') {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        CsrfMiddleware::generateToken();

        $settings = Setting::getMultiple([
            'two_factor_enabled',
            'two_factor_enforce_roles',
            'two_factor_code_lifetime',
            'two_factor_max_attempts',
            // Global modules
            'module_timetable',
            'module_substitution',
            'module_messages',
            'module_lists',
            'module_files',
            'module_templates',
            'module_school_aides',
            // Role-specific modules
            'module_teacher_absences_schulleitung',
            'module_teacher_absences_sekretariat',
            'module_timetable_schulleitung',
            'module_timetable_sekretariat',
            'module_substitution_schulleitung',
            'module_substitution_sekretariat',
            'module_school_aides_schulleitung',
            'module_school_aides_sekretariat',
            // Retention / Löschkonzept
            'retention_messages_days',
            'retention_audit_days',
            'retention_login_attempts_days',
        ]);

        // Effektive Aufbewahrungsfristen: DB-Wert, sonst config, sonst Default.
        foreach (self::RETENTION_KEYS as $key => $default) {
            if (!isset($settings[$key]) || $settings[$key] === '') {
                $settings[$key] = (string) (App::config('security.' . $key) ?? $default);
            }
        }

        $enforceRoles = json_decode($settings['two_factor_enforce_roles'] ?? '[]', true) ?: [];

        View::render('settings/index', [
            'title'       => 'Einstellungen',
            'settings'    => $settings,
            'enforceRoles'=> $enforceRoles,
            'allRoles'    => ['admin', 'schulleitung', 'sekretariat', 'lehrer'],
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Einstellungen'],
            ]),
        ]);
    }

    public function save(): void
    {
        if (App::currentUserRole() !== 'admin') {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        // --- 2FA settings ---
        $twoFactorEnabled = isset($_POST['two_factor_enabled']) ? '1' : '0';
        Setting::set('two_factor_enabled', $twoFactorEnabled);

        $enforceRoles = $_POST['two_factor_enforce_roles'] ?? [];
        $validRoles   = ['admin', 'schulleitung', 'sekretariat', 'lehrer'];
        $enforceRoles = array_intersect($enforceRoles, $validRoles);
        Setting::set('two_factor_enforce_roles', json_encode(array_values($enforceRoles)));

        $codeLifetime = (int) ($_POST['two_factor_code_lifetime'] ?? 600);
        $codeLifetime = max(60, min(3600, $codeLifetime));
        Setting::set('two_factor_code_lifetime', (string) $codeLifetime);

        $maxAttempts = (int) ($_POST['two_factor_max_attempts'] ?? 5);
        $maxAttempts = max(3, min(20, $maxAttempts));
        Setting::set('two_factor_max_attempts', (string) $maxAttempts);

        // --- Global module toggles ---
        $globalModules = ['timetable', 'substitution', 'messages', 'lists', 'files', 'templates', 'school_aides'];
        foreach ($globalModules as $module) {
            $key   = 'module_' . $module;
            $value = isset($_POST[$key]) ? '1' : '0';
            Setting::set($key, $value);
        }

        // --- Role-specific module access ---
        $roleModuleKeys = [
            'module_teacher_absences_schulleitung',
            'module_teacher_absences_sekretariat',
            'module_timetable_schulleitung',
            'module_timetable_sekretariat',
            'module_substitution_schulleitung',
            'module_substitution_sekretariat',
            'module_school_aides_schulleitung',
            'module_school_aides_sekretariat',
        ];
        foreach ($roleModuleKeys as $key) {
            $value = isset($_POST[$key]) ? '1' : '0';
            Setting::set($key, $value);
        }

        // --- Retention / Löschkonzept ---
        foreach (self::RETENTION_KEYS as $key => $default) {
            $days = (int) ($_POST[$key] ?? $default);
            // 0 = deaktiviert; obere Grenze 3650 Tage (10 Jahre)
            $days = max(0, min(3650, $days));
            Setting::set($key, (string) $days);
        }

        ModuleSettings::flush();

        App::setFlash('success', 'Einstellungen wurden gespeichert.');
        App::redirect('/settings');
    }

    /**
     * Löschroutinen (Aufbewahrungsfristen) manuell ausführen.
     */
    public function runRetention(): void
    {
        if (App::currentUserRole() !== 'admin') {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        try {
            $result = RetentionService::purge();
            $total = array_sum($result);
            App::setFlash('success', sprintf(
                'Bereinigung abgeschlossen: %d Nachricht(en) 1:1, %d Gruppen-Nachricht(en), %d Audit-Einträge, %d Login-Versuche entfernt (insgesamt %d Datensätze).',
                $result['messages'],
                $result['group_messages'],
                $result['audit_log'],
                $result['login_attempts'],
                $total
            ));
        } catch (\Throwable $e) {
            Logger::error('Manuelle Retention-Bereinigung fehlgeschlagen: ' . $e->getMessage());
            App::setFlash('error', 'Bereinigung fehlgeschlagen. Details siehe Server-Log.');
        }

        App::redirect('/settings');
    }
}
