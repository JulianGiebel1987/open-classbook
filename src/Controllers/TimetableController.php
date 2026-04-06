<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\SchoolClass;
use OpenClassbook\Models\Teacher;
use OpenClassbook\Models\TimetableSetting;
use OpenClassbook\Models\TimetableSlot;
use OpenClassbook\Services\Logger;
use OpenClassbook\Services\ModuleSettings;

class TimetableController
{
    private const ADMIN_ROLES = ['admin', 'schulleitung', 'sekretariat'];

    /**
     * Uebersicht aller Stundenplan-Konfigurationen.
     */
    public function index(): void
    {
        $this->requireAdminRole();

        $settings = TimetableSetting::findAll();
        // days_of_week + breaks JSON dekodieren
        foreach ($settings as &$s) {
            $s['days_of_week'] = json_decode($s['days_of_week'], true) ?: [];
            $s['breaks'] = json_decode($s['breaks'] ?? 'null', true) ?: [];
        }
        unset($s);

        View::render('timetable/index', [
            'title' => 'Stundenplanung',
            'settings' => $settings,
        ]);
    }

    /**
     * Formular: Raster-Konfiguration erstellen/bearbeiten.
     */
    public function settingsForm(): void
    {
        $this->requireAdminRole();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $setting = $id ? TimetableSetting::findById($id) : null;

        if ($setting) {
            $setting['days_of_week'] = json_decode($setting['days_of_week'], true) ?: [];
            $setting['breaks'] = json_decode($setting['breaks'] ?? 'null', true) ?: [];
        }

        CsrfMiddleware::generateToken();
        View::render('timetable/settings', [
            'title' => $setting ? 'Stundenplan bearbeiten' : 'Neuer Stundenplan',
            'setting' => $setting,
        ]);
    }

    /**
     * Raster-Konfiguration speichern.
     */
    public function saveSettings(): void
    {
        $this->requireAdminRole();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : null;
        $data = [
            'school_year' => trim($_POST['school_year'] ?? ''),
            'unit_duration' => (int) ($_POST['unit_duration'] ?? 45),
            'units_per_day' => (int) ($_POST['units_per_day'] ?? 8),
            'day_start_time' => $_POST['day_start_time'] ?? '08:00',
            'days_of_week' => array_map('intval', $_POST['days_of_week'] ?? [1, 2, 3, 4, 5]),
            'breaks' => $this->parseBreaksInput($_POST),
            'created_by' => $_SESSION['user_id'],
        ];

        // Validierung
        $errors = [];
        if (empty($data['school_year']) || !preg_match('/^\d{4}\/\d{4}$/', $data['school_year'])) {
            $errors[] = 'Schuljahr muss im Format JJJJ/JJJJ angegeben werden (z.B. 2025/2026).';
        }
        if (!in_array($data['unit_duration'], [30, 45, 60])) {
            $errors[] = 'Einheitsdauer muss 30, 45 oder 60 Minuten sein.';
        }
        if ($data['units_per_day'] < 1 || $data['units_per_day'] > 15) {
            $errors[] = 'Anzahl Einheiten pro Tag muss zwischen 1 und 15 liegen.';
        }
        if (empty($data['days_of_week'])) {
            $errors[] = 'Mindestens ein Wochentag muss ausgewaehlt werden.';
        }

        // Pausen validieren
        $seenAfterSlots = [];
        foreach ($data['breaks'] as $brk) {
            if ($brk['after_slot'] < 1 || $brk['after_slot'] >= $data['units_per_day']) {
                $errors[] = 'Pause nach Einheit ' . $brk['after_slot'] . ' ist ungueltig (muss zwischen 1 und ' . ($data['units_per_day'] - 1) . ' liegen).';
            }
            if ($brk['duration'] < 5 || $brk['duration'] > 90) {
                $errors[] = 'Pausendauer muss zwischen 5 und 90 Minuten liegen.';
            }
            if (in_array($brk['after_slot'], $seenAfterSlots)) {
                $errors[] = 'Doppelte Pause nach Einheit ' . $brk['after_slot'] . '.';
            }
            $seenAfterSlots[] = $brk['after_slot'];
        }

        // Duplikat-Pruefung (Schuljahr)
        if (!$id) {
            $existing = TimetableSetting::findBySchoolYear($data['school_year']);
            if ($existing) {
                $errors[] = 'Fuer dieses Schuljahr existiert bereits ein Stundenplan.';
            }
        }

        if (!empty($errors)) {
            App::setFlash('error', implode(' ', $errors));
            App::redirect('/timetable/settings' . ($id ? '?id=' . $id : ''));
            return;
        }

        if ($id) {
            TimetableSetting::update($id, $data);
            App::setFlash('success', 'Stundenplan-Konfiguration aktualisiert.');
        } else {
            $id = TimetableSetting::create($data);
            App::setFlash('success', 'Stundenplan-Konfiguration erstellt.');
        }

        Logger::audit(
            $id ? 'update_timetable_settings' : 'create_timetable_settings',
            $_SESSION['user_id'] ?? null,
            'TimetableSetting',
            $id,
            'Stundenplan-Konfiguration ' . $data['school_year']
        );

        App::redirect('/timetable');
    }

