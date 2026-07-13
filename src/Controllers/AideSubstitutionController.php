<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\AideSubstitution;
use OpenClassbook\Models\SchoolAide;
use OpenClassbook\Services\Logger;
use OpenClassbook\Services\ModuleSettings;

/**
 * Vertretungsplanung fuer Schulbegleiter:innen (schueler-/abwesenheitsbasiert,
 * ohne Stundenplan). Bei Ausfall einer Begleitung werden ihre zugewiesenen
 * Kinder aufgelistet und pro Kind eine Ersatz-Begleitung mit Prioritaet geplant.
 */
class AideSubstitutionController
{
    private const STAFF_ROLES = ['admin', 'schulleitung', 'sekretariat'];

    private function requireStaff(): bool
    {
        $role = App::currentUserRole();
        if (!in_array($role, self::STAFF_ROLES, true)) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/dashboard');
            return false;
        }
        if (!ModuleSettings::canAccess('school_aides', $role)) {
            App::setFlash('error', 'Das Modul Schulbegleiter:innen ist für Ihre Rolle nicht zugänglich.');
            App::redirect('/dashboard');
            return false;
        }
        return true;
    }

    /**
     * Uebersicht aller Vertretungsbedarfe im Zeitraum, nach Prioritaet sortiert.
     */
    public function index(): void
    {
        if (!$this->requireStaff()) return;

        [$dateFrom, $dateTo] = $this->range();

        CsrfMiddleware::generateToken();
        View::render('aide-substitution/index', [
            'title' => 'Schulbegleiter:innen-Vertretung',
            'needs' => AideSubstitution::findByDateRange($dateFrom, $dateTo),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'priorities' => AideSubstitution::PRIORITIES,
            'publishStatus' => AideSubstitution::getPublishStatusForRange($dateFrom, $dateTo),
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Schulbegleiter:innen-Vertretung'],
            ]),
        ]);
    }

    /**
     * Planungsansicht: abwesende Begleitungen im Zeitraum, je Begleitung deren
     * Kinder mit bestehendem oder noch offenem Vertretungsbedarf.
     */
    public function plan(): void
    {
        if (!$this->requireStaff()) return;

        [$dateFrom, $dateTo] = $this->range();

        $absences = AideSubstitution::getAbsentAidesForDateRange($dateFrom, $dateTo);
        foreach ($absences as &$absence) {
            $aideId = (int) $absence['aide_id'];
            $students = SchoolAide::getStudents($aideId);
            foreach ($students as &$student) {
                $student['substitution'] = AideSubstitution::findForAbsenceAndStudent(
                    (int) $absence['absence_id'],
                    (int) $student['id']
                );
            }
            unset($student);
            $absence['students'] = $students;
            $absence['available_aides'] = AideSubstitution::getAvailableAides(
                $absence['date_from'],
                $absence['date_to'],
                $aideId
            );
        }
        unset($absence);

        CsrfMiddleware::generateToken();
        View::render('aide-substitution/plan', [
            'title' => 'Vertretung planen',
            'absences' => $absences,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'priorities' => AideSubstitution::PRIORITIES,
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Schulbegleiter:innen-Vertretung', 'url' => '/aide-substitution'],
                ['label' => 'Planen'],
            ]),
        ]);
    }

    /**
     * Vertretungsbedarf fuer (Abwesenheit, Kind) anlegen oder aktualisieren.
     */
    public function assign(): void
    {
        if (!$this->requireStaff()) return;

        $absentAideId = (int) ($_POST['absent_aide_id'] ?? 0);
        $studentId = (int) ($_POST['student_id'] ?? 0);
        $absenceAideId = (int) ($_POST['absence_aide_id'] ?? 0) ?: null;
        $substituteAideId = (int) ($_POST['substitute_aide_id'] ?? 0) ?: null;
        $priority = (int) ($_POST['priority'] ?? 3);
        $notes = trim($_POST['notes'] ?? '') ?: null;
        $dateFrom = $_POST['date_from'] ?? '';
        $dateTo = $_POST['date_to'] ?? '';

        if (!$absentAideId || !$studentId || !$dateFrom || !$dateTo) {
            App::setFlash('error', 'Unvollständige Vertretungsdaten.');
            App::redirect('/aide-substitution/plan');
            return;
        }

        if (!isset(AideSubstitution::PRIORITIES[$priority])) {
            $priority = 3;
        }
        $status = $substituteAideId ? 'geplant' : 'offen';

        $data = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'absent_aide_id' => $absentAideId,
            'student_id' => $studentId,
            'substitute_aide_id' => $substituteAideId,
            'absence_aide_id' => $absenceAideId,
            'priority' => $priority,
            'status' => $status,
            'notes' => $notes,
            'created_by' => $_SESSION['user_id'],
        ];

        $existing = $absenceAideId
            ? AideSubstitution::findForAbsenceAndStudent($absenceAideId, $studentId)
            : null;

        if ($existing) {
            AideSubstitution::update((int) $existing['id'], $data);
        } else {
            AideSubstitution::create($data);
        }

        App::setFlash('success', 'Vertretung gespeichert.');
        App::redirect('/aide-substitution/plan?date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo));
    }

    public function update(string $id): void
    {
        if (!$this->requireStaff()) return;

        $need = AideSubstitution::findById((int) $id);
        if (!$need) {
            App::setFlash('error', 'Vertretung nicht gefunden.');
            App::redirect('/aide-substitution');
            return;
        }

        $substituteAideId = (int) ($_POST['substitute_aide_id'] ?? 0) ?: null;
        $priority = (int) ($_POST['priority'] ?? $need['priority']);
        if (!isset(AideSubstitution::PRIORITIES[$priority])) {
            $priority = (int) $need['priority'];
        }

        AideSubstitution::update((int) $id, [
            'substitute_aide_id' => $substituteAideId,
            'priority' => $priority,
            'status' => $_POST['status'] ?? ($substituteAideId ? 'geplant' : 'offen'),
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ]);

        App::setFlash('success', 'Vertretung aktualisiert.');
        App::redirect('/aide-substitution');
    }

    public function delete(string $id): void
    {
        if (!$this->requireStaff()) return;

        AideSubstitution::delete((int) $id);
        App::setFlash('success', 'Vertretung gelöscht.');
        App::redirect('/aide-substitution');
    }

    /**
     * Vertretungen im Zeitraum veroeffentlichen, sodass die eingeteilten
     * Ersatz-Begleitungen sie in "Meine Vertretungen" sehen.
     */
    public function publish(): void
    {
        if (!$this->requireStaff()) return;

        $dateFrom = $_POST['date_from'] ?? '';
        $dateTo = $_POST['date_to'] ?? '';
        if (!$this->isValidDate($dateFrom) || !$this->isValidDate($dateTo)) {
            App::setFlash('error', 'Ungültiger Zeitraum.');
            App::redirect('/aide-substitution');
            return;
        }

        $count = AideSubstitution::publishRange($dateFrom, $dateTo, (int) $_SESSION['user_id']);

        Logger::audit(
            'publish_aide_substitution',
            $_SESSION['user_id'] ?? null,
            'AideSubstitution',
            0,
            'Schulbegleiter:innen-Vertretung veröffentlicht: ' . $dateFrom . ' – ' . $dateTo . ' (' . $count . ')'
        );

        App::setFlash('success', $count > 0
            ? 'Vertretungsplan veröffentlicht. Die eingeteilten Begleitungen sehen ihre Vertretungen.'
            : 'Keine Vertretungen im gewählten Zeitraum zum Veröffentlichen vorhanden.');
        App::redirect('/aide-substitution?date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo));
    }

    /**
     * Veroeffentlichung fuer den Zeitraum zuruecknehmen.
     */
    public function unpublish(): void
    {
        if (!$this->requireStaff()) return;

        $dateFrom = $_POST['date_from'] ?? '';
        $dateTo = $_POST['date_to'] ?? '';
        if (!$this->isValidDate($dateFrom) || !$this->isValidDate($dateTo)) {
            App::setFlash('error', 'Ungültiger Zeitraum.');
            App::redirect('/aide-substitution');
            return;
        }

        AideSubstitution::unpublishRange($dateFrom, $dateTo);

        Logger::audit(
            'unpublish_aide_substitution',
            $_SESSION['user_id'] ?? null,
            'AideSubstitution',
            0,
            'Schulbegleiter:innen-Vertretung zurückgezogen: ' . $dateFrom . ' – ' . $dateTo
        );

        App::setFlash('success', 'Veröffentlichung zurückgezogen.');
        App::redirect('/aide-substitution?date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo));
    }

    /**
     * PDF-Export des Vertretungsplans.
     * Personal (Admin/Schulleitung/Sekretariat): kompletter Plan im Zeitraum.
     * Schulbegleiter:in: die eigenen veroeffentlichten Vertretungen.
     */
    public function exportPdf(): void
    {
        $role = App::currentUserRole();

        if (in_array($role, self::STAFF_ROLES, true)) {
            if (!ModuleSettings::canAccess('school_aides', $role)) {
                App::setFlash('error', 'Das Modul Schulbegleiter:innen ist für Ihre Rolle nicht zugänglich.');
                App::redirect('/dashboard');
                return;
            }
            [$dateFrom, $dateTo] = $this->range();
            $needs = AideSubstitution::findByDateRange($dateFrom, $dateTo);
            $subtitle = 'Zeitraum: ' . date('d.m.Y', strtotime($dateFrom)) . ' – ' . date('d.m.Y', strtotime($dateTo));
            $filename = 'schulbegleiter-vertretung_' . $dateFrom . '_' . $dateTo . '.pdf';
        } elseif ($role === 'schulbegleiter') {
            if (!ModuleSettings::isModuleEnabled('school_aides')) {
                App::setFlash('error', 'Das Modul Schulbegleiter:innen ist derzeit deaktiviert.');
                App::redirect('/dashboard');
                return;
            }
            $aideId = SchoolAide::getAideIdByUserId((int) $_SESSION['user_id']);
            if (!$aideId) {
                App::setFlash('error', 'Kein Schulbegleiter:innen-Profil gefunden.');
                App::redirect('/dashboard');
                return;
            }
            $needs = AideSubstitution::findUpcomingForAide($aideId, date('Y-m-d'));
            $subtitle = 'Meine Vertretungen ab ' . date('d.m.Y');
            $filename = 'meine-vertretungen.pdf';
        } else {
            App::setFlash('error', 'Kein Zugriff.');
            App::redirect('/dashboard');
            return;
        }

        Logger::audit(
            'export_aide_substitution_pdf',
            $_SESSION['user_id'] ?? null,
            'AideSubstitution',
            0,
            'PDF-Export Schulbegleiter:innen-Vertretung'
        );

        $this->renderPlanPdf($needs, $subtitle, $filename);
    }

    /**
     * Sicht der Begleitung: Vertretungen, in denen sie als Ersatz eingeteilt ist.
     */
    public function aideView(): void
    {
        if (!ModuleSettings::isModuleEnabled('school_aides')) {
            App::setFlash('error', 'Das Modul Schulbegleiter:innen ist derzeit deaktiviert.');
            App::redirect('/dashboard');
            return;
        }

        $aideId = SchoolAide::getAideIdByUserId((int) $_SESSION['user_id']);
        if (!$aideId) {
            App::setFlash('error', 'Kein Schulbegleiter:innen-Profil gefunden.');
            App::redirect('/dashboard');
            return;
        }

        View::render('aide-substitution/aide-view', [
            'title' => 'Meine Vertretungen',
            'needs' => AideSubstitution::findUpcomingForAide($aideId, date('Y-m-d')),
            'priorities' => AideSubstitution::PRIORITIES,
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Meine Vertretungen'],
            ]),
        ]);
    }

    /**
     * Zeitraum aus GET lesen; Default: heute bis in einer Woche.
     *
     * @return array{0:string,1:string}
     */
    private function range(): array
    {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d', strtotime('+7 days'));
        // Einfache Format-Absicherung.
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $dateFrom = date('Y-m-d');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $dateTo = date('Y-m-d', strtotime('+7 days'));
        }
        return [$dateFrom, $dateTo];
    }

    private function isValidDate(string $date): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
    }

    /**
     * PDF des Vertretungsplans erzeugen und als Download ausgeben.
     *
     * @param array<int,array<string,mixed>> $needs
     */
    private function renderPlanPdf(array $needs, string $subtitle, string $filename): void
    {
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Open-Classbook');
        $pdf->SetAuthor('Open-Classbook');
        $pdf->SetTitle('Schulbegleiter:innen-Vertretungsplan');
        $pdf->SetMargins(10, 15, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Schulbegleiter:innen-Vertretungsplan', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, $subtitle, 0, 1, 'C');
        $pdf->Ln(3);

        $statusLabels = ['offen' => 'Offen', 'geplant' => 'Geplant', 'erledigt' => 'Erledigt'];

        // Tabellenkopf
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(22, 8, 'Priorität', 1, 0, 'C', true);
        $pdf->Cell(45, 8, 'Zeitraum', 1, 0, 'C', true);
        $pdf->Cell(60, 8, 'Kind (Klasse)', 1, 0, 'C', true);
        $pdf->Cell(50, 8, 'Abwesende Begleitung', 1, 0, 'C', true);
        $pdf->Cell(50, 8, 'Ersatz-Begleitung', 1, 0, 'C', true);
        $pdf->Cell(20, 8, 'Status', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Notiz', 1, 1, 'C', true);

        $pdf->SetFont('helvetica', '', 9);
        if (empty($needs)) {
            $pdf->Cell(277, 8, 'Keine Vertretungen vorhanden.', 1, 1, 'C');
        } else {
            foreach ($needs as $n) {
                $priority = (int) $n['priority'];
                $priorityText = $priority . ' – ' . (AideSubstitution::PRIORITIES[$priority] ?? '');
                $period = date('d.m.Y', strtotime($n['date_from'])) . ' – ' . date('d.m.Y', strtotime($n['date_to']));
                $child = $n['student_lastname'] . ', ' . $n['student_firstname'] . ' (' . $n['class_name'] . ')';
                $absent = $n['absent_lastname'] . ', ' . $n['absent_firstname'];
                $substitute = !empty($n['substitute_aide_id'])
                    ? $n['substitute_lastname'] . ', ' . $n['substitute_firstname']
                    : '— offen —';
                $status = $statusLabels[$n['status']] ?? $n['status'];

                $pdf->Cell(22, 7, $priorityText, 1, 0, 'C');
                $pdf->Cell(45, 7, $period, 1, 0, 'C');
                $pdf->Cell(60, 7, $child, 1, 0, 'L');
                $pdf->Cell(50, 7, $absent, 1, 0, 'L');
                $pdf->Cell(50, 7, $substitute, 1, 0, 'L');
                $pdf->Cell(20, 7, $status, 1, 0, 'C');
                $pdf->Cell(30, 7, (string) ($n['notes'] ?? ''), 1, 1, 'L');
            }
        }

        $pdf->Output($filename, 'D');
        exit;
    }
}
