<div class="card">
    <div class="card-header">
        <h1>Datenschutzhinweise</h1>
    </div>

    <p class="text-muted">Stand: <?= date('d.m.Y') ?></p>

    <h2>1. Verantwortlicher</h2>
    <p>
        Verantwortlich fuer die Datenverarbeitung ist der Schultraeger bzw. die Schulleitung der jeweiligen Schule,
        die diese Instanz von Open-Classbook betreibt. Die Kontaktdaten entnehmen Sie bitte dem Impressum der Schule
        oder wenden Sie sich an die Schulleitung.
    </p>

    <h2>2. Zweck der Datenverarbeitung</h2>
    <p>Open-Classbook ist eine Schulverwaltungsplattform. Die Verarbeitung personenbezogener Daten erfolgt zur Erfuellung des schulischen Bildungs- und Erziehungsauftrags, insbesondere fuer:</p>
    <ul>
        <li>Verwaltung von Schueler- und Lehrerdaten (Name, Klasse, Faecher)</li>
        <li>Fuehrung des digitalen Klassenbuchs (Unterrichtsinhalte, Anwesenheit)</li>
        <li>Erfassung und Verwaltung von Fehlzeiten</li>
        <li>Schulinterne Kommunikation (Nachrichtensystem)</li>
        <li>Dateiverwaltung fuer schulische Dokumente</li>
        <li>Erstellung von Listen und Berichten</li>
    </ul>

    <h2>3. Rechtsgrundlage</h2>
    <p>
        Die Verarbeitung erfolgt auf Grundlage von Art. 6 Abs. 1 lit. e DSGVO (Wahrnehmung einer Aufgabe im oeffentlichen Interesse)
        in Verbindung mit den jeweiligen landesrechtlichen Schulgesetzen und Verordnungen zur Datenverarbeitung an Schulen.
    </p>

    <h2>4. Verarbeitete Daten</h2>
    <h3>4.1 Benutzerkonten</h3>
    <ul>
        <li>Benutzername, E-Mail-Adresse, Rolle (z.B. Lehrkraft, Sekretariat)</li>
        <li>Passwort-Hash (das Passwort selbst wird nicht gespeichert)</li>
        <li>Zeitpunkt der letzten Anmeldung</li>
    </ul>

    <h3>4.2 Schuelerdaten</h3>
    <ul>
        <li>Vorname, Nachname, Klassenzugehoerigkeit</li>
        <li>Geburtsdatum (optional)</li>
        <li>E-Mail-Adresse der Erziehungsberechtigten (optional)</li>
        <li>Fehlzeiten (Datum, Art, Entschuldigung)</li>
    </ul>

    <h3>4.3 Lehrerdaten</h3>
    <ul>
        <li>Vorname, Nachname, Kuerzel</li>
        <li>E-Mail-Adresse, Faecher, Klassenzuordnung</li>
        <li>Fehlzeiten</li>
    </ul>

    <h3>4.4 Klassenbucheintraege</h3>
    <ul>
        <li>Unterrichtsinhalte, Datum, Stunde</li>
        <li>Anwesenheitsinformationen</li>
        <li>Bemerkungen</li>
    </ul>

    <h3>4.5 Technische Daten</h3>
    <ul>
        <li>Fehlgeschlagene Login-Versuche werden pseudonymisiert protokolliert (IP-Adresse gekuerzt, Zeitstempel)</li>
        <li>Session-Daten zur Aufrechterhaltung der Anmeldung</li>
    </ul>

    <h2>5. Speicherdauer</h2>
    <p>
        Personenbezogene Daten werden nur so lange gespeichert, wie es fuer die Erfuellung des Verarbeitungszwecks
        erforderlich ist oder gesetzliche Aufbewahrungsfristen dies vorschreiben. Klassenbucheintraege werden
        entsprechend der landesrechtlichen Vorgaben aufbewahrt. Nach Ablauf der Aufbewahrungsfrist werden die Daten
        geloescht.
    </p>

    <h2>6. Datensicherheit</h2>
    <p>Zum Schutz Ihrer Daten werden folgende technische und organisatorische Massnahmen eingesetzt:</p>
    <ul>
        <li>Verschluesselte Uebertragung aller Daten (HTTPS/TLS)</li>
        <li>Passwoerter werden ausschliesslich als bcrypt-Hash gespeichert</li>
        <li>Schutz vor unbefugtem Zugriff durch rollenbasierte Zugriffskontrolle (RBAC)</li>
        <li>Schutz vor gaengigen Angriffen (SQL-Injection, XSS, CSRF)</li>
        <li>Automatische Sitzungsbeendigung nach 60 Minuten Inaktivitaet</li>
        <li>Temporaere Kontosperrung nach 5 fehlgeschlagenen Anmeldeversuchen</li>
        <li>On-Premises-Betrieb auf Servern des Schultraegers (keine Cloud-Dienste)</li>
    </ul>

    <h2>7. Weitergabe an Dritte</h2>
    <p>
        Es erfolgt keine Weitergabe personenbezogener Daten an Dritte, es sei denn, dies ist gesetzlich vorgeschrieben.
        Die Anwendung wird On-Premises betrieben; es werden keine externen Cloud-Dienste oder Drittanbieter-Services
        eingebunden.
    </p>

    <h2>8. Ihre Rechte</h2>
    <p>Sie haben nach der DSGVO folgende Rechte:</p>
    <ul>
        <li><strong>Auskunft</strong> (Art. 15 DSGVO): Sie koennen Auskunft ueber Ihre gespeicherten Daten verlangen.</li>
        <li><strong>Berichtigung</strong> (Art. 16 DSGVO): Sie koennen die Korrektur unrichtiger Daten verlangen.</li>
        <li><strong>Loeschung</strong> (Art. 17 DSGVO): Sie koennen die Loeschung Ihrer Daten verlangen, sofern keine gesetzlichen Aufbewahrungspflichten entgegenstehen.</li>
        <li><strong>Einschraenkung</strong> (Art. 18 DSGVO): Sie koennen die Einschraenkung der Verarbeitung verlangen.</li>
        <li><strong>Widerspruch</strong> (Art. 21 DSGVO): Sie koennen der Verarbeitung widersprechen.</li>
        <li><strong>Beschwerde</strong>: Sie haben das Recht, sich bei der zustaendigen Datenschutzaufsichtsbehoerde zu beschweren.</li>
    </ul>
    <p>Bitte wenden Sie sich fuer die Ausuebung Ihrer Rechte an die Schulleitung oder den Datenschutzbeauftragten der Schule.</p>

    <h2>9. Datenschutzbeauftragter</h2>
    <p>
        Den Datenschutzbeauftragten der Schule erreichen Sie ueber die Schulleitung oder das Sekretariat.
        Die Kontaktdaten finden Sie auf der Website der Schule.
    </p>

    <p style="margin-top: var(--spacing-lg);">
        <a href="/login" class="btn btn-secondary">Zurueck zur Anmeldung</a>
    </p>
</div>