    /**
     * Klassenauswahl fuer einen Stundenplan.
     */
    public function selectClass(string $settingId): void
    {
        $this->requireAdminRole();

        $setting = TimetableSetting::findById((int) $settingId);
        if (!$setting) {
            App::setFlash('error', 'Stundenplan-Konfiguration nicht gefunden.');
            App::redirect('/timetable');
            return;
        }
        $setting['days_of_week'] = json_decode($setting['days_of_week'], true) ?: [];
        $setting['breaks'] = json_decode($setting['breaks'] ?? 'null', true) ?: [];

        $classes = SchoolClass::findAll();

        View::render('timetable/select-class', [
            'title' => 'Klasse waehlen – ' . $setting['school_year'],
            'setting' => $setting,
            'classes' => $classes,
        ]);
    }

    /**
     * Stundenplan-Editor fuer eine Klasse.
     */
    public function editClass(string $settingId, string $classId): void
    {
        $this->requireAdminRole();

        $setting = TimetableSetting::findById((int) $settingId);
        if (!$setting) {
            App::setFlash('error', 'Stundenplan-Konfiguration nicht gefunden.');
            App::redirect('/timetable');
            return;
        }
        $setting['days_of_week'] = json_decode($setting['days_of_week'], true) ?: [];
        $setting['breaks'] = json_decode($setting['breaks'] ?? 'null', true) ?: [];

        $class = SchoolClass::findById((int) $classId);
        if (!$class) {
            App::setFlash('error', 'Klasse nicht gefunden.');
            App::redirect('/timetable');
            return;
        }

        $classes = SchoolClass::findAll();
        $teachers = Teacher::findAll();
        $slots = TimetableSlot::findBySettingAndClass((int) $settingId, (int) $classId);
        $teacherUnitCounts = TimetableSlot::getTeacherUnitCounts((int) $settingId);

        // Slots als Grid-Map aufbereiten: [day][slot] => [slots...]
        $slotGrid = [];
        foreach ($slots as $slot) {
            $slotGrid[$slot['day_of_week']][$slot['slot_number']][] = $slot;
        }

        // Zeitslots berechnen
        $timeSlots = $this->calculateTimeSlots($setting);

        CsrfMiddleware::generateToken();
        View::render('timetable/edit-class', [
            'title' => 'Stundenplan: ' . $class['name'],
            'setting' => $setting,
            'class' => $class,
            'classes' => $classes,
            'teachers' => $teachers,
            'slotGrid' => $slotGrid,
            'teacherUnitCounts' => $teacherUnitCounts,
            'timeSlots' => $timeSlots,
        ]);
    }

    /**
     * AJAX: Einzelnen Slot speichern.
     */
    public function saveSlot(): void
    {
        $this->requireAdminRole();

        header('Content-Type: application/json');

        $settingId = (int) ($_POST['timetable_setting_id'] ?? 0);
        $classId = (int) ($_POST['class_id'] ?? 0);
        $teacherId = (int) ($_POST['teacher_id'] ?? 0);
        $dayOfWeek = (int) ($_POST['day_of_week'] ?? 0);
        $slotNumber = (int) ($_POST['slot_number'] ?? 0);
        $subject = trim($_POST['subject'] ?? '');
        $room = trim($_POST['room'] ?? '');

        if (!$settingId || !$classId || !$teacherId || !$dayOfWeek || !$slotNumber) {
            echo json_encode(['success' => false, 'error' => 'Fehlende Pflichtfelder.']);
            return;
        }

        // Konfliktpruefung
        $conflict = TimetableSlot::checkTeacherConflict($settingId, $teacherId, $dayOfWeek, $slotNumber, $classId);
        $conflictWarning = null;
        if ($conflict) {
            $teacher = Teacher::findById($teacherId);
            $conflictWarning = ($teacher['firstname'] ?? '') . ' ' . ($teacher['lastname'] ?? '')
                . ' ist bereits in Klasse ' . $conflict['class_name']
                . ' eingeplant (Tag ' . $dayOfWeek . ', Einheit ' . $slotNumber . ').';
        }

        $slotId = TimetableSlot::create([
            'timetable_setting_id' => $settingId,
            'class_id' => $classId,
            'teacher_id' => $teacherId,
            'day_of_week' => $dayOfWeek,
            'slot_number' => $slotNumber,
            'subject' => $subject ?: null,
            'room' => $room ?: null,
        ]);

        $slot = TimetableSlot::findById($slotId);
        $unitCount = TimetableSlot::countTeacherUnits($settingId, $teacherId);

        echo json_encode([
            'success' => true,
            'slot' => $slot,
            'unit_count' => $unitCount,
            'conflict_warning' => $conflictWarning,
        ]);
    }

