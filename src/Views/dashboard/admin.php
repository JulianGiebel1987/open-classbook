<div class="page-header">
    <h1>Dashboard</h1>
</div>

<div class="dashboard-grid">
    <div class="widget">
        <div class="widget-value"><?= $stats['teachers'] ?? 0 ?></div>
        <div class="widget-label">Lehrkräfte</div>
    </div>
    <div class="widget">
        <div class="widget-value"><?= $stats['students'] ?? 0 ?></div>
        <div class="widget-label">Schüler:innen</div>
    </div>
    <div class="widget">
        <div class="widget-value"><?= $stats['classes'] ?? 0 ?></div>
        <div class="widget-label">Klassen</div>
    </div>
    <div class="widget">
        <div class="widget-value"><?= $stats['absent_teachers_today'] ?? 0 ?></div>
        <div class="widget-label">Abwesende Lehrkräfte heute</div>
    </div>
    <div class="widget">
        <div class="widget-value"><?= $stats['absent_students_today'] ?? 0 ?></div>
        <div class="widget-label">Abwesende Schüler heute</div>
    </div>
    <div class="widget">
        <div class="widget-value"><?= $stats['unexcused_absences'] ?? 0 ?></div>
        <div class="widget-label">Offene Fehlzeiten</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Schnellzugriff</h2>
    </div>
    <div class="btn-group flex-wrap">
        <a href="/users" class="btn">Benutzerverwaltung</a>
        <a href="/classes" class="btn btn-secondary">Klassenverwaltung</a>
        <a href="/classbook" class="btn btn-secondary">Klassenbuecher</a>
        <a href="/absences/students" class="btn btn-secondary">Schüler:innen-Fehlzeiten</a>
        <a href="/absences/teachers" class="btn btn-secondary">Lehrkraft-Abwesenheiten</a>
    </div>
</div>
