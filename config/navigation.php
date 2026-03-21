<?php

/**
 * Navigation nach Rolle
 */
return [
    'admin' => [
        ['label' => 'Dashboard', 'url' => '/dashboard'],
        ['label' => 'Benutzer', 'url' => '/users'],
        ['label' => 'Klassen', 'url' => '/classes'],
        ['label' => 'Klassenbuch', 'url' => '/classbook'],
        ['label' => 'Fehlzeiten', 'url' => '/absences/students'],
        ['label' => 'Lehrer-Abwesenheit', 'url' => '/absences/teachers'],
        ['label' => 'Import', 'url' => '/import'],
    ],
    'schulleitung' => [
        ['label' => 'Dashboard', 'url' => '/dashboard'],
        ['label' => 'Benutzer', 'url' => '/users'],
        ['label' => 'Klassen', 'url' => '/classes'],
        ['label' => 'Klassenbuch', 'url' => '/classbook'],
        ['label' => 'Fehlzeiten', 'url' => '/absences/students'],
        ['label' => 'Lehrer-Abwesenheit', 'url' => '/absences/teachers'],
    ],
    'sekretariat' => [
        ['label' => 'Dashboard', 'url' => '/dashboard'],
        ['label' => 'Benutzer', 'url' => '/users'],
        ['label' => 'Klassen', 'url' => '/classes'],
        ['label' => 'Klassenbuch', 'url' => '/classbook'],
        ['label' => 'Fehlzeiten', 'url' => '/absences/students'],
        ['label' => 'Lehrer-Abwesenheit', 'url' => '/absences/teachers'],
        ['label' => 'Import', 'url' => '/import'],
    ],
    'lehrer' => [
        ['label' => 'Dashboard', 'url' => '/dashboard'],
        ['label' => 'Meine Klassen', 'url' => '/classbook'],
        ['label' => 'Fehlzeiten', 'url' => '/absences/students'],
        ['label' => 'Krankmeldung', 'url' => '/absences/teachers/self'],
    ],
    'schueler' => [
        ['label' => 'Dashboard', 'url' => '/dashboard'],
        ['label' => 'Krankmeldung', 'url' => '/absences/students/self'],
        ['label' => 'Meine Fehlzeiten', 'url' => '/absences/students/mine'],
    ],
];
