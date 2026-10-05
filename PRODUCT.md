# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Usuarios principales (confirmados):

- **Jóvenes que buscan su primer empleo** en Guatemala: estudiantes y recién graduados que necesitan habilidades para el trabajo y un perfil profesional para entrar al mercado laboral.
- **Adultos que trabajan y quieren mejorar o cambiar de área**: personas que buscan subir de puesto o cambiar de rumbo (por ejemplo ofimática, programación, soporte técnico).

Usan la plataforma en celular y computadora más o menos por igual: el celular para revisar su avance, hablar con el Tutor IA y leer mensajes de mentoría; la computadora para lecciones y prácticas que implican archivos de Excel, código y entrega de documentos.

Públicos secundarios que el producto ya atiende con espacios propios:

- **Mentores** (rol `Instructor`): profesionales que se postulan con un cuestionario, son aprobados por un administrador, configuran su disponibilidad y aceptan, programan y conversan en sesiones de mentoría.
- **Administradores**: gestionan usuarios, límites y presupuesto de IA, agentes de IA, correo, avisos y solicitudes de mentor.

## Product Purpose

Aprende Guate ayuda a personas en Guatemala a desarrollar habilidades para conseguir empleo. Un diagnóstico capta la meta y el nivel de cada persona; la plataforma genera una ruta de aprendizaje personal por fases y temas, produce lecciones y prácticas bajo demanda, evalúa evidencias con IA, desbloquea retos prácticos a medida que avanza la ruta y conecta con mentores humanos. El éxito es que la persona complete su ruta, tenga evidencia evaluada de lo que sabe hacer y termine con un perfil profesional listo para mostrar a empleadores.

## Positioning

Lo que un curso en línea común no puede afirmar con verdad:

- **Una ruta hecha para cada persona**: las fases y temas salen del diagnóstico, la meta y el nivel de cada quien, no de un temario fijo.
- **Enfocada en empleo en Guatemala**: prácticas y retos con contextos laborales guatemaltecos realistas, y un cierre en perfil profesional y CV.
- **IA y personas**: un Tutor IA y evaluación con IA, junto con mentores humanos reales.
- **Aprender haciendo**: prácticas y retos por hitos con evidencia entregada y evaluada, no solo lectura o videos.

## Operating Context

- Producto en español para Guatemala (tuteo en los textos dirigidos a estudiantes).
- Recorrido del estudiante: diagnóstico (una pregunta abierta sobre su meta y cuatro preguntas de opción múltiple generadas) → ruta personal generada en segundo plano → fases que se desbloquean con un mini-quiz → temas con lección y práctica bajo demanda → entrega de la práctica con retroalimentación formativa de IA → retos opcionales al 25 / 50 / 75 / 100 % de la ruta, evaluados con rúbrica sobre la respuesta escrita y archivos adjuntos (PDF, DOCX, XLSX, PPTX, TXT, CSV) → perfil profesional (resumen, educación, experiencia, CV, foto) con enlace público opcional.
- Tutor IA disponible en todo momento; solicitudes de mentoría, agenda en hora de Guatemala, recordatorios y chat dentro de la plataforma.
- La generación con IA es asíncrona: varias pantallas muestran estados de espera mientras se prepara el contenido, y cada paso de IA tiene contenido de respaldo cuando el proveedor no está disponible.
- Notificaciones en la aplicación y por correo, que cada persona controla por categoría.

## Capabilities and Constraints

- Construido con Laravel 13 (PHP 8.3+), Blade, Tailwind CSS 3 y Alpine.js; páginas renderizadas en el servidor, no es una SPA.
- Tres espacios según el rol: aprendizaje (todas las personas), mentor (`Instructor`) y administración (`Admin`).
- Cuentas: verificación de correo, verificación en dos pasos opcional e inicio de sesión con Google desde la pantalla de acceso.
- El perfil público es privado por defecto y se comparte con un enlace no adivinable.
- Vocabulario del producto: Diagnóstico, Ruta, Fase, Tema, Lección, Práctica, Reto, Tutor IA, Mentoría / Mentor, Perfil profesional, Avisos.
- Solo en español; no se planean otros idiomas.

## Brand Commitments

- Nombre oficial: **Aprende Guate** (el repositorio se llama `aprende-guate-plus`; ese no es el nombre del producto).
- Recursos existentes: logo `public/images/aprende-guate-logo.png` e ícono `public/images/aprende-guate-app-icon.png`.
- Propietario: Edwing Batz.
- Voz de los textos actuales: directa y motivadora, en segunda persona ("Construye habilidades para tu futuro con IA", "Aprendizaje que se adapta a ti").

## Evidence on Hand

- Etapa: **proyecto académico** (desarrollado por sprints con reportes de avance), evaluado mediante demostraciones y no con una base pública de usuarios.
- **No hay testimonios reales, cifras de usuarios, resultados, empresas aliadas, prensa ni métricas.** El trabajo futuro no debe inventarlos.
- Contenido real existente: textos de la página de inicio (`resources/views/welcome.blade.php`), preguntas frecuentes y páginas legales (`resources/views/legal/`), y el contenido de aprendizaje generado con IA para cada usuario.

## Product Principles

1. **Cada paso acerca a un empleo.** Las funciones se juzgan por cuánto ayudan a que alguien sea empleable, no por cuánto contenido entregan.
2. **Personal, nunca genérico.** Lo que ve cada estudiante se deriva de su propio diagnóstico, meta y avance.
3. **Hacer antes que mirar.** La práctica y la evidencia evaluada son el centro del aprendizaje; la lectura las apoya.
4. **La IA acompaña, las personas importan.** Ser honestos sobre lo que genera la IA, ofrecer siempre una alternativa cuando no esté disponible y mantener visibles a los mentores humanos.
5. **Funciona en el celular y en la computadora.** Las consultas rápidas deben ser fáciles en el celular; el trabajo profundo, cómodo en la computadora.
