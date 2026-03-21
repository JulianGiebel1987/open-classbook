<?php

/**
 * Testdaten-Seed-Skript fuer Open-Classbook
 * Erstellt eine Demo-Schule mit Beispieldaten
 *
 * Ausfuehrung: php database/seed.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use OpenClassbook\Database;

echo "=== Open-Classbook Testdaten-Seed ===\n\n";

$pdo = Database::getConnection();

// Bestehende Daten loeschen (Reihenfolge wegen Fremdschluessel)
echo "Bestehende Daten werden geloescht...\n";
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
$tables = ['absences_teachers', 'absences_students', 'classbook_entries', 'class_teacher', 'students', 'classes', 'teachers', 'login_attempts', 'audit_log', 'users'];
foreach ($tables as $table) {
    $pdo->exec("TRUNCATE TABLE $table");
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
echo "Tabellen geleert.\n\n";

// ==========================================
// 1. Benutzer anlegen
// ==========================================
echo "Benutzer werden angelegt...\n";

$users = [
    // Admin
    ['admin', 'admin@demo-schule.de', 'admin', 'Admin2026!x'],
    // Schulleitung
    ['k.schmidt', 'k.schmidt@demo-schule.de', 'schulleitung', 'Leitung2026!'],
    // Sekretariat
    ['s.meyer', 's.meyer@demo-schule.de', 'sekretariat', 'Sekret2026!!'],
    // Lehrer
    ['m.mueller', 'm.mueller@demo-schule.de', 'lehrer', 'Lehrer2026!a'],
    ['a.fischer', 'a.fischer@demo-schule.de', 'lehrer', 'Lehrer2026!b'],
    ['h.weber', 'h.weber@demo-schule.de', 'lehrer', 'Lehrer2026!c'],
    ['l.becker', 'l.becker@demo-schule.de', 'lehrer', 'Lehrer2026!d'],
    ['t.hoffmann', 't.hoffmann@demo-schule.de', 'lehrer', 'Lehrer2026!e'],
];

$userIds = [];
$stmt = $pdo->prepare(
    'INSERT INTO users (username, email, password_hash, role, active, must_change_password) VALUES (?, ?, ?, ?, 1, 0)'
);
foreach ($users as $u) {
    $stmt->execute([$u[0], $u[1], password_hash($u[3], PASSWORD_BCRYPT), $u[2]]);
    $userIds[$u[0]] = (int) $pdo->lastInsertId();
}
echo "  " . count($users) . " Benutzer angelegt.\n";

// ==========================================
// 2. Lehrer anlegen
// ==========================================
echo "Lehrer werden angelegt...\n";

$teachers = [
    ['m.mueller', 'Maria', 'Mueller', 'MU', 'Mathematik, Physik'],
    ['a.fischer', 'Anna', 'Fischer', 'FI', 'Deutsch, Geschichte'],
    ['h.weber', 'Hans', 'Weber', 'WE', 'Englisch, Sport'],
    ['l.becker', 'Lena', 'Becker', 'BE', 'Biologie, Chemie'],
    ['t.hoffmann', 'Thomas', 'Hoffmann', 'HO', 'Kunst, Musik'],
];

$teacherIds = [];
$stmt = $pdo->prepare(
    'INSERT INTO teachers (user_id, firstname, lastname, abbreviation, subjects) VALUES (?, ?, ?, ?, ?)'
);
foreach ($teachers as $t) {
    $stmt->execute([$userIds[$t[0]], $t[1], $t[2], $t[3], $t[4]]);
    $teacherIds[$t[3]] = (int) $pdo->lastInsertId();
}
echo "  " . count($teachers) . " Lehrer angelegt.\n";

// ==========================================
// 3. Klassen anlegen
// ==========================================
echo "Klassen werden angelegt...\n";

$schoolYear = '2025/2026';
$classes = [
    ['5a', $teacherIds['MU']],
    ['5b', $teacherIds['FI']],
    ['6a', $teacherIds['WE']],
    ['7a', $teacherIds['BE']],
    ['8a', $teacherIds['HO']],
];

$classIds = [];
$stmt = $pdo->prepare(
    'INSERT INTO classes (name, school_year, head_teacher_id) VALUES (?, ?, ?)'
);
foreach ($classes as $c) {
    $stmt->execute([$c[0], $schoolYear, $c[1]]);
    $classIds[$c[0]] = (int) $pdo->lastInsertId();
}
echo "  " . count($classes) . " Klassen angelegt.\n";

// ==========================================
// 4. Lehrer-Klassen-Zuordnung
// ==========================================
echo "Lehrer-Klassen-Zuordnungen...\n";

$assignments = [
    ['5a', 'MU'], ['5a', 'FI'], ['5a', 'WE'],
    ['5b', 'FI'], ['5b', 'BE'], ['5b', 'HO'],
    ['6a', 'WE'], ['6a', 'MU'], ['6a', 'BE'],
    ['7a', 'BE'], ['7a', 'HO'], ['7a', 'MU'],
    ['8a', 'HO'], ['8a', 'FI'], ['8a', 'WE'],
];

$stmt = $pdo->prepare('INSERT INTO class_teacher (class_id, teacher_id) VALUES (?, ?)');
foreach ($assignments as $a) {
    $stmt->execute([$classIds[$a[0]], $teacherIds[$a[1]]]);
}
echo "  " . count($assignments) . " Zuordnungen angelegt.\n";

// ==========================================
// 5. Schueler anlegen
// ==========================================
echo "Schueler werden angelegt...\n";

$studentNames = [
    ['Max', 'Braun'], ['Sophie', 'Klein'], ['Leon', 'Wolf'],
    ['Emma', 'Schroeder'], ['Paul', 'Neumann'], ['Mia', 'Schwarz'],
    ['Felix', 'Zimmermann'], ['Hannah', 'Krueger'], ['Lukas', 'Hartmann'],
    ['Lara', 'Lange'], ['Tim', 'Werner'], ['Lea', 'Schmitt'],
    ['Jonas', 'Meier'], ['Marie', 'Krause'], ['Finn', 'Lehmann'],
];

$studentIds = [];
$studentUserIds = [];
$userStmt = $pdo->prepare(
    'INSERT INTO users (username, email, password_hash, role, active, must_change_password) VALUES (?, ?, ?, ?, 1, 1)'
);
$stmt = $pdo->prepare(
    'INSERT INTO students (user_id, firstname, lastname, class_id, birthday, guardian_email) VALUES (?, ?, ?, ?, ?, ?)'
);

$nameIndex = 0;
foreach ($classIds as $className => $classId) {
    $count = ($className === '5a' || $className === '5b') ? 5 : 3;
    for ($i = 0; $i < $count && $nameIndex < count($studentNames); $i++) {
        $n = $studentNames[$nameIndex];
        $birthday = sprintf('20%02d-%02d-%02d', rand(10, 15), rand(1, 12), rand(1, 28));
        $email = strtolower($n[0]) . '.' . strtolower($n[1]) . '.eltern@example.com';

        // User-Account fuer Schueler anlegen
        $username = strtolower(mb_substr($n[0], 0, 1)) . '.' . strtolower($n[1]);
        $password = 'Schueler2026!';
        $userStmt->execute([$username, $email, password_hash($password, PASSWORD_BCRYPT), 'schueler']);
        $sUserId = (int) $pdo->lastInsertId();
        $studentUserIds[$username] = $sUserId;

        $stmt->execute([$sUserId, $n[0], $n[1], $classId, $birthday, $email]);
        $studentIds[] = (int) $pdo->lastInsertId();
        $nameIndex++;
    }
}
echo "  " . count($studentIds) . " Schueler angelegt (mit User-Accounts).\n";

// ==========================================
// 6. Klassenbucheintraege
// ==========================================
echo "Klassenbucheintraege werden angelegt...\n";

$topics = [
    'Einfuehrung in die Bruchrechnung',
    'Gedichtanalyse: Der Erlkoenig',
    'Present Perfect Tense',
    'Photosynthese',
    'Farbenlehre: Primaer- und Sekundaerfarben',
    'Geometrie: Flaechenberechnung',
    'Kurzgeschichte: Analyse und Interpretation',
    'Vokabeltest Unit 5',
    'Oekosystem Wald',
    'Rhythmus und Takt',
];

$entryCount = 0;
$stmt = $pdo->prepare(
    'INSERT INTO classbook_entries (class_id, teacher_id, entry_date, lesson, topic, notes) VALUES (?, ?, ?, ?, ?, ?)'
);

foreach ($classIds as $className => $classId) {
    // Eintraege fuer die letzten 10 Schultage
    for ($day = 1; $day <= 10; $day++) {
        $date = date('Y-m-d', strtotime("-{$day} weekdays"));
        $lessonsPerDay = rand(2, 4);
        for ($lesson = 1; $lesson <= $lessonsPerDay; $lesson++) {
            $teacherAbbr = array_keys($teacherIds)[array_rand(array_keys($teacherIds))];
            $topic = $topics[array_rand($topics)];
            $notes = (rand(0, 1) === 1) ? 'Hausaufgaben bis naechste Stunde' : null;
            $stmt->execute([$classId, $teacherIds[$teacherAbbr], $date, $lesson, $topic, $notes]);
            $entryCount++;
        }
    }
}
echo "  $entryCount Klassenbucheintraege angelegt.\n";

// ==========================================
// 7. Schueler-Fehlzeiten
// ==========================================
echo "Schueler-Fehlzeiten werden angelegt...\n";

$reasons = ['Krank', 'Arzttermin', 'Familiaere Gruende', null];
$excusedOptions = ['ja', 'nein', 'offen'];

$absCount = 0;
$stmt = $pdo->prepare(
    'INSERT INTO absences_students (student_id, date_from, date_to, excused, reason, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)'
);

foreach ($studentIds as $studentId) {
    // Jeder Schueler hat 0-3 Fehlzeiten
    $numAbsences = rand(0, 3);
    for ($i = 0; $i < $numAbsences; $i++) {
        $daysAgo = rand(1, 30);
        $duration = rand(1, 3);
        $dateFrom = date('Y-m-d', strtotime("-{$daysAgo} days"));
        $dateTo = date('Y-m-d', strtotime("-" . ($daysAgo - $duration) . " days"));
        $excused = $excusedOptions[array_rand($excusedOptions)];
        $reason = $reasons[array_rand($reasons)];

        $stmt->execute([$studentId, $dateFrom, $dateTo, $excused, $reason, null, $userIds['admin']]);
        $absCount++;
    }
}
echo "  $absCount Schueler-Fehlzeiten angelegt.\n";

// ==========================================
// 8. Lehrer-Fehlzeiten
// ==========================================
echo "Lehrer-Fehlzeiten werden angelegt...\n";

$types = ['krank', 'fortbildung', 'sonstiges'];

$teacherAbsCount = 0;
$stmt = $pdo->prepare(
    'INSERT INTO absences_teachers (teacher_id, date_from, date_to, type, reason, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)'
);

// 2-3 Lehrer haben Fehlzeiten
$absentTeachers = array_rand($teacherIds, min(3, count($teacherIds)));
if (!is_array($absentTeachers)) {
    $absentTeachers = [$absentTeachers];
}

foreach ($absentTeachers as $abbr) {
    $tId = $teacherIds[$abbr];
    $numAbsences = rand(1, 2);
    for ($i = 0; $i < $numAbsences; $i++) {
        $daysAgo = rand(1, 20);
        $duration = rand(1, 5);
        $dateFrom = date('Y-m-d', strtotime("-{$daysAgo} days"));
        $dateTo = date('Y-m-d', strtotime("-" . max(0, $daysAgo - $duration) . " days"));
        $type = $types[array_rand($types)];
        $reason = ($type === 'fortbildung') ? 'Fortbildung: Digitale Medien im Unterricht' : null;

        $stmt->execute([$tId, $dateFrom, $dateTo, $type, $reason, null, $userIds['admin']]);
        $teacherAbsCount++;
    }
}
echo "  $teacherAbsCount Lehrer-Fehlzeiten angelegt.\n";

// ==========================================
// Zusammenfassung
// ==========================================
echo "\n=== Seed abgeschlossen! ===\n\n";
echo "Erstellt:\n";
echo "  - " . count($users) . " Benutzer\n";
echo "  - " . count($teachers) . " Lehrer\n";
echo "  - " . count($classes) . " Klassen\n";
echo "  - " . count($assignments) . " Lehrer-Klassen-Zuordnungen\n";
echo "  - " . count($studentIds) . " Schueler\n";
echo "  - $entryCount Klassenbucheintraege\n";
echo "  - $absCount Schueler-Fehlzeiten\n";
echo "  - $teacherAbsCount Lehrer-Fehlzeiten\n";
echo "\nLogin-Daten:\n";
echo "  Admin:        admin / Admin2026!x\n";
echo "  Schulleitung: k.schmidt / Leitung2026!\n";
echo "  Sekretariat:  s.meyer / Sekret2026!!\n";
echo "  Lehrer:       m.mueller / Lehrer2026!a\n";
echo "  (weitere Lehrer: a.fischer, h.weber, l.becker, t.hoffmann)\n";
echo "  Schueler:     m.braun / Schueler2026!\n";
echo "  (weitere Schueler: s.klein, l.wolf, e.schroeder, p.neumann, ...)\n";
