---
name: Aprende Guate
description: Plataforma de aprendizaje con IA para conseguir empleo en Guatemala
colors:
  azul-lago: "#2196F3"
  azul-lago-profundo: "#1976D2"
  azul-lago-intenso: "#1565C0"
  azul-lago-bruma: "#E3F2FD"
  celeste-claro: "#81D4FA"
  cielo-de-atitlan: "#020617"
  tinta: "#0F172A"
  texto: "#1E293B"
  texto-control: "#334155"
  texto-secundario: "#475569"
  texto-apagado: "#64748B"
  borde: "#E2E8F0"
  fondo: "#F8FAFC"
  fondo-admin: "#F1F5F9"
  superficie: "#FFFFFF"
  borde-noche: "#334155"
  exito: "#047857"
  exito-suave: "#ECFDF5"
  error: "#BE123C"
  error-suave: "#FFF1F2"
  aviso: "#B45309"
  aviso-suave: "#FFFBEB"
  pendiente: "#F97316"
typography:
  display:
    fontFamily: "Figtree, ui-sans-serif, system-ui, sans-serif"
    fontSize: "36px"
    fontWeight: 800
    lineHeight: "40px"
  headline:
    fontFamily: "Figtree, ui-sans-serif, system-ui, sans-serif"
    fontSize: "30px"
    fontWeight: 800
    lineHeight: "36px"
    letterSpacing: "-0.025em"
  title-large:
    fontFamily: "Figtree, ui-sans-serif, system-ui, sans-serif"
    fontSize: "24px"
    fontWeight: 800
    lineHeight: "32px"
  title:
    fontFamily: "Figtree, ui-sans-serif, system-ui, sans-serif"
    fontSize: "20px"
    fontWeight: 800
    lineHeight: "28px"
  body:
    fontFamily: "Figtree, ui-sans-serif, system-ui, sans-serif"
    fontSize: "16px"
    fontWeight: 400
    lineHeight: "24px"
  body-small:
    fontFamily: "Figtree, ui-sans-serif, system-ui, sans-serif"
    fontSize: "14px"
    fontWeight: 400
    lineHeight: "24px"
  button:
    fontFamily: "Figtree, ui-sans-serif, system-ui, sans-serif"
    fontSize: "14px"
    fontWeight: 700
    lineHeight: "20px"
  caption:
    fontFamily: "Figtree, ui-sans-serif, system-ui, sans-serif"
    fontSize: "12px"
    fontWeight: 700
    lineHeight: "16px"
  label:
    fontFamily: "Figtree, ui-sans-serif, system-ui, sans-serif"
    fontSize: "12px"
    fontWeight: 800
    lineHeight: "16px"
    letterSpacing: "0.12em"
rounded:
  sm: "8px"
  md: "12px"
  lg: "16px"
  xl: "24px"
  full: "9999px"
spacing:
  xs: "4px"
  sm: "8px"
  md: "16px"
  lg: "24px"
  xl: "32px"
  2xl: "40px"
components:
  button-primary:
    backgroundColor: "{colors.azul-lago-profundo}"
    textColor: "{colors.superficie}"
    typography: "{typography.button}"
    rounded: "{rounded.md}"
    padding: "10px 16px"
  button-primary-hover:
    backgroundColor: "{colors.azul-lago-intenso}"
    textColor: "{colors.superficie}"
  button-secondary:
    backgroundColor: "{colors.superficie}"
    textColor: "{colors.texto-control}"
    typography: "{typography.button}"
    rounded: "{rounded.md}"
    padding: "10px 16px"
  button-on-dark:
    backgroundColor: "{colors.azul-lago}"
    textColor: "{colors.cielo-de-atitlan}"
    typography: "{typography.button}"
    rounded: "{rounded.md}"
    padding: "12px 20px"
  card:
    backgroundColor: "{colors.superficie}"
    textColor: "{colors.texto}"
    rounded: "{rounded.lg}"
    padding: "24px"
  welcome-band:
    backgroundColor: "{colors.cielo-de-atitlan}"
    textColor: "{colors.superficie}"
    rounded: "{rounded.xl}"
    padding: "32px 40px"
  badge:
    backgroundColor: "{colors.azul-lago-bruma}"
    textColor: "{colors.azul-lago-intenso}"
    typography: "{typography.caption}"
    rounded: "{rounded.full}"
    padding: "4px 12px"
  input:
    backgroundColor: "{colors.fondo}"
    textColor: "{colors.texto}"
    typography: "{typography.body-small}"
    rounded: "{rounded.md}"
    padding: "8px 12px"
  nav-link:
    textColor: "{colors.texto-secundario}"
    typography: "{typography.button}"
    rounded: "{rounded.md}"
    padding: "10px 12px"
  nav-link-active:
    backgroundColor: "{colors.azul-lago-profundo}"
    textColor: "{colors.superficie}"
    rounded: "{rounded.md}"
    padding: "10px 12px"
