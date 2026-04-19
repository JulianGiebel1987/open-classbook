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
        ['label' => 'Dashboard',        'url' => '/dashboard',            'icon' => 'dashboard'],
        ['label' => 'Benutzer',         'url' => '/users',                'icon' => 'users'],
        ['label' => 'Klassenverwaltung','url' => '/classes',              'icon' => 'classes'],
        ['label' => 'Stundenplanung',   'url' => '/timetable',            'icon' => 'timetable',   'module' => 'timetable'],
        ['label' => 'Vertretung',       'url' => '/substitution',         'icon' => 'substitution','module' => 'substitution'],
        ['label' => 'Schüler:innen-Fehlzeiten','url' => '/absences/students', 'icon' => 'absences-students'],
        ['label' => 'Nachrichten',      'url' => '/messages',             'icon' => 'messages',    'module' => 'messages'],
        ['label' => 'Listen',           'url' => '/lists',                'icon' => 'lists',       'module' => 'lists'],
        ['label' => 'Dateien',          'url' => '/files',                'icon' => 'files',       'module' => 'files'],
        ['label' => 'Vorlagen',         'url' => '/zeugnis/templates',    'icon' => 'templates',   'module' => 'templates'],
        ['label' => 'Einstellungen',    'url' => '/settings',             'icon' => 'settings'],
    ],
    'schulleitung' => [
        ['label' => 'Dashboard',           'url' => '/dashboard',         'icon' => 'dashboard'],
        ['label' => 'Benutzer',            'url' => '/users',             'icon' => 'users'],
        ['label' => 'Klassenverwaltung',   'url' => '/classes',           'icon' => 'classes'],
        ['label' => 'Stundenplanung',      'url' => '/timetable',         'icon' => 'timetable',   'module' => 'timetable',    'role_module' => 'timetable'],
        ['label' => 'Vertretung',          'url' => '/substitution',      'icon' => 'substitution','module' => 'substitution', 'role_module' => 'substitution'],
        ['label' => 'Schüler:innen-Fehlzeiten',   'url' => '/absences/students', 'icon' => 'absences-students'],
        ['label' => 'Lehrkraft-Abwesenheiten', 'url' => '/absences/teachers', 'icon' => 'absences-teachers', 'role_module' => 'teacher_absences'],
        ['label' => 'Nachrichten',         'url' => '/messages',          'icon' => 'messages',    'module' => 'messages'],
        ['label' => 'Listen',              'url' => '/lists',             'icon' => 'lists',       'module' => 'lists'],
        ['label' => 'Dateien',             'url' => '/files',             'icon' => 'files',       'module' => 'files'],
        ['label' => 'Vorlagen',            'url' => '/zeugnis/templates', 'icon' => 'templates',   'module' => 'templates'],
    ],
    'sekretariat' => [
        ['label' => 'Dashboard',           'url' => '/dashboard',         'icon' => 'dashboard'],
        ['label' => 'Benutzer',            'url' => '/users',             'icon' => 'users'],
        ['label' => 'Klassenverwaltung',   'url' => '/classes',           'icon' => 'classes'],
        ['label' => 'Stundenplanung',      'url' => '/timetable',         'icon' => 'timetable',   'module' => 'timetable',    'role_module' => 'timetable'],
        ['label' => 'Vertretung',          'url' => '/substitution',      'icon' => 'substitution','module' => 'substitution', 'role_module' => 'substitution'],
        ['label' => 'Schüler:innen-Fehlzeiten',   'url' => '/absences/students', 'icon' => 'absences-students'],
        ['label' => 'Lehrkraft-Abwesenheiten', 'url' => '/absences/teachers', 'icon' => 'absences-teachers', 'role_module' => 'teacher_absences'],
        ['label' => 'Nachrichten',         'url' => '/messages',          'icon' => 'messages',    'module' => 'messages'],
        ['label' => 'Listen',              'url' => '/lists',             'icon' => 'lists',       'module' => 'lists'],
        ['label' => 'Dateien',             'url' => '/files',             'icon' => 'files',       'module' => 'files'],
        ['label' => 'Vorlagen',            'url' => '/zeugnis/templates', 'icon' => 'templates',   'module' => 'templates'],
    ],
    'lehrer' => [
        ['label' => 'Dashboard',       'url' => '/dashboard',                      'icon' => 'dashboard'],
        ['label' => 'Meine Klassen',   'url' => '/classbook',                      'icon' => 'classbook'],
        ['label' => 'Mein Stundenplan','url' => '/timetable/my-schedule',          'icon' => 'timetable',   'module' => 'timetable'],
        ['label' => 'Vertretung',      'url' => '/substitution/my-substitutions',  'icon' => 'substitution','module' => 'substitution'],
        ['label' => 'Lehrkraft-Abwesenheit', 'url' => '/absences/teachers/self',    'icon' => 'sick-note'],
        ['label' => 'Nachrichten',     'url' => '/messages',                       'icon' => 'messages',    'module' => 'messages'],
        ['label' => 'Listen',          'url' => '/lists',                          'icon' => 'lists',       'module' => 'lists'],
        ['label' => 'Dateien',         'url' => '/files',                          'icon' => 'files',       'module' => 'files'],
        ['label' => 'Vorlagen',        'url' => '/zeugnis',                        'icon' => 'templates',   'module' => 'templates'],
    ],
    'schueler' => [
        ['label' => 'Dashboard',       'url' => '/dashboard',               'icon' => 'dashboard'],
        ['label' => 'Krankmeldung',    'url' => '/absences/students/self',  'icon' => 'sick-note'],
        ['label' => 'Meine Fehlzeiten','url' => '/absences/students/mine',  'icon' => 'my-absences'],
        ['label' => 'Nachrichten',     'url' => '/messages',                'icon' => 'messages',  'module' => 'messages'],
    ],
];
