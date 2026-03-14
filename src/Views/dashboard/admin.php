<div class="page-header">
    <h1>Dashboard</h1>
</div>

<div class="dashboard-grid">
    <div class="widget">
        <div class="widget-value"><?= $stats['teachers'] ?? 0 ?></div>
        <div class="widget-label">Lehrkraefte</div>
    </div>
    <div class="widget">
        <div class="widget-value"><?= $stats['students'] ?? 0 ?></div>
        <div class="widget-label">Schueler/innen</div>
    </div>
    <div class="widget">
        <div class="widget-value"><?= $stats['classes'] ?? 0 ?></div>
        <div class="widget-label">Klassen</div>
    </div>
    <div class="widget">
        <div class="widget-value"><?= $stats['absent_teachers_today'] ?? 0 ?></div>
        <div class="widget-label">Abwesende Lehrkraefte heute</div>
    </div>
    <div class="widget">
        <div class="widget-value"><?= $stats['absent_students_today'] ?? 0 ?></div>
        <div class="widget-label">Abwesende Schueler heute</div>
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
        <a href="/absences/students" class="btn btn-secondary">Schueler-Fehlzeiten</a>
        <a href="/absences/teachers" class="btn btn-secondary">Lehrer-Fehlzeiten</a>
    </div>
</div>