---

# Design System: Aprende Guate

## Overview

**Creative North Star: "Rumbo claro"**

Aprende Guate se diseña como una brújula profesional. Cada pantalla responde tres preguntas sin que nadie tenga que buscarlas: dónde estoy, cuánto he avanzado y cuál es mi siguiente paso. El sistema es sereno y ordenado, con superficies blancas sobre un fondo casi blanco, un único azul de identidad y tipografía firme que señala lo importante. La calidez no viene de la decoración sino del trato: textos que hablan de tú, esquinas suaves y una banda nocturna de bienvenida que saluda por su nombre a quien aprende.

Debe sentirse confiable y profesional (alguien que busca empleo la puede mostrar con orgullo), cercana y motivadora (no intimida a quien empieza desde cero), moderna (la IA trabaja de fondo sin convertirse en espectáculo) y clara (se entiende en un vistazo, también en el celular). La plataforma es de trabajo: la densidad es media, el contenido manda y el color se usa para orientar, no para adornar.

Rechazos confirmados: no debe parecerse a una plantilla genérica de IA (degradados morados, neones, tarjetas clonadas), ni a un aula infantil (colores primarios saturados, ilustraciones caricaturescas), ni a un banco o gobierno frío (gris rígido y burocrático), ni a una red social ruidosa (contadores y colores compitiendo por atención).

**Key Characteristics:**
- Un solo color de identidad, Azul Lago, sobre neutros fríos de la familia pizarra.
- Una banda nocturna (Cielo de Atitlán) como portada de bienvenida, no como fondo general.
- Figtree en todos los pesos; los títulos y cifras pesan 800, el texto corrido 400.
- Esquinas suaves y escalonadas: 12 px en controles, 16 px en tarjetas, 24 px en bandas.
- Plano en reposo; las tarjetas se elevan levemente solo al responder al cursor.
- Modo claro y modo oscuro completos en la plataforma y la administración.

## Colors

Una paleta fría y contenida: el azul orienta y llama a la acción; todo lo demás es neutro, salvo los estados.

