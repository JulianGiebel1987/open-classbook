<?php

namespace OpenClassbook\Services;

use OpenClassbook\Models\Setting;

/**
 * Module access control helper.
 *
 * Global module keys (enabled/disabled for all roles):
 *   module_timetable, module_substitution, module_messages,
 *   module_lists, module_files, module_templates
 *
 * Role-specific access keys (schulleitung / sekretariat):
 *   module_teacher_absences_schulleitung, module_teacher_absences_sekretariat
 *   module_timetable_schulleitung,        module_timetable_sekretariat
 *   module_substitution_schulleitung,     module_substitution_sekretariat
 *
 * Default: all enabled (1). Stored as '0' / '1' in settings table.
 */
class ModuleSettings
{
    /** Global toggles: module name => setting key */
    public const GLOBAL_MODULES = [
        'timetable'   => 'module_timetable',
        'substitution'=> 'module_substitution',
        'messages'    => 'module_messages',
        'lists'       => 'module_lists',
        'files'       => 'module_files',
        'templates'   => 'module_templates',
    ];

    /** Role-specific access: [module][role] => setting key */
    public const ROLE_MODULES = [
        'teacher_absences' => [
            'schulleitung' => 'module_teacher_absences_schulleitung',
            'sekretariat'  => 'module_teacher_absences_sekretariat',
        ],
        'timetable' => [
            'schulleitung' => 'module_timetable_schulleitung',
            'sekretariat'  => 'module_timetable_sekretariat',
        ],
        'substitution' => [
            'schulleitung' => 'module_substitution_schulleitung',
            'sekretariat'  => 'module_substitution_sekretariat',
        ],
    ];

    private static ?array $cache = null;

    /**
     * Load all relevant settings once per request.
     */
    private static function load(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $keys = array_values(self::GLOBAL_MODULES);
        foreach (self::ROLE_MODULES as $roles) {
            foreach ($roles as $key) {
                $keys[] = $key;
            }
        }

        self::$cache = Setting::getMultiple($keys);
        return self::$cache;
    }

    /**
     * Check if a global module is enabled.
     * Unknown modules are considered enabled.
     */
    public static function isModuleEnabled(string $module): bool
    {
        $key = self::GLOBAL_MODULES[$module] ?? null;
        if ($key === null) {
            return true;
        }
        $value = self::load()[$key] ?? '1';
        return $value !== '0';
    }

    /**
     * Check if a role-specific module is accessible for a given role.
     * Only applies to 'schulleitung' and 'sekretariat'.
     * Admin always has access. Other roles are not restricted here.
     */
    public static function isRoleModuleAccessible(string $module, string $role): bool
    {
        if ($role === 'admin') {
            return true;
        }

        $key = self::ROLE_MODULES[$module][$role] ?? null;
        if ($key === null) {
            return true;
        }
        $value = self::load()[$key] ?? '1';
        return $value !== '0';
    }

    /**
     * Convenience: check both global enable and role-specific access.
     * For admin the global check is skipped (admin always sees the module).
     */
    public static function canAccess(string $module, string $role): bool
    {
        if ($role !== 'admin' && !self::isModuleEnabled($module)) {
            return false;
        }

        return self::isRoleModuleAccessible($module, $role);
    }

    /**
     * Flush the in-memory cache (useful after saving settings).
     */
    public static function flush(): void
    {
        self::$cache = null;
    }
}
