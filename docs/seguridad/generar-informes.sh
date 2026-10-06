#!/usr/bin/env bash
# Genera los informes de seguridad del día en docs/seguridad/informes/AAAA-MM-DD/.
# Uso (desde la raíz del proyecto, en Git Bash): bash docs/seguridad/generar-informes.sh
# Variables opcionales: GITLEAKS=/ruta/a/gitleaks(.exe)

set -u
cd "$(dirname "$0")/../.."

out="docs/seguridad/informes/$(date +%F)"
mkdir -p "$out"
gitleaks="${GITLEAKS:-gitleaks}"
failed=()

step() { printf '\n== %s\n' "$1"; }

step "Secretos en el historial (gitleaks)"
if command -v "$gitleaks" > /dev/null 2>&1; then
    "$gitleaks" version
    "$gitleaks" git --no-banner --redact --report-format json --report-path "$out/gitleaks.json" . || failed+=(gitleaks)
    # Archivos sin commit (las carpetas de .gitignore se excluyen en .gitleaks.toml).
    "$gitleaks" dir --no-banner --redact --report-format json --report-path "$out/gitleaks-archivos.json" . || failed+=(gitleaks-archivos)
else
    echo "gitleaks no está instalado: winget install Gitleaks.Gitleaks (o define GITLEAKS=/ruta/gitleaks.exe)"
    failed+=(gitleaks-no-instalado)
fi

step "Dependencias PHP (composer audit)"
composer --version
composer audit --locked --format=json > "$out/composer-audit.json" || failed+=(composer)
composer audit --locked

step "Dependencias JS (npm audit)"
echo "npm $(npm --version)"
npm audit --json > "$out/npm-audit.json"
npm audit --omit=dev --audit-level=high || failed+=(npm)

step "Análisis estático (Larastan)"
vendor/bin/phpstan --version
vendor/bin/phpstan analyse --memory-limit=1G --no-progress --error-format=json > "$out/larastan.json"
vendor/bin/phpstan analyse --memory-limit=1G --no-progress || failed+=(larastan)

printf '\nInformes en %s\n' "$out"
if [ ${#failed[@]} -gt 0 ]; then
    echo "Revisar: ${failed[*]}"
    exit 1
fi
echo "Sin hallazgos nuevos."