    /**
     * AJAX: Slot entfernen.
     */
    public function deleteSlot(string $id): void
    {
        $this->requireAdminRole();

        header('Content-Type: application/json');

        $slot = TimetableSlot::findById((int) $id);
        if (!$slot) {
            echo json_encode(['success' => false, 'error' => 'Slot nicht gefunden.']);
            return;
        }

        $teacherId = $slot['teacher_id'];
        $settingId = $slot['timetable_setting_id'];

        TimetableSlot::delete((int) $id);
        $unitCount = TimetableSlot::countTeacherUnits($settingId, $teacherId);

        echo json_encode([
            'success' => true,
            'teacher_id' => $teacherId,
            'unit_count' => $unitCount,
        ]);
    }

    /**
     * AJAX: Lehrer-Konfliktpruefung.
     */
    public function checkConflict(): void
    {
        $this->requireAdminRole();

        header('Content-Type: application/json');

        $settingId = (int) ($_POST['timetable_setting_id'] ?? 0);
        $teacherId = (int) ($_POST['teacher_id'] ?? 0);
        $dayOfWeek = (int) ($_POST['day_of_week'] ?? 0);
        $slotNumber = (int) ($_POST['slot_number'] ?? 0);
        $classId = (int) ($_POST['class_id'] ?? 0);

        $conflict = TimetableSlot::checkTeacherConflict($settingId, $teacherId, $dayOfWeek, $slotNumber, $classId);

        if ($conflict) {
            $teacher = Teacher::findById($teacherId);
            echo json_encode([
                'has_conflict' => true,
                'message' => ($teacher['firstname'] ?? '') . ' ' . ($teacher['lastname'] ?? '')
                    . ' ist in Einheit ' . $slotNumber
                    . ' bereits in Klasse ' . $conflict['class_name'] . ' eingeplant.',
            ]);
        } else {
            echo json_encode(['has_conflict' => false]);
        }
    }

    /**
     * Stundenplan veroeffentlichen.
     */
    public function publish(string $settingId): void
    {
        $this->requireAdminRole();

        $setting = TimetableSetting::findById((int) $settingId);
        if (!$setting) {
            App::setFlash('error', 'Stundenplan nicht gefunden.');
            App::redirect('/timetable');
            return;
        }

        TimetableSetting::publish((int) $settingId, $_SESSION['user_id']);

        Logger::audit(
            'publish_timetable',
            $_SESSION['user_id'] ?? null,
            'TimetableSetting',
            (int) $settingId,
            'Stundenplan veroeffentlicht: ' . $setting['school_year']
        );

        App::setFlash('success', 'Stundenplan wurde veroeffentlicht. Lehrkraefte koennen ihn jetzt einsehen.');
        App::redirect('/timetable');
    }

    /**
     * Veroeffentlichung zurueckziehen.
     */
    public function unpublish(string $settingId): void
    {
        $this->requireAdminRole();

        $setting = TimetableSetting::findById((int) $settingId);
        if (!$setting) {
            App::setFlash('error', 'Stundenplan nicht gefunden.');
            App::redirect('/timetable');
            return;
        }

        TimetableSetting::unpublish((int) $settingId);

        Logger::audit(
            'unpublish_timetable',
            $_SESSION['user_id'] ?? null,
            'TimetableSetting',
            (int) $settingId,
            'Stundenplan-Veroeffentlichung zurueckgezogen: ' . $setting['school_year']
        );

        App::setFlash('success', 'Veroeffentlichung zurueckgezogen.');
        App::redirect('/timetable');
    }

