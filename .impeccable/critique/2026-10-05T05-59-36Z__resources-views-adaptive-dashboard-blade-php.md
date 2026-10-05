---
target: panel del estudiante
total_score: 21
max_score: 40
na_heuristics: 
p0_count: 1
p1_count: 3
target_identity: "file:C:\\laragon\\www\\aprende-guate-plus\\resources\\views\\adaptive\\dashboard.blade.php"
target_fingerprint: "sha256:5ce2419af2ee5cbae47c7edb215303c9fdfe1325ea1cef153fc900b6ac393f88"
target_path: "C:\\laragon\\www\\aprende-guate-plus\\resources\\views\\adaptive\\dashboard.blade.php"
timestamp: 2026-10-05T05-59-36Z
slug: resources-views-adaptive-dashboard-blade-php
---
Method: dual-agent (A: diseño · B: detector)

## Puntuación de salud (Nielsen): 21/40, Aceptable
| # | Heurística | Puntos | Problema clave |
|---|---|---|---|
| 1 | Visibilidad del estado | 1 | Sin estados de ruta generándose / fallida / completada; la mentoría no muestra fecha ni hora |
| 2 | Lenguaje del mundo real | 2 | "Laboratorio / Explorar pruebas" mientras el producto dice Retos; insignia "Personalizada" vacía |
| 3 | Control y libertad | 2 | El botón principal con ruta activa es "Actualizar mi diagnóstico", que reemplaza la ruta |
| 4 | Consistencia | 2 | "Iniciar diagnóstico" lleva a /mi-ruta; error de especificidad en .learning-stat |
| 5 | Prevención de errores | 2 | Rediagnosticar no advierte que reemplaza la ruta; "Explorar pruebas" lleva a retos bloqueados |
| 6 | Reconocer en vez de recordar | 3 | Tarjetas rotuladas; la fase siguiente aparece con ruta activa |
| 7 | Flexibilidad y eficiencia | 2 | "Continuar fase" lleva a /mi-ruta y no a la fase |
| 8 | Estética y minimalismo | 2 | Tres cifras en cero en estado vacío; 6 acciones; dos CTA primarias casi iguales |
| 9 | Recuperación de errores | 1 | Una generación fallida se ve igual que "nunca empezó" |
| 10 | Ayuda y documentación | 3 | Recorrido y tarjeta del tutor; el recorrido de 8 pasos tapa el contenido al cargar |

## Veredicto de especificidad
Intercambiable: banda de saludo + 3 tarjetas iguales + panel 3/5 y 2/5. No muestra la meta (path->goal), el empleo, los hitos de retos 25/50/75/100 ni el perfil profesional. Detector: 10 gray-on-color en CLI, todos falsos positivos o debatibles (hover, ternarios Alpine; slate-950 sobre blue-500 = 6.5:1). Overlay: layout-transition (padding), gpt-thin-border-wide-shadow (popup del recorrido), 4 dark-glow en oscuro. Medición 390px: 16 objetivos táctiles <44px, 4 textos de 11px en el pie, sin desborde.

## Problemas prioritarios
- [P0] Estados del ciclo de vida faltantes (generando, fallida, completada): el controlador solo lee active(). → /impeccable harden
- [P1] Siguiente paso duplicado e indirecto; CTA principal semidestructiva; en celular el paso real queda a ~1158px. → /impeccable layout + clarify
- [P1] .learning-stat strong vence a text-lg: "Aún sin sesión" sale a 30px/800; descripción en 700. → /impeccable typeset
- [P1] No muestra la meta ni el arco al empleo. → /impeccable shape / bolder
- [P2] En oscuro la banda se disuelve en el fondo; degradado from-blue-50 to-blue-50 sin efecto. → /impeccable polish

## Personas
Jordan: dos botones de inicio con nombres distintos; "pruebas" suena a examen; recorrido que cambia de página.
Casey: popup tapa el CTA; paso real lejos; enlaces de 20px de alto; insignia estirada a 308px.
Sam: 👋 en el h1; flechas en nombres de enlaces; barra sin role=progressbar.
Ana (19, Villa Nueva, primer empleo): no ve su meta; mentoría sin día ni hora; su ruta terminada desaparece.

## Observaciones menores
$profile sin usar; bases de tarjetas desalineadas; progreso solo por fases; botón ⚑ flotante sobre el pie; blobs blur-3xl costosos en gama baja; el recorrido navega a /perfil-profesional y /notificaciones.

## Preguntas
¿Una sola frase "Tu meta: ‹puesto›, te faltan N temas" en vez de tres tarjetas? ¿Por qué la acción más visible borra la ruta? ¿Qué ve quien termina? ¿El recorrido de 8 pasos se gana la interrupción?
