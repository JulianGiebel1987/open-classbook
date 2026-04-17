<?php

/**
 * Navigation nach Rolle.
 *
 * Optional keys per item:
 *   'module'      => global module name (checked via ModuleSettings::isModuleEnabled)
 *   'role_module' => role-specific module name (checked via ModuleSettings::isRoleModuleAccessible)
 *
 * Admin bypasses global module checks but still obeys role_module restrictions (none defined for admin).
 */
return [
    'admin' => [
        ['label' => 'Dashboard',        'url' => '/dashboard'],
        ['label' => 'Benutzer',         'url' => '/users'],
        ['label' => 'Klassenverwaltung','url' => '/classes'],
        ['label' => 'Stundenplanung',   'url' => '/timetable',            'module' => 'timetable'],
        ['label' => 'Vertretung',       'url' => '/substitution',         'module' => 'substitution'],
        ['label' => 'Schüler:innen-Fehlzeiten','url' => '/absences/students'],
        ['label' => 'Nachrichten',      'url' => '/messages',             'module' => 'messages'],
        ['label' => 'Listen',           'url' => '/lists',                'module' => 'lists'],
        ['label' => 'Dateien',          'url' => '/files',                'module' => 'files'],
        ['label' => 'Vorlagen',         'url' => '/zeugnis/templates',    'module' => 'templates'],
        ['label' => 'Einstellungen',    'url' => '/settings'],
    ],
    'schulleitung' => [
        ['label' => 'Dashboard',           'url' => '/dashboard'],
        ['label' => 'Benutzer',            'url' => '/users'],
        ['label' => 'Klassenverwaltung',   'url' => '/classes'],
        ['label' => 'Stundenplanung',      'url' => '/timetable',            'module' => 'timetable',    'role_module' => 'timetable'],
        ['label' => 'Vertretung',          'url' => '/substitution',         'module' => 'substitution', 'role_module' => 'substitution'],
        ['label' => 'Schüler:innen-Fehlzeiten',   'url' => '/absences/students'],
        ['label' => 'Lehrkraft-Abwesenheiten', 'url' => '/absences/teachers',   'role_module' => 'teacher_absences'],
        ['label' => 'Nachrichten',         'url' => '/messages',             'module' => 'messages'],
        ['label' => 'Listen',              'url' => '/lists',                'module' => 'lists'],
        ['label' => 'Dateien',             'url' => '/files',                'module' => 'files'],
        ['label' => 'Vorlagen',            'url' => '/zeugnis/templates',    'module' => 'templates'],
    ],
    'sekretariat' => [
        ['label' => 'Dashboard',           'url' => '/dashboard'],
        ['label' => 'Benutzer',            'url' => '/users'],
        ['label' => 'Klassenverwaltung',   'url' => '/classes'],
        ['label' => 'Stundenplanung',      'url' => '/timetable',            'module' => 'timetable',    'role_module' => 'timetable'],
        ['label' => 'Vertretung',          'url' => '/substitution',         'module' => 'substitution', 'role_module' => 'substitution'],
        ['label' => 'Schüler:innen-Fehlzeiten',   'url' => '/absences/students'],
        ['label' => 'Lehrkraft-Abwesenheiten', 'url' => '/absences/teachers',   'role_module' => 'teacher_absences'],
        ['label' => 'Nachrichten',         'url' => '/messages',             'module' => 'messages'],
        ['label' => 'Listen',              'url' => '/lists',                'module' => 'lists'],
        ['label' => 'Dateien',             'url' => '/files',                'module' => 'files'],
        ['label' => 'Vorlagen',            'url' => '/zeugnis/templates',    'module' => 'templates'],
    ],
    'lehrer' => [
        ['label' => 'Dashboard',       'url' => '/dashboard'],
        ['label' => 'Meine Klassen',   'url' => '/classbook'],
        ['label' => 'Mein Stundenplan','url' => '/timetable/my-schedule',   'module' => 'timetable'],
        ['label' => 'Vertretung',      'url' => '/substitution/my-substitutions', 'module' => 'substitution'],
        ['label' => 'Krankmeldung',    'url' => '/absences/teachers/self'],
        ['label' => 'Nachrichten',     'url' => '/messages',                'module' => 'messages'],
        ['label' => 'Listen',          'url' => '/lists',                   'module' => 'lists'],
        ['label' => 'Dateien',         'url' => '/files',                   'module' => 'files'],
        ['label' => 'Vorlagen',        'url' => '/zeugnis',                 'module' => 'templates'],
    ],
    'schueler' => [
        ['label' => 'Dashboard',       'url' => '/dashboard'],
        ['label' => 'Krankmeldung',    'url' => '/absences/students/self'],
        ['label' => 'Meine Fehlzeiten','url' => '/absences/students/mine'],
        ['label' => 'Nachrichten',     'url' => '/messages',                'module' => 'messages'],
    ],
];
