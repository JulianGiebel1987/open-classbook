<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\Substitution;
use OpenClassbook\Models\SubstitutionPlan;
use OpenClassbook\Models\TimetableSetting;
use OpenClassbook\Models\Teacher;
use OpenClassbook\Services\Logger;
use OpenClassbook\Services\ModuleSettings;

class SubstitutionController
{
    private const ADMIN_ROLES = ['admin', 'schulleitung', 'sekretariat'];

    /**
     * Uebersicht: Datumsauswahl + Status.
     */
    public function index(): void
    {
        $this->requireAdminRole();

        $setting = $this->getActiveTimetableSetting();
        if (!$setting) {
            View::render('substitution/index', [
                'title' => 'Vertretungsplan',
                'setting' => null,
                'dates' => [],
                'plans' => [],
            ]);
            return;
        }

        $setting['days_of_week'] = json_decode($setting['days_of_week'], true) ?: [];

        // Naechste 14 Tage als Schnellzugriff generieren
        $dates = $this->getUpcomingSchoolDays($setting, 14);
        $plans = SubstitutionPlan::findAll($setting['id']);

        // Abwesente Lehrer pro Datum zaehlen
        $absentCounts = [];
        foreach ($dates as $d) {
            $absent = Substitution::getAbsentTeachersForDate($d['date']);
            $absentCounts[$d['date']] = count($absent);
        }

        // Plan-Status-Map
        $planStatusMap = [];
        foreach ($plans as $p) {
            $planStatusMap[$p['date']] = $p;
        }

        View::render('substitution/index', [
            'title' => 'Vertretungsplan',
            'setting' => $setting,
            'dates' => $dates,
            'absentCounts' => $absentCounts,
            'planStatusMap' => $planStatusMap,
        ]);
    }

    /**
     * Tagesplan-Editor.
     */
    public function plan(): void
    {
        $this->requireAdminRole();

        $setting = $this->getActiveTimetableSetting();
        if (!$setting) {
            App::setFlash('error', 'Kein aktiver Stundenplan vorhanden.');
            App::redirect('/substitution');
            return;
        }
        $setting['days_of_week'] = json_decode($setting['days_of_week'], true) ?: [];

        $date = $_GET['date'] ?? date('Y-m-d');

        // Datum-Validierung
        $dayOfWeek = (int) date('N', strtotime($date));
        if (!in_array($dayOfWeek, $setting['days_of_week'])) {
            App::setFlash('error', 'An diesem Wochentag findet kein Unterricht statt.');
            App::redirect('/substitution');
            return;
        }

        // Abwesende Lehrer
        $absentTeachers = Substitution::getAbsentTeachersForDate($date);

        // Offene Slots (noch nicht zugewiesene Vertretungen)
        $openSlots = Substitution::getOpenSlots($setting['id'], $date);

        // Bereits zugewiesene Vertretungen
        $assignedSubstitutions = Substitution::findByDate($setting['id'], $date);

        // Zeitslots berechnen
        $timeSlots = $this->calculateTimeSlots($setting);

        // Plan-Status
        $plan = SubstitutionPlan::findByDate($setting['id'], $date);

        CsrfMiddleware::generateToken();
        View::render('substitution/plan', [
            'title' => 'Vertretungsplan: ' . date('d.m.Y', strtotime($date)),
            'setting' => $setting,
            'date' => $date,
            'dayOfWeek' => $dayOfWeek,
            'absentTeachers' => $absentTeachers,
            'openSlots' => $openSlots,
            'assignedSubstitutions' => $assignedSubstitutions,
            'timeSlots' => $timeSlots,
            'plan' => $plan,
        ]);
    }

