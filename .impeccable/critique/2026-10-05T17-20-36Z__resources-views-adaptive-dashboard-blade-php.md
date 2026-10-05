---
target: panel del estudiante
total_score: 25
max_score: 40
na_heuristics: 
p0_count: 0
p1_count: 2
target_identity: "file:C:\\laragon\\www\\aprende-guate-plus\\resources\\views\\adaptive\\dashboard.blade.php"
target_fingerprint: "sha256:eb7ba1f4632953d32bd064f9cbe9fb850eecdd1b8d642797c9c3c8616ba0b3fa"
target_path: "C:\\laragon\\www\\aprende-guate-plus\\resources\\views\\adaptive\\dashboard.blade.php"
timestamp: 2026-10-05T17-20-36Z
slug: resources-views-adaptive-dashboard-blade-php
---
⚠️ Sin navegador (Playwright MCP no conectó): ambas evaluaciones se hicieron sobre el código. Method: dual-agent (A: diseño · B: detector).

## Salud (Nielsen): 25/40, Aceptable (antes 21/40)
1 Estado 3 · 2 Lenguaje 3 · 3 Control 2 · 4 Consistencia 3 · 5 Prevención 2 · 6 Reconocimiento 3 · 7 Eficiencia 2 · 8 Estética 3 · 9 Recuperación 2 · 10 Ayuda 2

## Especificidad
~60% propio (meta, fases, retos, hora de Guatemala), ~40% intercambiable (banda oscura + rejilla 2/3-1/3). El empleo no aparece hasta el final.
Detector: 14 gray-on-color, 13 falsos positivos; 1 borde (× en hover, 4.17:1, glifo grande).

## Problemas prioritarios
- [P1] Mensajes 'info' nunca se muestran (DiagnosticController:48,59; TopicController:101); /diagnostico no avisa que reemplaza la ruta; eyebrow y blur en esa página. → harden, clarify
- [P1] Casos borde: ruta con 0 fases ("Te falta 1 fase"), mentor nulo ("con "), sesión aceptada sin fecha cae a "Pedir una mentoría", reto "preparando" no se actualiza, título completo sin límite. → harden
- [P2] Jerarquía en curso: h1 = meta escrita por el usuario (hasta 160 car.) empuja el botón en celular; dos botones primarios (Continuar / Abrir reto). → layout, typeset
- [P2] Espera y fallo: recarga completa cada 5 s repite la animación y molesta a lectores de pantalla; el fallo no ofrece alternativa humana. → harden, animate
- [P3] Final poco celebrado y sin enlace al empleo. → delight

## Personas
Jordan: sin recorrido y con Quetzi desactivada (@if (false), commit 836996f) nadie explica fases, mini-quiz ni retos. Casey: meta larga empuja el CTA; burbuja de chat tapa el enlace del tutor. Sam: sin anillo de foco visible en la banda; hitos solo por color; recarga interrumpe. Adulto que cambia de área: la mentoría no aparece hasta tener ruta.

## Menores
Marcas de 25/50/75 casi invisibles en claro; hover de enlaces sin cambio en oscuro; paneles no clicables se elevan al pasar el mouse; DESIGN.md aún documenta el recorrido eliminado; faltan pruebas de los casos borde.