### Primary
- **Azul Lago** (#2196F3): el color de identidad del logo y la marca. Se usa en íconos, detalles decorativos, indicadores de progreso, texto grande y como botón principal sobre fondos oscuros (contraste 6.46:1 sobre Cielo de Atitlán). No lleva texto blanco encima en tamaños normales.
- **Azul Lago Profundo** (#1976D2): la misma familia, un tono más oscuro, para todo lo que lleva texto: botones primarios con texto blanco (4.60:1), enlaces, navegación activa.
- **Azul Lago Intenso** (#1565C0): estado hover y presionado de los botones primarios; texto de insignias y de acentos sobre fondos claros (5.75:1 sobre blanco).
- **Bruma de Lago** (#E3F2FD): fondos teñidos de insignias, estados seleccionados y paneles de acento suave.
- **Celeste Claro** (#81D4FA): texto de marca sobre superficies oscuras (12.23:1 sobre Cielo de Atitlán) y acento en el modo oscuro.

### Secondary
- **Cielo de Atitlán** (#020617): el azul nocturno de la banda de bienvenida del estudiante y del fondo del modo oscuro. Da profundidad y peso de portada.

### Neutral
- **Tinta** (#0F172A): títulos, cifras grandes y texto de máximo énfasis (17.85:1 sobre blanco). También es la superficie de las tarjetas en modo oscuro.
- **Texto** (#1E293B): texto corrido.
- **Texto de control** (#334155): texto de botones secundarios y controles neutros.
- **Texto secundario** (#475569): descripciones, enlaces de navegación en reposo.
- **Texto apagado** (#64748B): metadatos, ayudas y etiquetas en mayúsculas (4.76:1 sobre blanco; es el gris más claro permitido para texto).
- **Borde** (#E2E8F0): bordes de tarjetas, divisores y campos.
- **Fondo** (#F8FAFC): fondo de la plataforma del estudiante y relleno de campos.
- **Fondo de administración** (#F1F5F9): fondo del espacio de administración, un paso más gris para separarlo del espacio de aprendizaje.
- **Superficie** (#FFFFFF): tarjetas, barras superiores y menús.
- **Borde nocturno** (#334155): bordes y divisores en modo oscuro.

### Estados
- **Éxito** (#047857 sobre #ECFDF5), **Error** (#BE123C sobre #FFF1F2) y **Aviso** (#B45309 sobre #FFFBEB): siempre como contenedor suave con fondo teñido, borde del mismo tono y texto oscuro.
- **Pendiente** (#F97316): solo como punto indicador pequeño (por ejemplo, la verificación en dos pasos sin activar), nunca como fondo de texto.

### Named Rules
**La regla del Azul Legible.** Si el azul lleva texto o el texto es azul en tamaño normal, se usa Azul Lago Profundo o Intenso; Azul Lago puro queda para íconos, detalles, texto grande y fondos oscuros.

**La regla del único acento.** Azul Lago es el único color de identidad. Verde, rojo y ámbar existen solo para comunicar estados, nunca para decorar.

**La regla de la noche como portada.** Cielo de Atitlán se usa como mucho una vez por pantalla, en la banda de bienvenida o portada; nunca como fondo de tarjetas en el modo claro.

**Estado actual (deriva por corregir).** Gran parte de la interfaz todavía usa el azul de Tailwind (#1D4ED8, #2563EB, #EFF6FF) en lugar de la familia Azul Lago, y el botón del diagnóstico usa un azul claro con texto oscuro. Al tocar una pantalla, migra sus azules a esta paleta; no introduzcas nuevos usos de #1D4ED8.

## Typography

**Display Font:** Figtree (con ui-sans-serif, system-ui, sans-serif)
**Body Font:** Figtree (con ui-sans-serif, system-ui, sans-serif)

**Character:** una sola familia geométrica y amable que cambia de voz con el peso: 800 compacto para títulos y cifras que orientan, 400 cómodo para leer. Se carga desde Bunny Fonts en los pesos 400 a 800.

### Hierarchy
- **Display** (800, 36px, 40px): el saludo de la banda de bienvenida.
- **Headline** (800, 30px, 36px, -0.025em): título principal de cada página y cifras de las tarjetas de estadísticas.
- **Title large** (800, 24px, 32px): títulos de sección dentro de una página.
- **Title** (800, 20px, 28px): títulos de tarjetas y paneles.
- **Body** (400, 16px, 24px): texto corrido; máximo de 65 a 75 caracteres por línea en lecciones y descripciones largas.
- **Body small** (400, 14px, 24px): descripciones de tarjetas, ayudas, contenido de campos.
- **Button** (700, 14px, 20px): botones y enlaces de navegación.
- **Caption** (700, 12px, 16px): insignias y metadatos breves.
- **Label** (800, 12px, 0.12em, MAYÚSCULAS): etiquetas de sección ("CONTINÚA TU RUTA", "GESTIÓN").

### Named Rules
**La regla del peso con propósito.** El peso 800 se reserva para títulos y cifras; el texto corrido nunca va en negrita completa y los botones van en 700.

**La regla de la etiqueta legible.** Las etiquetas en mayúsculas miden al menos 11 px y usan como mínimo Texto apagado (#64748B). Las etiquetas actuales en #94A3B8 (2.56:1) no cumplen el contraste y deben oscurecerse.

**Estado actual (deriva por corregir).** La página de inicio declara Inter pero no la carga, así que muestra la fuente del sistema. Debe usar Figtree como el resto.

## Layout

La plataforma del estudiante usa una barra superior fija con navegación horizontal; en pantallas grandes la persona puede cambiar a un menú lateral. La administración usa siempre un menú lateral fijo de unos 290 px con secciones etiquetadas (VISTA GENERAL, GESTIÓN, CONFIGURACIÓN) y una barra superior con el título de la página.

El contenido ocupa todo el ancho disponible en escritorio, con márgenes laterales de 16 px en móvil, 24 px en tablet, 32 px en escritorio y 48 px en pantallas muy anchas, y 32 a 40 px arriba y abajo. Dentro de ese ancho, los textos largos y formularios mantienen un ancho de lectura cómodo. Las tarjetas se organizan en rejillas que pasan de una columna en móvil a dos o tres en escritorio, con separaciones de 16 a 24 px.

El ritmo vertical sigue una escala de 4 px: 8 px entre elementos relacionados, 16 px entre bloques de una tarjeta, 24 px de relleno interno de tarjeta y 32 a 40 px entre secciones. Puntos de quiebre de Tailwind: 640, 1024 y 1536 px.

### Named Rules
**La regla del siguiente paso visible.** En cada pantalla del estudiante, la acción que lo hace avanzar en su ruta es visible sin desplazarse en escritorio y es la primera acción en móvil.

## Elevation & Depth

El sistema es plano en reposo y usa capas tonales: tarjetas blancas con borde de 1 px sobre un fondo casi blanco. La sombra no define la estructura; aparece apenas como un borde suave en reposo y crece solo cuando el elemento responde.

### Shadow Vocabulary
- **Reposo** (`box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05)`): tarjetas y paneles en reposo.
- **Elevación al pasar** (`box-shadow: 0 12px 28px rgb(15 23 42 / 0.08)` con `translateY(-2px)`): tarjetas interactivas bajo el cursor, en 0.25 s con `ease`.
- **Acento activo** (`box-shadow: 0 4px 6px -1px rgb(25 118 210 / 0.2)`): elemento de navegación activo.
- **Portada** (`box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1)`): banda de bienvenida.

### Named Rules
**La regla de plano en reposo.** Nada flota en reposo. La elevación es una respuesta (cursor, foco, apertura de un menú o modal), nunca un estado decorativo.

**La regla del movimiento respetuoso.** Las entradas usan `cubic-bezier(.22, 1, .36, 1)`: 0.45 s para la aparición escalonada del contenido y 0.22 s para modales. Con `prefers-reduced-motion: reduce` todas las animaciones y transiciones se anulan.

## Shapes

Esquinas suaves en escalera, que crecen con el tamaño del elemento: 8 px en enlaces pequeños de navegación, 12 px en botones, campos y elementos del menú, 16 px en tarjetas, paneles y áreas de texto grandes, 24 px en bandas de portada y forma de píldora en insignias, avatares de estado y barras de progreso. Los bordes son de 1 px en Borde; no hay recortes diagonales ni formas irregulares.

### Named Rules
**La regla de la escalera.** Un elemento nunca tiene esquinas más redondeadas que su contenedor: control (12 px) dentro de tarjeta (16 px) dentro de banda (24 px).

## Components

### Buttons
Suaves y seguros: invitan a avanzar sin gritar.
- **Shape:** esquinas suaves (12px).
- **Primary:** Azul Lago Profundo con texto blanco en peso 700, relleno de 10 × 16 px (12 × 20 px en llamadas principales).
- **Hover / Focus:** pasa a Azul Lago Intenso en 0.2 s; el foco muestra un anillo visible de 2 px en Azul Lago.
- **Secondary:** fondo blanco o transparente, borde de 1 px en Borde y texto de control; al pasar el cursor, fondo Fondo.
- **On dark:** sobre la banda nocturna, Azul Lago con texto Cielo de Atitlán en peso 800.

### Chips
- **Style:** píldora en Bruma de Lago con texto Azul Lago Intenso, peso 700, 12 px, relleno de 4 × 12 px.
- **State:** las insignias de estado usan la pareja suave del estado correspondiente (Éxito, Error, Aviso).

### Cards / Containers
- **Corner Style:** 16px.
- **Background:** Superficie (Tinta en modo oscuro).
- **Shadow Strategy:** Reposo; Elevación al pasar si la tarjeta es interactiva (ver Elevation & Depth).
- **Border:** 1 px en Borde (Borde nocturno en modo oscuro).
- **Internal Padding:** 24px (20px en tarjetas de estadística).

### Inputs / Fields
- **Style:** relleno Fondo, borde de 1 px en Borde, esquinas de 12px, texto Body small.
- **Focus:** el fondo pasa a Superficie, el borde a Azul Lago y aparece un anillo de 1 px del mismo azul.
- **Error / Disabled:** el error usa borde y mensaje en Error debajo del campo; deshabilitado baja la opacidad y conserva el borde.

### Navigation
- **Estudiante:** barra superior blanca al 95 % con desenfoque de fondo, logo y nombre a la izquierda, enlaces en Button y menú de perfil a la derecha. En móvil se pliega en un menú de hamburguesa. El menú lateral opcional y el de administración usan enlaces de 12px de radio con Texto secundario en reposo, Bruma de Lago al pasar y Azul Lago Profundo con texto blanco cuando están activos.
- **Administración:** menú lateral con etiquetas de sección en Label y accesos directos para cambiar al espacio del estudiante.

### Banda de bienvenida
La firma visual del espacio del estudiante: un bloque Cielo de Atitlán de 24px de radio y relleno de 32 × 40 px que saluda por su nombre a la persona, con una etiqueta en Celeste Claro, el saludo en Display blanco, una línea de contexto y una única llamada a la acción On dark. Aparece una sola vez, al inicio del panel.

### Recorrido guiado
Ventana emergente blanca de 16px de radio con sombra marcada que presenta la plataforma por pasos ("RECORRIDO · PASO 1 DE 8"), con acciones Omitir, Atrás y Siguiente. Señala el elemento que explica y atenúa el resto de la pantalla.

## Do's and Don'ts

### Do:
- **Do** usar Azul Lago Profundo (#1976D2) o Intenso (#1565C0) cada vez que el azul lleve texto o sea texto.
- **Do** mantener una sola llamada principal por bloque y dejar visible el siguiente paso de la ruta.
- **Do** usar contenedores suaves (fondo teñido, borde y texto oscuro del mismo tono) para éxito, error y aviso.
- **Do** escalonar las esquinas: 12 px en controles, 16 px en tarjetas, 24 px en bandas.
- **Do** cuidar el modo oscuro en cada pantalla nueva: Cielo de Atitlán de fondo, Tinta en tarjetas y Borde nocturno.
- **Do** respetar `prefers-reduced-motion` en cualquier animación nueva.

### Don't:
- **Don't** usar degradados morados, brillos de neón ni tarjetas idénticas repetidas: Aprende Guate no debe parecer una plantilla genérica de IA.
- **Don't** usar colores primarios saturados ni ilustraciones caricaturescas: no es un aula infantil.
- **Don't** construir pantallas grises, rígidas y sin calidez: no es un banco ni una oficina de gobierno.
- **Don't** llenar la interfaz de contadores, insignias y colores compitiendo por atención: no es una red social.
- **Don't** poner texto blanco sobre Azul Lago (#2196F3) en tamaños normales (3.12:1).
- **Don't** usar #94A3B8 o más claro para texto sobre fondos claros.
- **Don't** introducir nuevos usos del azul de Tailwind #1D4ED8 ni otro azul fuera de la familia Azul Lago.
- **Don't** usar Cielo de Atitlán más de una vez por pantalla en el modo claro.