    /**
     * Lehrer-Ansicht: eigener Stundenplan (nur veroeffentlichte).
     */
    public function teacherView(): void
    {
        $role = App::currentUserRole();
        if ($role !== 'lehrer') {
            App::setFlash('error', 'Kein Zugriff.');
            App::redirect('/dashboard');
            return;
        }
        if (!ModuleSettings::isModuleEnabled('timetable')) {
            App::setFlash('error', 'Das Modul Stundenplanung ist derzeit deaktiviert.');
            App::redirect('/dashboard');
            return;
        }

        $teacherId = Teacher::getTeacherIdByUserId($_SESSION['user_id']);
        if (!$teacherId) {
            App::setFlash('error', 'Kein Lehrer-Profil gefunden.');
            App::redirect('/dashboard');
            return;
        }

        // Veroeffentlichten Stundenplan finden
        $allSettings = TimetableSetting::findAll();
        $setting = null;
        foreach ($allSettings as $s) {
            if ($s['is_published']) {
                $setting = $s;
                break;
            }
        }

        if (!$setting) {
            View::render('timetable/teacher-view', [
                'title' => 'Mein Stundenplan',
                'setting' => null,
                'slots' => [],
                'slotGrid' => [],
                'timeSlots' => [],
            ]);
            return;
        }

        $setting['days_of_week'] = json_decode($setting['days_of_week'], true) ?: [];
        $setting['breaks'] = json_decode($setting['breaks'] ?? 'null', true) ?: [];
        $slots = TimetableSlot::findBySettingAndTeacher($setting['id'], $teacherId);

        // Slots als Grid-Map: [day][slot] => [slots...]
        $slotGrid = [];
        foreach ($slots as $slot) {
            $slotGrid[$slot['day_of_week']][$slot['slot_number']][] = $slot;
        }

        $timeSlots = $this->calculateTimeSlots($setting);

        View::render('timetable/teacher-view', [
            'title' => 'Mein Stundenplan',
            'setting' => $setting,
            'slots' => $slots,
            'slotGrid' => $slotGrid,
            'timeSlots' => $timeSlots,
            'teacherId' => $teacherId,
        ]);
    }

    /**
     * Admin-Ansicht: Stundenplan eines bestimmten Lehrers.
     */
    public function teacherSchedule(string $teacherId): void
    {
        $this->requireAdminRole();

        $teacher = Teacher::findById((int) $teacherId);
        if (!$teacher) {
            App::setFlash('error', 'Lehrkraft nicht gefunden.');
            App::redirect('/timetable');
            return;
        }

        $settingId = isset($_GET['setting_id']) ? (int) $_GET['setting_id'] : null;
        $setting = null;

        if ($settingId) {
            $setting = TimetableSetting::findById($settingId);
        } else {
            // Neuesten Stundenplan verwenden
            $allSettings = TimetableSetting::findAll();
            $setting = $allSettings[0] ?? null;
        }

        $slotGrid = [];
        $timeSlots = [];

        if ($setting) {
            $setting['days_of_week'] = json_decode($setting['days_of_week'], true) ?: [];
            $setting['breaks'] = json_decode($setting['breaks'] ?? 'null', true) ?: [];
            $slots = TimetableSlot::findBySettingAndTeacher($setting['id'], (int) $teacherId);
            foreach ($slots as $slot) {
                $slotGrid[$slot['day_of_week']][$slot['slot_number']][] = $slot;
            }
            $timeSlots = $this->calculateTimeSlots($setting);
        }

        View::render('timetable/teacher-schedule', [
            'title' => 'Stundenplan: ' . $teacher['firstname'] . ' ' . $teacher['lastname'],
            'teacher' => $teacher,
            'setting' => $setting,
            'slotGrid' => $slotGrid,
            'timeSlots' => $timeSlots,
            'allSettings' => TimetableSetting::findAll(),
        ]);
    }

