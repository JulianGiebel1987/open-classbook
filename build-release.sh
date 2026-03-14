#!/bin/bash

# Open-Classbook Release-Build-Skript
#
# Erstellt ein ZIP-Paket ohne Entwicklungsdateien.
# Verwendung: ./build-release.sh [version]
# Beispiel:   ./build-release.sh 1.0.0

set -e

VERSION="${1:-dev}"
PROJECT_NAME="open-classbook"
BUILD_DIR="build"
RELEASE_NAME="${PROJECT_NAME}-v${VERSION}"
RELEASE_ZIP="${RELEASE_NAME}.zip"

echo "=== Open-Classbook Release Build ==="
echo "Version: ${VERSION}"
echo ""

# Build-Verzeichnis vorbereiten
rm -rf "${BUILD_DIR}"
mkdir -p "${BUILD_DIR}/${RELEASE_NAME}"

echo "Dateien kopieren..."

# Quellcode und Assets kopieren
cp -r public/ "${BUILD_DIR}/${RELEASE_NAME}/public/"
cp -r src/ "${BUILD_DIR}/${RELEASE_NAME}/src/"
cp -r config/ "${BUILD_DIR}/${RELEASE_NAME}/config/"
cp -r database/ "${BUILD_DIR}/${RELEASE_NAME}/database/"

# Templates kopieren (falls vorhanden)
if [ -d "templates/" ]; then
    cp -r templates/ "${BUILD_DIR}/${RELEASE_NAME}/templates/"
fi

# Dokumentation kopieren
cp composer.json "${BUILD_DIR}/${RELEASE_NAME}/"
cp composer.lock "${BUILD_DIR}/${RELEASE_NAME}/"
cp install.php "${BUILD_DIR}/${RELEASE_NAME}/"
cp README.md "${BUILD_DIR}/${RELEASE_NAME}/"
cp INSTALL.md "${BUILD_DIR}/${RELEASE_NAME}/"
cp UPDATE.md "${BUILD_DIR}/${RELEASE_NAME}/"
cp CHANGELOG.md "${BUILD_DIR}/${RELEASE_NAME}/"
cp ADMIN_HANDBUCH.md "${BUILD_DIR}/${RELEASE_NAME}/"

# LICENSE kopieren (falls vorhanden)
if [ -f "LICENSE" ]; then
    cp LICENSE "${BUILD_DIR}/${RELEASE_NAME}/"
fi

# Storage-Verzeichnisse erstellen (leer)
mkdir -p "${BUILD_DIR}/${RELEASE_NAME}/storage/logs"
mkdir -p "${BUILD_DIR}/${RELEASE_NAME}/storage/uploads"
mkdir -p "${BUILD_DIR}/${RELEASE_NAME}/storage/cache"

# .htaccess fuer Storage-Schutz
echo "Deny from all" > "${BUILD_DIR}/${RELEASE_NAME}/storage/.htaccess"

# .gitkeep-Dateien in leere Verzeichnisse
touch "${BUILD_DIR}/${RELEASE_NAME}/storage/logs/.gitkeep"
touch "${BUILD_DIR}/${RELEASE_NAME}/storage/uploads/.gitkeep"
touch "${BUILD_DIR}/${RELEASE_NAME}/storage/cache/.gitkeep"

# config.php durch config.example.php ersetzen (keine Zugangsdaten ausliefern)
rm -f "${BUILD_DIR}/${RELEASE_NAME}/config/config.php"

# Entwicklungsdateien entfernen
rm -rf "${BUILD_DIR}/${RELEASE_NAME}/tests/"
rm -f "${BUILD_DIR}/${RELEASE_NAME}/phpunit.xml.dist"
rm -f "${BUILD_DIR}/${RELEASE_NAME}/CLAUDE.md"
rm -f "${BUILD_DIR}/${RELEASE_NAME}/PROJECT_PLAN.md"
rm -f "${BUILD_DIR}/${RELEASE_NAME}/TESTING_CHECKLIST.md"
rm -f "${BUILD_DIR}/${RELEASE_NAME}/Open-Classbook-PRD.docx"

echo "Composer-Abhaengigkeiten installieren (ohne Dev)..."
cd "${BUILD_DIR}/${RELEASE_NAME}"
composer install --no-dev --optimize-autoloader --no-interaction --quiet 2>/dev/null || {
    echo "WARNUNG: composer install fehlgeschlagen. vendor/ muss manuell installiert werden."
}
cd ../..

echo "ZIP-Archiv erstellen..."
cd "${BUILD_DIR}"
zip -r -q "../${RELEASE_ZIP}" "${RELEASE_NAME}/"
cd ..

# Aufraumen
rm -rf "${BUILD_DIR}"

# Ergebnis
SIZE=$(du -h "${RELEASE_ZIP}" | cut -f1)
echo ""
echo "=== Release erstellt ==="
echo "Datei:   ${RELEASE_ZIP}"
echo "Groesse: ${SIZE}"
echo ""
echo "Upload als GitHub Release oder direkt an Schultraeger verteilen."
