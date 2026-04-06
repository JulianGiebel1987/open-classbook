<div class="card" style="max-width: 900px; margin: 0 auto;">
    <div class="card-header" style="border-bottom: 2px solid var(--color-primary); padding-bottom: var(--spacing-md); text-align: center;">
        <h1 style="font-size: var(--font-size-2xl); color: var(--color-primary);">Datenschutzhinweise</h1>
        <p class="text-muted" style="margin-top: var(--spacing-xs);">Stand: <?= date('d.m.Y') ?></p>
    </div>

    <!-- 1. Verantwortlicher -->
    <div style="margin-top: var(--spacing-xl); padding: var(--spacing-lg); background: var(--color-primary-50); border-radius: var(--radius); border-left: 4px solid var(--color-primary);">
        <h2 style="font-size: var(--font-size-lg); margin-bottom: var(--spacing-sm);">1. Verantwortlicher</h2>
        <p>
            Verantwortlich für die Datenverarbeitung ist der Schultraeger bzw. die Schulleitung der jeweiligen Schule,
            die diese Instanz von Open-Classbook betreibt. Die Kontaktdaten entnehmen Sie bitte dem Impressum der Schule
            oder wenden Sie sich an die Schulleitung.
        </p>
    </div>

    <!-- 2. Zweck der Datenverarbeitung -->
    <div style="margin-top: var(--spacing-xl);">
        <h2 style="font-size: var(--font-size-lg); margin-bottom: var(--spacing-md); padding-bottom: var(--spacing-xs); border-bottom: 1px solid var(--color-border);">2. Zweck der Datenverarbeitung</h2>
        <p style="margin-bottom: var(--spacing-md);">Open-Classbook ist eine Schulverwaltungsplattform. Die Verarbeitung personenbezogener Daten erfolgt zur Erfuellung des schulischen Bildungs- und Erziehungsauftrags, insbesondere für:</p>
        <ul style="list-style: none; padding: 0;">
            <li style="padding: var(--spacing-sm) var(--spacing-md); margin-bottom: var(--spacing-xs); background: var(--color-bg); border-radius: var(--radius);">Verwaltung von Schüler- und Lehrerdaten (Name, Klasse, Fächer)</li>
            <li style="padding: var(--spacing-sm) var(--spacing-md); margin-bottom: var(--spacing-xs); background: var(--color-bg); border-radius: var(--radius);">Fuehrung des digitalen Klassenbuchs (Unterrichtsinhalte, Anwesenheit)</li>
            <li style="padding: var(--spacing-sm) var(--spacing-md); margin-bottom: var(--spacing-xs); background: var(--color-bg); border-radius: var(--radius);">Erfassung und Verwaltung von Fehlzeiten</li>
            <li style="padding: var(--spacing-sm) var(--spacing-md); margin-bottom: var(--spacing-xs); background: var(--color-bg); border-radius: var(--radius);">Schulinterne Kommunikation (Nachrichtensystem)</li>
            <li style="padding: var(--spacing-sm) var(--spacing-md); margin-bottom: var(--spacing-xs); background: var(--color-bg); border-radius: var(--radius);">Dateiverwaltung für schulische Dokumente</li>
            <li style="padding: var(--spacing-sm) var(--spacing-md); background: var(--color-bg); border-radius: var(--radius);">Erstellung von Listen und Berichten</li>
        </ul>
    </div>

    <!-- 3. Rechtsgrundlage -->
    <div style="margin-top: var(--spacing-xl);">
        <h2 style="font-size: var(--font-size-lg); margin-bottom: var(--spacing-md); padding-bottom: var(--spacing-xs); border-bottom: 1px solid var(--color-border);">3. Rechtsgrundlage</h2>
        <p>
            Die Verarbeitung erfolgt auf Grundlage von <strong>Art. 6 Abs. 1 lit. e DSGVO</strong> (Wahrnehmung einer Aufgabe im öffentlichen Interesse)
            in Verbindung mit den jeweiligen landesrechtlichen Schulgesetzen und Verordnungen zur Datenverarbeitung an Schulen.
        </p>
    </div>

    <!-- 4. Verarbeitete Daten -->
    <div style="margin-top: var(--spacing-xl);">
        <h2 style="font-size: var(--font-size-lg); margin-bottom: var(--spacing-md); padding-bottom: var(--spacing-xs); border-bottom: 1px solid var(--color-border);">4. Verarbeitete Daten</h2>

        <div style="display: grid; gap: var(--spacing-md); margin-top: var(--spacing-md);">
            <div style="padding: var(--spacing-md); background: var(--color-bg); border-radius: var(--radius);">
                <h3 style="font-size: var(--font-size-base); color: var(--color-primary); margin-bottom: var(--spacing-sm);">4.1 Benutzerkonten</h3>
                <ul style="margin-left: var(--spacing-lg); color: var(--color-text-light);">
                    <li style="margin-bottom: var(--spacing-xs);">Benutzername, E-Mail-Adresse, Rolle (z.B. Lehrkraft, Sekretariat)</li>
                    <li style="margin-bottom: var(--spacing-xs);">Passwort-Hash (das Passwort selbst wird nicht gespeichert)</li>
                    <li>Zeitpunkt der letzten Anmeldung</li>
                </ul>
            </div>

            <div style="padding: var(--spacing-md); background: var(--color-bg); border-radius: var(--radius);">
                <h3 style="font-size: var(--font-size-base); color: var(--color-primary); margin-bottom: var(--spacing-sm);">4.2 Schülerdaten</h3>
                <ul style="margin-left: var(--spacing-lg); color: var(--color-text-light);">
                    <li style="margin-bottom: var(--spacing-xs);">Vorname, Nachname, Klassenzugehoerigkeit</li>
                    <li style="margin-bottom: var(--spacing-xs);">Geburtsdatum (optional)</li>
                    <li style="margin-bottom: var(--spacing-xs);">E-Mail-Adresse der Erziehungsberechtigten (optional)</li>
                    <li>Fehlzeiten (Datum, Art, Entschuldigung)</li>
                </ul>
            </div>

            <div style="padding: var(--spacing-md); background: var(--color-bg); border-radius: var(--radius);">
                <h3 style="font-size: var(--font-size-base); color: var(--color-primary); margin-bottom: var(--spacing-sm);">4.3 Lehrerdaten</h3>
                <ul style="margin-left: var(--spacing-lg); color: var(--color-text-light);">
                    <li style="margin-bottom: var(--spacing-xs);">Vorname, Nachname, Kürzel</li>
                    <li style="margin-bottom: var(--spacing-xs);">E-Mail-Adresse, Fächer, Klassenzuordnung</li>
                    <li>Fehlzeiten</li>
                </ul>
            </div>

            <div style="padding: var(--spacing-md); background: var(--color-bg); border-radius: var(--radius);">
                <h3 style="font-size: var(--font-size-base); color: var(--color-primary); margin-bottom: var(--spacing-sm);">4.4 Klassenbucheintraege</h3>
                <ul style="margin-left: var(--spacing-lg); color: var(--color-text-light);">
                    <li style="margin-bottom: var(--spacing-xs);">Unterrichtsinhalte, Datum, Stunde</li>
                    <li style="margin-bottom: var(--spacing-xs);">Anwesenheitsinformationen</li>
                    <li>Bemerkungen</li>
                </ul>
            </div>

            <div style="padding: var(--spacing-md); background: var(--color-bg); border-radius: var(--radius);">
                <h3 style="font-size: var(--font-size-base); color: var(--color-primary); margin-bottom: var(--spacing-sm);">4.5 Technische Daten</h3>
                <ul style="margin-left: var(--spacing-lg); color: var(--color-text-light);">
                    <li style="margin-bottom: var(--spacing-xs);">Fehlgeschlagene Login-Versuche werden pseudonymisiert protokolliert (IP-Adresse gekuerzt, Zeitstempel)</li>
                    <li>Session-Daten zur Aufrechterhaltung der Anmeldung</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- 5. Speicherdauer -->
    <div style="margin-top: var(--spacing-xl);">
        <h2 style="font-size: var(--font-size-lg); margin-bottom: var(--spacing-md); padding-bottom: var(--spacing-xs); border-bottom: 1px solid var(--color-border);">5. Speicherdauer</h2>
        <p>
            Personenbezogene Daten werden nur so lange gespeichert, wie es für die Erfuellung des Verarbeitungszwecks
            erforderlich ist oder gesetzliche Aufbewahrungsfristen dies vorschreiben. Klassenbucheintraege werden
            entsprechend der landesrechtlichen Vorgaben aufbewahrt. Nach Ablauf der Aufbewahrungsfrist werden die Daten
            gelöscht.
        </p>
    </div>

    <!-- 6. Datensicherheit -->
    <div style="margin-top: var(--spacing-xl);">
        <h2 style="font-size: var(--font-size-lg); margin-bottom: var(--spacing-md); padding-bottom: var(--spacing-xs); border-bottom: 1px solid var(--color-border);">6. Datensicherheit</h2>
        <p style="margin-bottom: var(--spacing-md);">Zum Schutz Ihrer Daten werden folgende technische und organisatorische Massnahmen eingesetzt:</p>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: var(--spacing-sm);">
            <div style="padding: var(--spacing-md); background: var(--color-success-light); border-radius: var(--radius); font-size: var(--font-size-sm);">
                Verschlüsselte Übertragung aller Daten (HTTPS/TLS)
            </div>
            <div style="padding: var(--spacing-md); background: var(--color-success-light); border-radius: var(--radius); font-size: var(--font-size-sm);">
                Passwoerter werden ausschliesslich als bcrypt-Hash gespeichert
            </div>
            <div style="padding: var(--spacing-md); background: var(--color-success-light); border-radius: var(--radius); font-size: var(--font-size-sm);">
                Rollenbasierte Zugriffskontrolle (RBAC)
            </div>
            <div style="padding: var(--spacing-md); background: var(--color-success-light); border-radius: var(--radius); font-size: var(--font-size-sm);">
                Schutz vor SQL-Injection, XSS und CSRF
            </div>
            <div style="padding: var(--spacing-md); background: var(--color-success-light); border-radius: var(--radius); font-size: var(--font-size-sm);">
                Automatische Sitzungsbeendigung nach 60 Min. Inaktivitaet
            </div>
            <div style="padding: var(--spacing-md); background: var(--color-success-light); border-radius: var(--radius); font-size: var(--font-size-sm);">
                Kontosperrung nach 5 fehlgeschlagenen Anmeldeversuchen
            </div>
            <div style="padding: var(--spacing-md); background: var(--color-success-light); border-radius: var(--radius); font-size: var(--font-size-sm);">
                On-Premises-Betrieb auf Servern des Schultraegers
            </div>
        </div>
    </div>

    <!-- 7. Weitergabe an Dritte -->
    <div style="margin-top: var(--spacing-xl);">
        <h2 style="font-size: var(--font-size-lg); margin-bottom: var(--spacing-md); padding-bottom: var(--spacing-xs); border-bottom: 1px solid var(--color-border);">7. Weitergabe an Dritte</h2>
        <p>
            Es erfolgt <strong>keine Weitergabe</strong> personenbezogener Daten an Dritte, es sei denn, dies ist gesetzlich vorgeschrieben.
            Die Anwendung wird On-Premises betrieben; es werden keine externen Cloud-Dienste oder Drittanbieter-Services
            eingebunden.
        </p>
    </div>

    <!-- 8. Ihre Rechte -->
    <div style="margin-top: var(--spacing-xl);">
        <h2 style="font-size: var(--font-size-lg); margin-bottom: var(--spacing-md); padding-bottom: var(--spacing-xs); border-bottom: 1px solid var(--color-border);">8. Ihre Rechte</h2>
        <p style="margin-bottom: var(--spacing-md);">Sie haben nach der DSGVO folgende Rechte:</p>
        <div style="display: grid; gap: var(--spacing-sm);">
            <div style="padding: var(--spacing-md); background: var(--color-info-light); border-radius: var(--radius); border-left: 3px solid var(--color-info);">
                <strong>Auskunft</strong> <span class="text-muted">(Art. 15 DSGVO)</span><br>
                <span style="color: var(--color-text-light);">Sie können Auskunft über Ihre gespeicherten Daten verlangen.</span>
            </div>
            <div style="padding: var(--spacing-md); background: var(--color-info-light); border-radius: var(--radius); border-left: 3px solid var(--color-info);">
                <strong>Berichtigung</strong> <span class="text-muted">(Art. 16 DSGVO)</span><br>
                <span style="color: var(--color-text-light);">Sie können die Korrektur unrichtiger Daten verlangen.</span>
            </div>
            <div style="padding: var(--spacing-md); background: var(--color-info-light); border-radius: var(--radius); border-left: 3px solid var(--color-info);">
                <strong>Loeschung</strong> <span class="text-muted">(Art. 17 DSGVO)</span><br>
                <span style="color: var(--color-text-light);">Sie können die Loeschung Ihrer Daten verlangen, sofern keine gesetzlichen Aufbewahrungspflichten entgegenstehen.</span>
            </div>
            <div style="padding: var(--spacing-md); background: var(--color-info-light); border-radius: var(--radius); border-left: 3px solid var(--color-info);">
                <strong>Einschraenkung</strong> <span class="text-muted">(Art. 18 DSGVO)</span><br>
                <span style="color: var(--color-text-light);">Sie können die Einschraenkung der Verarbeitung verlangen.</span>
            </div>
            <div style="padding: var(--spacing-md); background: var(--color-info-light); border-radius: var(--radius); border-left: 3px solid var(--color-info);">
                <strong>Widerspruch</strong> <span class="text-muted">(Art. 21 DSGVO)</span><br>
                <span style="color: var(--color-text-light);">Sie können der Verarbeitung widersprechen.</span>
            </div>
            <div style="padding: var(--spacing-md); background: var(--color-info-light); border-radius: var(--radius); border-left: 3px solid var(--color-info);">
                <strong>Beschwerde</strong><br>
                <span style="color: var(--color-text-light);">Sie haben das Recht, sich bei der zustaendigen Datenschutzaufsichtsbehoerde zu beschweren.</span>
            </div>
        </div>
        <p style="margin-top: var(--spacing-md); color: var(--color-text-light);">
            Bitte wenden Sie sich für die Ausuebung Ihrer Rechte an die Schulleitung oder den Datenschutzbeauftragten der Schule.
        </p>
    </div>

    <!-- 9. Datenschutzbeauftragter -->
    <div style="margin-top: var(--spacing-xl); padding: var(--spacing-lg); background: var(--color-primary-50); border-radius: var(--radius); border-left: 4px solid var(--color-primary);">
        <h2 style="font-size: var(--font-size-lg); margin-bottom: var(--spacing-sm);">9. Datenschutzbeauftragter</h2>
        <p>
            Den Datenschutzbeauftragten der Schule erreichen Sie über die Schulleitung oder das Sekretariat.
            Die Kontaktdaten finden Sie auf der Website der Schule.
        </p>
    </div>

    <div style="margin-top: var(--spacing-2xl); padding-top: var(--spacing-lg); border-top: 1px solid var(--color-border);">
        <a href="/login" class="btn btn-secondary">Zurück zur Anmeldung</a>
    </div>
</div>
