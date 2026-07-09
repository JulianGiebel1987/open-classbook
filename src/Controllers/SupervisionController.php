<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\SupervisionPlan;
use OpenClassbook\Models\SupervisionBreak;
use OpenClassbook\Models\SupervisionLocation;
use OpenClassbook\Models\SupervisionAssignment;
use OpenClassbook\Models\Teacher;
use OpenClassbook\Services\Logger;
use OpenClassbook\Services\ModuleSettings;

class SupervisionController
{
    private const ADMIN_ROLES = ['admin', 'schulleitung', 'sekretariat'];

    /**
     * Übersicht aller Pausenaufsichtspläne.
     */
    public function index(): void
    {
        $this->requireAdminRole();

        $plans = SupervisionPlan::findAll();
        foreach ($plans as &$p) {
            $p['days_of_week'] = json_decode($p['days_of_week'], true) ?: [];
        }
        unset($p);

        View::render('supervision/index', [
            'title' => 'Pausenaufsichtsplan',
            'plans' => $plans,
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Stundenplanung', 'url' => '/timetable'],
                ['label' => 'Pausenaufsichtsplan'],
            ]),
        ]);
    }

    /**
     * Formular: Plan-Grunddaten (inkl. Pausenspalten) erstellen/bearbeiten.
     */
    public function settingsForm(): void
    {
        $this->requireAdminRole();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $plan = $id ? SupervisionPlan::findById($id) : null;
        $breaks = [];

        if ($plan) {
            $plan['days_of_week'] = json_decode($plan['days_of_week'], true) ?: [];
            $breaks = SupervisionBreak::findByPlan($id);
        }

        CsrfMiddleware::generateToken();
        View::render('supervision/settings', [
            'title' => $plan ? 'Pausenaufsichtsplan bearbeiten' : 'Neuer Pausenaufsichtsplan',
            'plan' => $plan,
            'breaks' => $breaks,
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Stundenplanung', 'url' => '/timetable'],
                ['label' => 'Pausenaufsichtsplan', 'url' => '/supervision'],
                ['label' => $plan ? 'Bearbeiten' : 'Neuer Plan'],
            ]),
        ]);
    }

    /**
     * Plan-Grunddaten speichern (inkl. Abgleich der Pausenspalten).
     */
    public function saveSettings(): void
    {
        $this->requireAdminRole();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : null;
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'school_year' => trim($_POST['school_year'] ?? ''),
            'days_of_week' => array_map('intval', $_POST['days_of_week'] ?? []),
            'created_by' => $_SESSION['user_id'],
        ];
        $breaks = $this->parseBreaksInput($_POST);

        // Validierung
        $errors = [];
        if ($data['name'] === '') {
            $errors[] = 'Bitte einen Namen für den Plan angeben.';
        }
        if (empty($data['school_year']) || !preg_match('/^\d{4}\/\d{4}$/', $data['school_year'])) {
            $errors[] = 'Schuljahr muss im Format JJJJ/JJJJ angegeben werden (z.B. 2025/2026).';
        }
        if (empty($data['days_of_week'])) {
            $errors[] = 'Mindestens ein Wochentag muss ausgewählt werden.';
        }
        if (empty($breaks)) {
            $errors[] = 'Mindestens eine Pausenspalte muss definiert werden.';
        }

        // Duplikat-Prüfung (Schuljahr) nur bei neuem Plan
        if (!$id && empty(array_filter($errors, fn($e) => str_contains($e, 'Schuljahr')))) {
            $existing = SupervisionPlan::findBySchoolYear($data['school_year']);
            if ($existing) {
                $errors[] = 'Für dieses Schuljahr existiert bereits ein Pausenaufsichtsplan.';
            }
        }

        if (!empty($errors)) {
            App::setFlash('error', implode(' ', $errors));
            App::redirect('/supervision/settings' . ($id ? '?id=' . $id : ''));
            return;
        }

        if ($id) {
            SupervisionPlan::update($id, $data);
        } else {
            $id = SupervisionPlan::create($data);
        }

        $this->syncBreaks($id, $breaks);

        Logger::audit(
            $id ? 'update_supervision_plan' : 'create_supervision_plan',
            $_SESSION['user_id'] ?? null,
            'SupervisionPlan',
            $id,
            'Pausenaufsichtsplan ' . $data['name'] . ' (' . $data['school_year'] . ')'
        );

        App::setFlash('success', 'Pausenaufsichtsplan gespeichert. Jetzt Aufsichtspunkte und Aufsichten planen.');
        App::redirect('/supervision/' . $id);
    }

    /**
     * Grid-Editor: Aufsichtspunkte (Zeilen) x Tage/Pausen (Spalten) mit Lehrer-Zuweisungen.
     */
    public function edit(string $planId): void
    {
        $this->requireAdminRole();

        $plan = SupervisionPlan::findById((int) $planId);
        if (!$plan) {
            App::setFlash('error', 'Pausenaufsichtsplan nicht gefunden.');
            App::redirect('/supervision');
            return;
        }
        $plan['days_of_week'] = json_decode($plan['days_of_week'], true) ?: [];

        $breaks = SupervisionBreak::findByPlan((int) $planId);
        $locations = SupervisionLocation::findByPlan((int) $planId);
        $assignments = SupervisionAssignment::findByPlan((int) $planId);
        $teachers = Teacher::findAll();
        $teacherCounts = SupervisionAssignment::getTeacherCounts((int) $planId);

        // Grid-Map: [location_id][day][break_id] => [assignments...]
        $grid = [];
        foreach ($assignments as $a) {
            $grid[$a['location_id']][$a['day_of_week']][$a['break_id']][] = $a;
        }

        CsrfMiddleware::generateToken();
        View::render('supervision/edit', [
            'title' => 'Pausenaufsichten: ' . $plan['name'],
            'plan' => $plan,
            'breaks' => $breaks,
            'locations' => $locations,
            'grid' => $grid,
            'teachers' => $teachers,
            'teacherCounts' => $teacherCounts,
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Stundenplanung', 'url' => '/timetable'],
                ['label' => 'Pausenaufsichtsplan', 'url' => '/supervision'],
                ['label' => $plan['name']],
            ]),
        ]);
    }

    /**
     * Aufsichtspunkt (Zeile) hinzufügen.
     */
    public function addLocation(): void
    {
        $this->requireAdminRole();

        $planId = (int) ($_POST['plan_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');

        $plan = SupervisionPlan::findById($planId);
        if (!$plan) {
            App::setFlash('error', 'Pausenaufsichtsplan nicht gefunden.');
            App::redirect('/supervision');
            return;
        }
        if ($name === '') {
            App::setFlash('error', 'Bitte eine Bezeichnung für den Aufsichtspunkt angeben.');
            App::redirect('/supervision/' . $planId);
            return;
        }

        $existing = SupervisionLocation::findByPlan($planId);
        SupervisionLocation::create([
            'plan_id' => $planId,
            'name' => $name,
            'sort_order' => count($existing),
        ]);

        App::redirect('/supervision/' . $planId);
    }

    /**
     * Aufsichtspunkt (Zeile) löschen.
     */
    public function deleteLocation(string $id): void
    {
        $this->requireAdminRole();

        $location = SupervisionLocation::findById((int) $id);
        if (!$location) {
            App::setFlash('error', 'Aufsichtspunkt nicht gefunden.');
            App::redirect('/supervision');
            return;
        }

        SupervisionLocation::delete((int) $id);
        App::setFlash('success', 'Aufsichtspunkt entfernt.');
        App::redirect('/supervision/' . (int) $location['plan_id']);
    }

    /**
     * AJAX: Lehrkraft zu einer Zelle (Aufsichtspunkt x Tag x Pause) zuweisen.
     */
    public function saveAssignment(): void
    {
        $this->requireAdminRole();

        header('Content-Type: application/json');

        $planId = (int) ($_POST['plan_id'] ?? 0);
        $locationId = (int) ($_POST['location_id'] ?? 0);
        $breakId = (int) ($_POST['break_id'] ?? 0);
        $dayOfWeek = (int) ($_POST['day_of_week'] ?? 0);
        $teacherId = (int) ($_POST['teacher_id'] ?? 0);

        if (!$planId || !$locationId || !$breakId || !$dayOfWeek || !$teacherId) {
            echo json_encode(['success' => false, 'error' => 'Fehlende Pflichtfelder.']);
            return;
        }

        if (SupervisionAssignment::exists($breakId, $locationId, $dayOfWeek, $teacherId)) {
            echo json_encode(['success' => false, 'error' => 'Diese Lehrkraft ist hier bereits eingeplant.']);
            return;
        }

        // Konfliktprüfung: gleiche Lehrkraft, gleiche Zeit, anderer Aufsichtspunkt
        $conflict = SupervisionAssignment::checkTeacherConflict($planId, $teacherId, $dayOfWeek, $breakId, $locationId);
        $conflictWarning = null;
        if ($conflict) {
            $teacher = Teacher::findById($teacherId);
            $conflictWarning = ($teacher['firstname'] ?? '') . ' ' . ($teacher['lastname'] ?? '')
                . ' ist zur selben Zeit bereits am Aufsichtspunkt "' . $conflict['location_name'] . '" eingeplant.';
        }

        $assignmentId = SupervisionAssignment::create([
            'plan_id' => $planId,
            'break_id' => $breakId,
            'location_id' => $locationId,
            'day_of_week' => $dayOfWeek,
            'teacher_id' => $teacherId,
        ]);

        $assignment = SupervisionAssignment::findById($assignmentId);
        $count = SupervisionAssignment::getTeacherCounts($planId)[$teacherId] ?? 0;

        echo json_encode([
            'success' => true,
            'assignment' => $assignment,
            'teacher_count' => $count,
            'conflict_warning' => $conflictWarning,
        ]);
    }

    /**
     * AJAX: Zuweisung entfernen.
     */
    public function deleteAssignment(string $id): void
    {
        $this->requireAdminRole();

        header('Content-Type: application/json');

        $assignment = SupervisionAssignment::findById((int) $id);
        if (!$assignment) {
            echo json_encode(['success' => false, 'error' => 'Zuweisung nicht gefunden.']);
            return;
        }

        $teacherId = (int) $assignment['teacher_id'];
        $planId = (int) $assignment['plan_id'];

        SupervisionAssignment::delete((int) $id);
        $count = SupervisionAssignment::getTeacherCounts($planId)[$teacherId] ?? 0;

        echo json_encode([
            'success' => true,
            'teacher_id' => $teacherId,
            'teacher_count' => $count,
        ]);
    }

    /**
     * AJAX: Konfliktprüfung vor dem Zuweisen.
     */
    public function checkConflict(): void
    {
        $this->requireAdminRole();

        header('Content-Type: application/json');

        $planId = (int) ($_POST['plan_id'] ?? 0);
        $teacherId = (int) ($_POST['teacher_id'] ?? 0);
        $dayOfWeek = (int) ($_POST['day_of_week'] ?? 0);
        $breakId = (int) ($_POST['break_id'] ?? 0);
        $locationId = (int) ($_POST['location_id'] ?? 0);

        $conflict = SupervisionAssignment::checkTeacherConflict($planId, $teacherId, $dayOfWeek, $breakId, $locationId);

        if ($conflict) {
            $teacher = Teacher::findById($teacherId);
            echo json_encode([
                'has_conflict' => true,
                'message' => ($teacher['firstname'] ?? '') . ' ' . ($teacher['lastname'] ?? '')
                    . ' ist zur selben Zeit bereits am Aufsichtspunkt "' . $conflict['location_name'] . '" eingeplant.',
            ]);
        } else {
            echo json_encode(['has_conflict' => false]);
        }
    }

    /**
     * Plan veröffentlichen.
     */
    public function publish(string $planId): void
    {
        $this->requireAdminRole();

        $plan = SupervisionPlan::findById((int) $planId);
        if (!$plan) {
            App::setFlash('error', 'Pausenaufsichtsplan nicht gefunden.');
            App::redirect('/supervision');
            return;
        }

        SupervisionPlan::publish((int) $planId, $_SESSION['user_id']);

        Logger::audit(
            'publish_supervision_plan',
            $_SESSION['user_id'] ?? null,
            'SupervisionPlan',
            (int) $planId,
            'Pausenaufsichtsplan veröffentlicht: ' . $plan['name']
        );

        App::setFlash('success', 'Pausenaufsichtsplan veröffentlicht. Lehrkräfte können ihre Aufsichten jetzt einsehen.');
        App::redirect('/supervision');
    }

    /**
     * Veröffentlichung zurückziehen.
     */
    public function unpublish(string $planId): void
    {
        $this->requireAdminRole();

        $plan = SupervisionPlan::findById((int) $planId);
        if (!$plan) {
            App::setFlash('error', 'Pausenaufsichtsplan nicht gefunden.');
            App::redirect('/supervision');
            return;
        }

        SupervisionPlan::unpublish((int) $planId);

        Logger::audit(
            'unpublish_supervision_plan',
            $_SESSION['user_id'] ?? null,
            'SupervisionPlan',
            (int) $planId,
            'Pausenaufsichtsplan-Veröffentlichung zurückgezogen: ' . $plan['name']
        );

        App::setFlash('success', 'Veröffentlichung zurückgezogen.');
        App::redirect('/supervision');
    }

    /**
     * Lehrer-Ansicht: eigene Pausenaufsichten (nur veröffentlichte Pläne).
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

        // Veröffentlichten Plan finden (neuester zuerst)
        $plan = null;
        foreach (SupervisionPlan::findAll() as $p) {
            if ($p['is_published']) {
                $plan = $p;
                break;
            }
        }

        $assignments = [];
        if ($plan) {
            $plan['days_of_week'] = json_decode($plan['days_of_week'], true) ?: [];
            $assignments = SupervisionAssignment::findByPlanAndTeacher((int) $plan['id'], $teacherId);
        }

        // Gruppieren nach Wochentag
        $byDay = [];
        foreach ($assignments as $a) {
            $byDay[$a['day_of_week']][] = $a;
        }

        View::render('supervision/teacher-view', [
            'title' => 'Meine Pausenaufsichten',
            'plan' => $plan,
            'byDay' => $byDay,
            'totalCount' => count($assignments),
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Mein Stundenplan', 'url' => '/timetable/my-schedule'],
                ['label' => 'Meine Pausenaufsichten'],
            ]),
        ]);
    }

    /**
     * PDF-Export des kompletten Pausenaufsichtsplans.
     */
    public function exportPdf(string $planId): void
    {
        $this->requireAdminRole();

        $plan = SupervisionPlan::findById((int) $planId);
        if (!$plan) {
            App::setFlash('error', 'Pausenaufsichtsplan nicht gefunden.');
            App::redirect('/supervision');
            return;
        }
        $plan['days_of_week'] = json_decode($plan['days_of_week'], true) ?: [];

        $breaks = SupervisionBreak::findByPlan((int) $planId);
        $locations = SupervisionLocation::findByPlan((int) $planId);
        $assignments = SupervisionAssignment::findByPlan((int) $planId);

        $grid = [];
        foreach ($assignments as $a) {
            $grid[$a['location_id']][$a['day_of_week']][$a['break_id']][] = $a;
        }

        $this->renderPlanPdf($plan, $breaks, $locations, $grid);

        Logger::audit(
            'export_supervision_pdf',
            $_SESSION['user_id'] ?? null,
            'SupervisionPlan',
            (int) $planId,
            'PDF-Export Pausenaufsichtsplan ' . $plan['name']
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
            App::setFlash('error', 'Das Modul Stundenplanung ist für Ihre Rolle nicht zugänglich.');
            App::redirect('/dashboard');
            exit;
        }
    }

    /**
     * Pausenspalten aus dem POST parsen.
     * @return array Liste aus ['id'=>?int, 'label'=>string, 'start_time'=>?string, 'end_time'=>?string]
     */
    private function parseBreaksInput(array $post): array
    {
        $labels = $post['break_label'] ?? [];
        $ids = $post['break_id'] ?? [];
        $starts = $post['break_start'] ?? [];
        $ends = $post['break_end'] ?? [];

        if (!is_array($labels)) {
            return [];
        }

        $breaks = [];
        for ($i = 0; $i < count($labels); $i++) {
            $label = trim($labels[$i] ?? '');
            if ($label === '') {
                continue;
            }
            $breaks[] = [
                'id' => isset($ids[$i]) && $ids[$i] !== '' ? (int) $ids[$i] : null,
                'label' => $label,
                'start_time' => trim($starts[$i] ?? '') ?: null,
                'end_time' => trim($ends[$i] ?? '') ?: null,
            ];
        }
        return $breaks;
    }

    /**
     * Pausenspalten mit der Datenbank abgleichen (Insert/Update/Delete).
     */
    private function syncBreaks(int $planId, array $breaks): void
    {
        $existing = SupervisionBreak::findByPlan($planId);
        $existingIds = array_map(fn($b) => (int) $b['id'], $existing);
        $keptIds = [];

        $sort = 0;
        foreach ($breaks as $brk) {
            $payload = [
                'plan_id' => $planId,
                'label' => $brk['label'],
                'start_time' => $brk['start_time'],
                'end_time' => $brk['end_time'],
                'sort_order' => $sort++,
            ];

            if ($brk['id'] !== null && in_array($brk['id'], $existingIds, true)) {
                SupervisionBreak::update($brk['id'], $payload);
                $keptIds[] = $brk['id'];
            } else {
                SupervisionBreak::create($payload);
            }
        }

        // Entfernte Pausenspalten löschen (Cascade entfernt zugehörige Zuweisungen)
        foreach ($existingIds as $eid) {
            if (!in_array($eid, $keptIds, true)) {
                SupervisionBreak::delete($eid);
            }
        }
    }

    private static function dayName(int $day): string
    {
        $names = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag'];
        return $names[$day] ?? '';
    }

    /**
     * PDF-Render-Logik: Spalten = Tage x Pausen, Zeilen = Aufsichtspunkte.
     */
    private function renderPlanPdf(array $plan, array $breaks, array $locations, array $grid): void
    {
        $days = $plan['days_of_week'];
        $numDays = count($days);
        $numBreaks = max(count($breaks), 1);

        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Open-Classbook');
        $pdf->SetAuthor('Open-Classbook');
        $pdf->SetTitle('Pausenaufsichtsplan ' . $plan['name']);
        $pdf->SetMargins(10, 15, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Pausenaufsichtsplan: ' . $plan['name'] . ' (' . $plan['school_year'] . ')', 0, 1, 'C');
        $pdf->Ln(3);

        $locColWidth = 35;
        $available = $pdf->getPageWidth() - 20 - $locColWidth;
        $cellWidth = $available / max($numDays * $numBreaks, 1);
        $dayColWidth = $cellWidth * $numBreaks;

        // Kopfzeile 1: Tage
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell($locColWidth, 14, 'Aufsichtspunkt', 1, 0, 'C', true);
        $xStart = $pdf->GetX();
        $yStart = $pdf->GetY();
        foreach ($days as $i => $day) {
            $pdf->SetXY($xStart + $i * $dayColWidth, $yStart);
            $pdf->Cell($dayColWidth, 7, self::dayName($day), 1, 0, 'C', true);
        }
        // Kopfzeile 2: Pausen je Tag
        $pdf->SetFont('helvetica', '', 7);
        foreach ($days as $i => $day) {
            foreach ($breaks as $j => $brk) {
                $pdf->SetXY($xStart + $i * $dayColWidth + $j * $cellWidth, $yStart + 7);
                $label = $brk['label'];
                if (!empty($brk['start_time'])) {
                    $label .= "\n" . substr($brk['start_time'], 0, 5);
                    if (!empty($brk['end_time'])) {
                        $label .= '-' . substr($brk['end_time'], 0, 5);
                    }
                }
                $pdf->MultiCell($cellWidth, 7, $label, 1, 'C', true, 0);
            }
        }
        $pdf->SetXY(10, $yStart + 14);

        // Datenzeilen: Aufsichtspunkte
        $pdf->SetFont('helvetica', '', 8);
        if (empty($locations)) {
            $pdf->Cell(0, 8, 'Noch keine Aufsichtspunkte definiert.', 1, 1, 'C');
        }
        foreach ($locations as $loc) {
            $rowHeight = 12;
            $y = $pdf->GetY();
            if ($y + $rowHeight > $pdf->getPageHeight() - 15) {
                $pdf->AddPage();
            }

            $rowY = $pdf->GetY();
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->MultiCell($locColWidth, $rowHeight, $loc['name'], 1, 'L', false, 0);
            $pdf->SetFont('helvetica', '', 8);

            $rowX = 10 + $locColWidth;
            foreach ($days as $i => $day) {
                foreach ($breaks as $j => $brk) {
                    $entries = $grid[$loc['id']][$day][$brk['id']] ?? [];
                    $text = '';
                    foreach ($entries as $e) {
                        $text .= ($text ? "\n" : '') . ($e['abbreviation'] ?? ($e['lastname'] ?? ''));
                    }
                    $pdf->SetXY($rowX + $i * $dayColWidth + $j * $cellWidth, $rowY);
                    $pdf->MultiCell($cellWidth, $rowHeight, $text, 1, 'C', false, 0);
                }
            }
            $pdf->SetXY(10, $rowY + $rowHeight);
        }

        $filename = 'pausenaufsichtsplan_' . str_replace(['/', ' '], '_', $plan['name']) . '_' . date('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }
}