    /**
     * PDF-Export: Klassen-Stundenplan.
     */
    public function exportPdf(string $settingId, string $classId): void
    {
        $this->requireAdminRole();

        $setting = TimetableSetting::findById((int) $settingId);
        $class = SchoolClass::findById((int) $classId);

        if (!$setting || !$class) {
            App::setFlash('error', 'Daten nicht gefunden.');
            App::redirect('/timetable');
            return;
        }

        $setting['days_of_week'] = json_decode($setting['days_of_week'], true) ?: [];
        $setting['breaks'] = json_decode($setting['breaks'] ?? 'null', true) ?: [];
        $slots = TimetableSlot::findBySettingAndClass((int) $settingId, (int) $classId);
        $timeSlots = $this->calculateTimeSlots($setting);

        $slotGrid = [];
        foreach ($slots as $slot) {
            $slotGrid[$slot['day_of_week']][$slot['slot_number']][] = $slot;
        }

        $this->renderSchedulePdf(
            'Stundenplan: ' . $class['name'] . ' (' . $setting['school_year'] . ')',
            $setting,
            $slotGrid,
            $timeSlots,
            'class',
            'stundenplan_' . $class['name'] . '_' . $setting['school_year']
        );

        Logger::audit(
            'export_timetable_pdf',
            $_SESSION['user_id'] ?? null,
            'SchoolClass',
            (int) $classId,
            'PDF-Export Stundenplan Klasse ' . $class['name']
        );
    }

    /**
     * PDF-Export: Lehrer-Stundenplan.
     */
    public function exportTeacherPdf(string $settingId, string $teacherId): void
    {
        $role = App::currentUserRole();

        // Lehrer darf nur eigenen Plan exportieren
        if ($role === 'lehrer') {
            $ownTeacherId = Teacher::getTeacherIdByUserId($_SESSION['user_id']);
            if ((int) $teacherId !== $ownTeacherId) {
                App::setFlash('error', 'Kein Zugriff.');
                App::redirect('/dashboard');
                return;
            }
        } elseif (!in_array($role, self::ADMIN_ROLES)) {
            App::setFlash('error', 'Kein Zugriff.');
            App::redirect('/dashboard');
            return;
        }

        $setting = TimetableSetting::findById((int) $settingId);
        $teacher = Teacher::findById((int) $teacherId);

        if (!$setting || !$teacher) {
            App::setFlash('error', 'Daten nicht gefunden.');
            App::redirect('/timetable');
            return;
        }

        // Lehrer darf nur veroeffentlichte Plaene sehen
        if ($role === 'lehrer' && !$setting['is_published']) {
            App::setFlash('error', 'Stundenplan ist noch nicht veroeffentlicht.');
            App::redirect('/timetable/my-schedule');
            return;
        }

        $setting['days_of_week'] = json_decode($setting['days_of_week'], true) ?: [];
        $setting['breaks'] = json_decode($setting['breaks'] ?? 'null', true) ?: [];
        $slots = TimetableSlot::findBySettingAndTeacher((int) $settingId, (int) $teacherId);
        $timeSlots = $this->calculateTimeSlots($setting);

        $slotGrid = [];
        foreach ($slots as $slot) {
            $slotGrid[$slot['day_of_week']][$slot['slot_number']][] = $slot;
        }

        $this->renderSchedulePdf(
            'Stundenplan: ' . $teacher['firstname'] . ' ' . $teacher['lastname'] . ' (' . $setting['school_year'] . ')',
            $setting,
            $slotGrid,
            $timeSlots,
            'teacher',
            'stundenplan_' . $teacher['abbreviation'] . '_' . $setting['school_year']
        );

        Logger::audit(
            'export_timetable_teacher_pdf',
            $_SESSION['user_id'] ?? null,
            'Teacher',
            (int) $teacherId,
            'PDF-Export Stundenplan Lehrer ' . $teacher['firstname'] . ' ' . $teacher['lastname']
        );
    }

    // === Private Hilfsmethoden ===

    private function requireAdminRole(): void
    {
        $role = App::currentUserRole();
        if (!in_array($role, self::ADMIN_ROLES)) {
            App::setFlash('error', 'Kein Zugriff.');
            App::redirect('/dashboard');
            exit;
        }
        if (!ModuleSettings::canAccess('timetable', $role)) {
            App::setFlash('error', 'Das Modul Stundenplanung ist fuer Ihre Rolle nicht zugaenglich.');
            App::redirect('/dashboard');
            exit;
        }
    }

    /**
     * Zeitslots berechnen aus Raster-Konfiguration.
     */
    private function calculateTimeSlots(array $setting): array
    {
        $slots = [];
        $startTime = strtotime($setting['day_start_time']);
        $duration = (int) $setting['unit_duration'];
        $breaks = $setting['breaks'] ?? [];

        // Pausen nach after_slot indexieren
        $breakMap = [];
        foreach ($breaks as $b) {
            $breakMap[(int) $b['after_slot']] = $b;
        }

        for ($i = 1; $i <= $setting['units_per_day']; $i++) {
            $from = date('H:i', $startTime);
            $to = date('H:i', $startTime + ($duration * 60));
            $slots[$i] = [
                'number' => $i,
                'from' => $from,
                'to' => $to,
                'break_after' => $breakMap[$i] ?? null,
            ];
            $startTime += $duration * 60;

            // Pausendauer addieren falls Pause nach diesem Slot
            if (isset($breakMap[$i])) {
                $startTime += (int) $breakMap[$i]['duration'] * 60;
            }
        }

        return $slots;
    }

