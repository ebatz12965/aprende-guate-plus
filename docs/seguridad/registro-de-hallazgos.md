# Registro de hallazgos

Una fila por hallazgo, también los descartados. La evidencia es la ruta al informe o a la captura. Los informes viven fuera de git, en `docs/seguridad/informes/`.

**Riesgo:**
- Crítico: se pueden ver o cambiar datos de otras personas, o tomar una cuenta.
- Alto: se salta un permiso o se expone un secreto.
- Medio: se requiere una condición adicional para explotarlo.
- Bajo: configuración o endurecimiento.
- Info: no requiere acción.

**Estado:** Abierto · Corregido · Verificado (se volvió a probar y ya no ocurre) · Aceptado (riesgo asumido, con motivo) · Falso positivo.

| ID | Fecha | Herramienta y versión | Qué se probó | Hallazgo | Evidencia | Riesgo | Corrección | Estado | Nueva prueba |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| H-001 | 2026-10-05 | gitleaks 8.30.1 | Historial completo (61 commits) y archivos del proyecto | El historial no tiene secretos. `storage/logs/laravel.log` (local, fuera de git) guarda cookies `XSRF-TOKEN` y de sesión, `_token` y `remember_token`, que llegaron ahí por consultas fallidas de Telescope (hasta 2026-08). | Salida de `gitleaks git` y `gitleaks dir` | Bajo | Vaciar el log local; Telescope apagado en producción | Abierto | — |
| H-002 | 2026-10-05 | composer audit (Composer 2.8.11) | Dependencias PHP según `composer.lock` | Sin avisos | `composer-audit.json` | Info | — | Verificado | — |
| H-003 | 2026-10-05 | npm audit (npm 10.9.3) | Dependencias JS según `package-lock.json` | `brace-expansion` (alto, denegación de servicio): se actualizó con `npm audit fix`. Sigue `braces` (alto, sin versión corregida) y `postcss-selector-parser` (moderado); los dos vienen de las herramientas de compilación de Tailwind 3 y no llegan al navegador. | `npm-audit.json` | Bajo | Se resuelve al migrar a Tailwind 4 | Aceptado | `npm audit --omit=dev`: 0 avisos |
| H-004 | 2026-10-05 | Larastan 3.12 (nivel 5) | `app`, `routes`, `database` | 119 hallazgos previos; 68 son relaciones de Eloquent sin tipo. Para revisar: `notify()` sobre un tipo genérico (9) y `format()` / `isFuture()` sobre fechas sin cast (3). | `larastan.json`, `phpstan-baseline.neon` | Bajo | Tipar relaciones y casts de fechas; regenerar la línea base | Abierto | — |

<!-- Plantilla para copiar:
| H-00X | AAAA-MM-DD | Herramienta x.y | Ruta, pantalla o petición | Qué pasó | informes/AAAA-MM-DD/archivo | Riesgo | Qué se cambió (commit) | Estado | Fecha y resultado |
-->
