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
    ],
    'lehrer' => [
        ['label' => 'Dashboard', 'url' => '/dashboard'],
        ['label' => 'Meine Klassen', 'url' => '/classbook'],
        ['label' => 'Krankmeldung', 'url' => '/absences/teachers/self'],
    ],
    'schueler' => [
        ['label' => 'Dashboard', 'url' => '/dashboard'],
    ],
];