    /**
     * AJAX: Vertretung zuweisen.
     */
    public function assign(): void
    {
        $this->requireAdminRole();
        header('Content-Type: application/json');

        $setting = $this->getActiveTimetableSetting();
        if (!$setting) {
            echo json_encode(['success' => false, 'error' => 'Kein aktiver Stundenplan.']);
            return;
        }

        $date = $_POST['date'] ?? '';
        $slotNumber = (int) ($_POST['slot_number'] ?? 0);
        $classId = (int) ($_POST['class_id'] ?? 0);
        $absentTeacherId = (int) ($_POST['absent_teacher_id'] ?? 0);
        $substituteTeacherId = (int) ($_POST['substitute_teacher_id'] ?? 0);
        $subject = trim($_POST['subject'] ?? '');
        $room = trim($_POST['room'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $isCancelled = (int) ($_POST['is_cancelled'] ?? 0);
        $absenceTeacherId = (int) ($_POST['absence_teacher_id'] ?? 0) ?: null;

        if (!$date || !$slotNumber || !$classId || !$absentTeacherId) {
            echo json_encode(['success' => false, 'error' => 'Fehlende Pflichtfelder.']);
            return;
        }

        if (!$isCancelled && !$substituteTeacherId) {
            echo json_encode(['success' => false, 'error' => 'Bitte Vertretungslehrkraft waehlen oder als Entfall markieren.']);
            return;
        }

        $dayOfWeek = (int) date('N', strtotime($date));

        // Konfliktpruefung
        $conflictWarning = null;
        if ($substituteTeacherId) {
            $conflicts = Substitution::checkSubstituteConflict($setting['id'], $date, $substituteTeacherId, $slotNumber);
            if (!empty($conflicts)) {
                $teacher = Teacher::findById($substituteTeacherId);
                $name = ($teacher['firstname'] ?? '') . ' ' . ($teacher['lastname'] ?? '');
                $msgs = array_map(fn($c) => $c['message'], $conflicts);
                $conflictWarning = $name . ': ' . implode('; ', $msgs);
            }
        }

        // Plan-Eintrag sicherstellen
        SubstitutionPlan::createOrUpdate($setting['id'], $date, $_SESSION['user_id']);

        $subId = Substitution::create([
            'timetable_setting_id' => $setting['id'],
            'date' => $date,
            'day_of_week' => $dayOfWeek,
            'slot_number' => $slotNumber,
            'class_id' => $classId,
            'absent_teacher_id' => $absentTeacherId,
            'substitute_teacher_id' => $isCancelled ? null : $substituteTeacherId,
            'absence_teacher_id' => $absenceTeacherId,
            'subject' => $subject ?: null,
            'room' => $room ?: null,
            'notes' => $notes ?: null,
            'is_cancelled' => $isCancelled,
            'created_by' => $_SESSION['user_id'],
        ]);

        $sub = Substitution::findById($subId);

        echo json_encode([
            'success' => true,
            'substitution' => $sub,
            'conflict_warning' => $conflictWarning,
        ]);
    }

    /**
     * AJAX: Vertretung aendern.
     */
    public function update(string $id): void
    {
        $this->requireAdminRole();
        header('Content-Type: application/json');

        $sub = Substitution::findById((int) $id);
        if (!$sub) {
            echo json_encode(['success' => false, 'error' => 'Vertretung nicht gefunden.']);
            return;
        }

        $substituteTeacherId = (int) ($_POST['substitute_teacher_id'] ?? 0);
        $isCancelled = (int) ($_POST['is_cancelled'] ?? 0);

        Substitution::update((int) $id, [
            'substitute_teacher_id' => $isCancelled ? null : ($substituteTeacherId ?: null),
            'subject' => trim($_POST['subject'] ?? '') ?: null,
            'room' => trim($_POST['room'] ?? '') ?: null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
            'is_cancelled' => $isCancelled,
        ]);

        $updated = Substitution::findById((int) $id);
        echo json_encode(['success' => true, 'substitution' => $updated]);
    }

    /**
     * AJAX: Vertretung entfernen.
     */
    public function delete(string $id): void
    {
        $this->requireAdminRole();
        header('Content-Type: application/json');

        $sub = Substitution::findById((int) $id);
        if (!$sub) {
            echo json_encode(['success' => false, 'error' => 'Vertretung nicht gefunden.']);
            return;
        }

        Substitution::delete((int) $id);
        echo json_encode(['success' => true]);
    }

    /**
     * AJAX: Einheit als Entfall markieren.
     */
    public function cancel(): void
    {
        $this->requireAdminRole();
        header('Content-Type: application/json');

        $setting = $this->getActiveTimetableSetting();
        if (!$setting) {
            echo json_encode(['success' => false, 'error' => 'Kein aktiver Stundenplan.']);
            return;
        }

        $date = $_POST['date'] ?? '';
        $slotNumber = (int) ($_POST['slot_number'] ?? 0);
        $classId = (int) ($_POST['class_id'] ?? 0);
        $absentTeacherId = (int) ($_POST['absent_teacher_id'] ?? 0);
        $absenceTeacherId = (int) ($_POST['absence_teacher_id'] ?? 0) ?: null;
        $dayOfWeek = (int) date('N', strtotime($date));

        SubstitutionPlan::createOrUpdate($setting['id'], $date, $_SESSION['user_id']);

        $subId = Substitution::create([
            'timetable_setting_id' => $setting['id'],
            'date' => $date,
            'day_of_week' => $dayOfWeek,
            'slot_number' => $slotNumber,
            'class_id' => $classId,
            'absent_teacher_id' => $absentTeacherId,
            'substitute_teacher_id' => null,
            'absence_teacher_id' => $absenceTeacherId,
            'subject' => null,
            'room' => null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
            'is_cancelled' => 1,
            'created_by' => $_SESSION['user_id'],
        ]);

        $sub = Substitution::findById($subId);
        echo json_encode(['success' => true, 'substitution' => $sub]);
    }

    /**
     * AJAX: Konfliktpruefung fuer Vertretungslehrer.
     */
    public function checkConflict(): void
    {
        $this->requireAdminRole();
        header('Content-Type: application/json');

        $setting = $this->getActiveTimetableSetting();
        if (!$setting) {
            echo json_encode(['conflicts' => []]);
            return;
        }

        $date = $_POST['date'] ?? '';
        $teacherId = (int) ($_POST['teacher_id'] ?? 0);
        $slotNumber = (int) ($_POST['slot_number'] ?? 0);

        $conflicts = Substitution::checkSubstituteConflict($setting['id'], $date, $teacherId, $slotNumber);
        $teacher = Teacher::findById($teacherId);

        echo json_encode([
            'conflicts' => $conflicts,
            'teacher_name' => $teacher ? $teacher['firstname'] . ' ' . $teacher['lastname'] : '',
        ]);
    }

    /**
     * AJAX: Verfuegbare Lehrer fuer einen Slot.
     */
    public function availableTeachers(): void
    {
        $this->requireAdminRole();
        header('Content-Type: application/json');

        $setting = $this->getActiveTimetableSetting();
        if (!$setting) {
            echo json_encode(['teachers' => []]);
            return;
        }

        $date = $_POST['date'] ?? '';
        $slotNumber = (int) ($_POST['slot_number'] ?? 0);

        $teachers = Substitution::getAvailableTeachers($setting['id'], $date, $slotNumber);

        echo json_encode(['teachers' => $teachers]);
    }

    /**
     * Vertretungsplan veroeffentlichen.
     */
    public function publish(): void
    {
        $this->requireAdminRole();

        $setting = $this->getActiveTimetableSetting();
        $date = $_POST['date'] ?? '';

        if (!$setting || !$date) {
            App::setFlash('error', 'Fehlende Daten.');
            App::redirect('/substitution');
            return;
        }

        $planId = SubstitutionPlan::createOrUpdate($setting['id'], $date, $_SESSION['user_id']);
        SubstitutionPlan::publish($planId, $_SESSION['user_id']);

        Logger::audit(
            'publish_substitution_plan',
            $_SESSION['user_id'] ?? null,
            'SubstitutionPlan',
            $planId,
            'Vertretungsplan veroeffentlicht: ' . $date
        );

        App::setFlash('success', 'Vertretungsplan fuer ' . date('d.m.Y', strtotime($date)) . ' veroeffentlicht.');
        App::redirect('/substitution/plan?date=' . $date);
    }

    /**
     * Veroeffentlichung zurueckziehen.
     */
    public function unpublish(): void
    {
        $this->requireAdminRole();

        $setting = $this->getActiveTimetableSetting();
        $date = $_POST['date'] ?? '';

        if (!$setting || !$date) {
            App::setFlash('error', 'Fehlende Daten.');
            App::redirect('/substitution');
            return;
        }

        $plan = SubstitutionPlan::findByDate($setting['id'], $date);
        if ($plan) {
            SubstitutionPlan::unpublish($plan['id']);

            Logger::audit(
                'unpublish_substitution_plan',
                $_SESSION['user_id'] ?? null,
                'SubstitutionPlan',
                $plan['id'],
                'Vertretungsplan zurueckgezogen: ' . $date
            );
        }

        App::setFlash('success', 'Veroeffentlichung zurueckgezogen.');
        App::redirect('/substitution/plan?date=' . $date);
    }

    /**
     * Lehrer-Ansicht: eigene Vertretungen.
     */
    public function teacherView(): void
    {
        $role = App::currentUserRole();
        if ($role !== 'lehrer') {
            App::setFlash('error', 'Kein Zugriff.');
            App::redirect('/dashboard');
            return;
        }
        if (!ModuleSettings::isModuleEnabled('substitution')) {
            App::setFlash('error', 'Das Modul Vertretung ist derzeit deaktiviert.');
            App::redirect('/dashboard');
            return;
        }

        $teacherId = Teacher::getTeacherIdByUserId($_SESSION['user_id']);
        if (!$teacherId) {
            App::setFlash('error', 'Kein Lehrer-Profil gefunden.');
            App::redirect('/dashboard');
            return;
        }

        $setting = $this->getPublishedTimetableSetting();
        $mySubstitutions = [];
        $myAbsences = [];

        if ($setting) {
            $today = date('Y-m-d');
            $mySubstitutions = Substitution::findUpcomingForTeacher($setting['id'], $teacherId, $today);
            $myAbsences = Substitution::findAbsentTeacherEntries($setting['id'], $teacherId, $today);
        }

        View::render('substitution/teacher-view', [
            'title' => 'Vertretung',
            'setting' => $setting,
            'mySubstitutions' => $mySubstitutions,
            'myAbsences' => $myAbsences,
            'teacherId' => $teacherId,
        ]);
    }

    /**
     * PDF-Export: Tagesplan.
     */
    public function exportPdf(): void
    {
        $role = App::currentUserRole();
        $date = $_GET['date'] ?? date('Y-m-d');

        // Lehrer duerfen nur veroeffentlichte Plaene exportieren
        if ($role === 'lehrer') {
            $setting = $this->getPublishedTimetableSetting();
        } elseif (in_array($role, self::ADMIN_ROLES)) {
            $setting = $this->getActiveTimetableSetting();
        } else {
            App::setFlash('error', 'Kein Zugriff.');
            App::redirect('/dashboard');
            return;
        }

        if (!$setting) {
            App::setFlash('error', 'Kein Stundenplan vorhanden.');
            App::redirect('/substitution');
            return;
        }

        // Lehrer: Pruefen ob Plan veroeffentlicht ist
        if ($role === 'lehrer') {
            $plan = SubstitutionPlan::findByDate($setting['id'], $date);
            if (!$plan || !$plan['is_published']) {
                App::setFlash('error', 'Vertretungsplan nicht verfuegbar.');
                App::redirect('/substitution/my-substitutions');
                return;
            }
        }

        $setting['days_of_week'] = json_decode($setting['days_of_week'], true) ?: [];
        $substitutions = Substitution::findByDate($setting['id'], $date);
        $absentTeachers = Substitution::getAbsentTeachersForDate($date);
        $timeSlots = $this->calculateTimeSlots($setting);

        $this->renderDayPlanPdf($date, $substitutions, $absentTeachers, $timeSlots, $setting);

        Logger::audit(
            'export_substitution_pdf',
            $_SESSION['user_id'] ?? null,
            'SubstitutionPlan',
            0,
            'PDF-Export Vertretungsplan: ' . $date
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
        if (!ModuleSettings::canAccess('substitution', $role)) {
            App::setFlash('error', 'Das Modul Vertretung ist fuer Ihre Rolle nicht zugaenglich.');
            App::redirect('/dashboard');
            exit;
        }
    }

    private function getActiveTimetableSetting(): ?array
    {
        $allSettings = TimetableSetting::findAll();
        return $allSettings[0] ?? null;
    }

    private function getPublishedTimetableSetting(): ?array
    {
        $allSettings = TimetableSetting::findAll();
        foreach ($allSettings as $s) {
            if ($s['is_published']) {
                return $s;
            }
        }
        return null;
    }

    private function calculateTimeSlots(array $setting): array
    {
        $slots = [];
        $startTime = strtotime($setting['day_start_time']);
        $duration = (int) $setting['unit_duration'];

        for ($i = 1; $i <= $setting['units_per_day']; $i++) {
            $from = date('H:i', $startTime);
            $to = date('H:i', $startTime + ($duration * 60));
            $slots[$i] = ['number' => $i, 'from' => $from, 'to' => $to];
            $startTime += $duration * 60;
        }

        return $slots;
    }

    private function getUpcomingSchoolDays(array $setting, int $count): array
    {
        $days = [];
        $current = new \DateTime();
        $dayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag'];

        while (count($days) < $count) {
            $dow = (int) $current->format('N');
            if (in_array($dow, $setting['days_of_week'])) {
                $days[] = [
                    'date' => $current->format('Y-m-d'),
                    'formatted' => $current->format('d.m.Y'),
                    'day_name' => $dayNames[$dow] ?? '',
                    'is_today' => $current->format('Y-m-d') === date('Y-m-d'),
                ];
            }
            $current->modify('+1 day');
        }

        return $days;
    }

    private function renderDayPlanPdf(string $date, array $substitutions, array $absentTeachers, array $timeSlots, array $setting): void
    {
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Open-Classbook');
        $pdf->SetAuthor('Open-Classbook');
        $pdf->SetTitle('Vertretungsplan ' . date('d.m.Y', strtotime($date)));
        $pdf->SetMargins(10, 15, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        $dayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag'];
        $dow = (int) date('N', strtotime($date));

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Vertretungsplan: ' . ($dayNames[$dow] ?? '') . ', ' . date('d.m.Y', strtotime($date)), 0, 1, 'C');

        // Abwesende Lehrer
        if (!empty($absentTeachers)) {
            $pdf->Ln(2);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 7, 'Abwesende Lehrkraefte:', 0, 1);
            $pdf->SetFont('helvetica', '', 9);
            foreach ($absentTeachers as $at) {
                $typeLabels = ['krank' => 'krank', 'fortbildung' => 'Fortbildung', 'sonstiges' => 'sonstiges'];
                $pdf->Cell(0, 5, $at['abbreviation'] . ' - ' . $at['lastname'] . ', ' . $at['firstname']
                    . ' (' . ($typeLabels[$at['absence_type']] ?? $at['absence_type']) . ')', 0, 1);
            }
        }

        $pdf->Ln(3);

        // Vertretungstabelle
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(20, 8, 'Einheit', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Klasse', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Fach', 1, 0, 'C', true);
        $pdf->Cell(50, 8, 'Abwesend', 1, 0, 'C', true);
        $pdf->Cell(50, 8, 'Vertretung', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Raum', 1, 0, 'C', true);
        $pdf->Cell(67, 8, 'Hinweis', 1, 1, 'C', true);

        $pdf->SetFont('helvetica', '', 9);
        if (empty($substitutions)) {
            $pdf->Cell(277, 8, 'Keine Vertretungen eingetragen.', 1, 1, 'C');
        } else {
            foreach ($substitutions as $s) {
                $slotInfo = isset($timeSlots[$s['slot_number']])
                    ? $s['slot_number'] . '. (' . $timeSlots[$s['slot_number']]['from'] . ')'
                    : (string) $s['slot_number'];

                $substituteText = $s['is_cancelled']
                    ? '--- Entfall ---'
                    : (($s['substitute_abbreviation'] ?? '') . ' ' . ($s['substitute_lastname'] ?? ''));

                $pdf->Cell(20, 7, $slotInfo, 1, 0, 'C');
                $pdf->Cell(25, 7, $s['class_name'] ?? '', 1, 0, 'C');
                $pdf->Cell(40, 7, $s['subject'] ?? '', 1, 0, 'L');
                $pdf->Cell(50, 7, ($s['absent_abbreviation'] ?? '') . ' ' . ($s['absent_lastname'] ?? ''), 1, 0, 'L');
                $pdf->Cell(50, 7, $substituteText, 1, 0, 'L');
                $pdf->Cell(25, 7, $s['room'] ?? '', 1, 0, 'C');
                $pdf->Cell(67, 7, $s['notes'] ?? '', 1, 1, 'L');
            }
        }

        $filename = 'vertretungsplan_' . $date . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }
}