    private function parseBreaksInput(array $post): array
    {
        $breaks = [];
        $afterSlots = $post['break_after_slot'] ?? [];
        $durations = $post['break_duration'] ?? [];
        $labels = $post['break_label'] ?? [];

        if (!is_array($afterSlots)) {
            return [];
        }

        for ($i = 0; $i < count($afterSlots); $i++) {
            $afterSlot = (int) ($afterSlots[$i] ?? 0);
            $dur = (int) ($durations[$i] ?? 0);
            if ($afterSlot > 0 && $dur > 0) {
                $breaks[] = [
                    'after_slot' => $afterSlot,
                    'duration' => $dur,
                    'label' => trim($labels[$i] ?? 'Pause') ?: 'Pause',
                ];
            }
        }
        return $breaks;
    }

    private static function dayName(int $day): string
    {
        $names = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag'];
        return $names[$day] ?? '';
    }

    /**
     * Gemeinsame PDF-Render-Logik fuer Klassen- und Lehrer-Stundenplaene.
     */
    private function renderSchedulePdf(
        string $title,
        array $setting,
        array $slotGrid,
        array $timeSlots,
        string $type,
        string $filenameBase
    ): void {
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Open-Classbook');
        $pdf->SetAuthor('Open-Classbook');
        $pdf->SetTitle($title);
        $pdf->SetMargins(10, 15, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, $title, 0, 1, 'C');
        $pdf->Ln(3);

        $days = $setting['days_of_week'];
        $numDays = count($days);
        $timeColWidth = 30;
        $dayColWidth = ($pdf->getPageWidth() - 20 - $timeColWidth) / max($numDays, 1);

        // Tabellenkopf
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell($timeColWidth, 8, 'Zeit', 1, 0, 'C', true);
        foreach ($days as $day) {
            $pdf->Cell($dayColWidth, 8, self::dayName($day), 1, 0, 'C', true);
        }
        $pdf->Ln();

        // Zeilen
        $pdf->SetFont('helvetica', '', 8);
        foreach ($timeSlots as $slotNum => $time) {
            $rowHeight = 12;
            $y = $pdf->GetY();

            if ($y + $rowHeight > $pdf->getPageHeight() - 15) {
                $pdf->AddPage();
            }

            $pdf->Cell($timeColWidth, $rowHeight, $slotNum . '. ' . $time['from'] . '-' . $time['to'], 1, 0, 'C');

            foreach ($days as $day) {
                $entries = $slotGrid[$day][$slotNum] ?? [];
                $cellText = '';
                foreach ($entries as $entry) {
                    if ($cellText) {
                        $cellText .= "\n";
                    }
                    if ($type === 'class') {
                        $cellText .= ($entry['abbreviation'] ?? '');
                        if (!empty($entry['subject'])) {
                            $cellText .= ' - ' . $entry['subject'];
                        }
                    } else {
                        $cellText .= ($entry['class_name'] ?? '');
                        if (!empty($entry['subject'])) {
                            $cellText .= ' - ' . $entry['subject'];
                        }
                    }
                    if (!empty($entry['room'])) {
                        $cellText .= ' (' . $entry['room'] . ')';
                    }
                }
                $pdf->MultiCell($dayColWidth, $rowHeight, $cellText, 1, 'C', false, 0);
            }
            $pdf->Ln();

            // Pausen-Zeile
            if (!empty($time['break_after'])) {
                $breakY = $pdf->GetY();
                if ($breakY + 6 > $pdf->getPageHeight() - 15) {
                    $pdf->AddPage();
                }
                $pdf->SetFont('helvetica', 'I', 7);
                $pdf->SetFillColor(245, 245, 245);
                $totalWidth = $timeColWidth + ($dayColWidth * $numDays);
                $breakText = $time['break_after']['label'] . ' (' . (int) $time['break_after']['duration'] . ' Min.)';
                $pdf->Cell($totalWidth, 6, $breakText, 1, 1, 'C', true);
                $pdf->SetFont('helvetica', '', 8);
            }
        }

        $filename = str_replace(['/', ' '], '_', $filenameBase) . '_' . date('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }
}
