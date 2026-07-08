<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\AideSubstitution;
use OpenClassbook\Models\SchoolAide;
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

        View::render('aide-substitution/index', [
            'title' => 'Schulbegleiter:innen-Vertretung',
            'needs' => AideSubstitution::findByDateRange($dateFrom, $dateTo),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'priorities' => AideSubstitution::PRIORITIES,
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
     * Sicht der Begleitung: Vertretungen, in denen sie als Ersatz eingeteilt ist.
     */
    public function aideView(): void
    {
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
}
