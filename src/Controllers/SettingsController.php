<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Models\Setting;
use OpenClassbook\Services\ModuleSettings;
use OpenClassbook\Middleware\CsrfMiddleware;

class SettingsController
{
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
            // Role-specific modules
            'module_teacher_absences_schulleitung',
            'module_teacher_absences_sekretariat',
            'module_timetable_schulleitung',
            'module_timetable_sekretariat',
            'module_substitution_schulleitung',
            'module_substitution_sekretariat',
        ]);

        $enforceRoles = json_decode($settings['two_factor_enforce_roles'] ?? '[]', true) ?: [];

        View::render('settings/index', [
            'title'       => 'Einstellungen',
            'settings'    => $settings,
            'enforceRoles'=> $enforceRoles,
            'allRoles'    => ['admin', 'schulleitung', 'sekretariat', 'lehrer'],
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
        $globalModules = ['timetable', 'substitution', 'messages', 'lists', 'files', 'templates'];
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
        ];
        foreach ($roleModuleKeys as $key) {
            $value = isset($_POST[$key]) ? '1' : '0';
            Setting::set($key, $value);
        }

        ModuleSettings::flush();

        App::setFlash('success', 'Einstellungen wurden gespeichert.');
        App::redirect('/settings');
    }
}
