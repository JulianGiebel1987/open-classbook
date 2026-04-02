<?php

/**
 * Navigation nach Rolle
 */
return [
    'admin' => [
        ['label' => 'Dashboard', 'url' => '/dashboard'],
        ['label' => 'Benutzer', 'url' => '/users'],
        ['label' => 'Klassenverwaltung', 'url' => '/classes'],
        ['label' => 'Stundenplanung', 'url' => '/timetable'],
        ['label' => 'Vertretung', 'url' => '/substitution'],
        ['label' => 'Schülerfehlzeiten', 'url' => '/absences/students'],
        ['label' => 'Nachrichten', 'url' => '/messages'],
        ['label' => 'Listen', 'url' => '/lists'],
        ['label' => 'Dateien', 'url' => '/files'],
        ['label' => 'Vorlagen', 'url' => '/zeugnis/templates'],
        ['label' => 'Einstellungen', 'url' => '/settings'],
    ],
    'schulleitung' => [
        ['label' => 'Dashboard', 'url' => '/dashboard'],
        ['label' => 'Benutzer', 'url' => '/users'],
        ['label' => 'Klassenverwaltung', 'url' => '/classes'],
        ['label' => 'Stundenplanung', 'url' => '/timetable'],
        ['label' => 'Vertretung', 'url' => '/substitution'],
        ['label' => 'Schülerfehlzeiten', 'url' => '/absences/students'],
        ['label' => 'Nachrichten', 'url' => '/messages'],
        ['label' => 'Listen', 'url' => '/lists'],
        ['label' => 'Dateien', 'url' => '/files'],
        ['label' => 'Vorlagen', 'url' => '/zeugnis/templates'],
    ],
    'sekretariat' => [
        ['label' => 'Dashboard', 'url' => '/dashboard'],
        ['label' => 'Benutzer', 'url' => '/users'],
        ['label' => 'Klassenverwaltung', 'url' => '/classes'],
        ['label' => 'Stundenplanung', 'url' => '/timetable'],
        ['label' => 'Vertretung', 'url' => '/substitution'],
        ['label' => 'Schülerfehlzeiten', 'url' => '/absences/students'],
        ['label' => 'Nachrichten', 'url' => '/messages'],
        ['label' => 'Listen', 'url' => '/lists'],
        ['label' => 'Dateien', 'url' => '/files'],
        ['label' => 'Vorlagen', 'url' => '/zeugnis/templates'],
    ],
    'lehrer' => [
        ['label' => 'Dashboard', 'url' => '/dashboard'],
        ['label' => 'Meine Klassen', 'url' => '/classbook'],
        ['label' => 'Mein Stundenplan', 'url' => '/timetable/my-schedule'],
        ['label' => 'Vertretung', 'url' => '/substitution/my-substitutions'],
        ['label' => 'Krankmeldung', 'url' => '/absences/teachers/self'],
        ['label' => 'Nachrichten', 'url' => '/messages'],
        ['label' => 'Listen', 'url' => '/lists'],
        ['label' => 'Dateien', 'url' => '/files'],
        ['label' => 'Vorlagen', 'url' => '/zeugnis'],
    ],
    'schueler' => [
        ['label' => 'Dashboard', 'url' => '/dashboard'],
        ['label' => 'Krankmeldung', 'url' => '/absences/students/self'],
        ['label' => 'Meine Fehlzeiten', 'url' => '/absences/students/mine'],
        ['label' => 'Nachrichten', 'url' => '/messages'],
    ],
];
