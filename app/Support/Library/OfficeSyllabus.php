<?php

namespace App\Support\Library;

/**
 * Temario curado de Microsoft Office, de principiante a experto. La estructura la decide el equipo (no la IA)
 * para que el orden y los prerrequisitos sean correctos; la IA solo redacta el contenido de cada tema, una vez.
 */
class OfficeSyllabus
{
    public const COURSE = 'office';

    /** @var array<string, array{label: string, order: int}> */
    public const LEVELS = [
        'principiante' => ['label' => 'Principiante', 'order' => 1],
        'intermedio' => ['label' => 'Intermedio', 'order' => 2],
        'avanzado' => ['label' => 'Avanzado', 'order' => 3],
        'experto' => ['label' => 'Experto', 'order' => 4],
    ];

    /** @var array<string, string> */
    public const TOOLS = ['word' => 'Word', 'excel' => 'Excel', 'powerpoint' => 'PowerPoint', 'outlook' => 'Outlook'];

    /**
     * Temas por herramienta y nivel: [título, objetivo, tipo de práctica].
     *
     * @return array<string, array<string, list<array{0: string, 1: string, 2: string}>>>
     */
    public static function topics(): array
    {
        return [
            'word' => [
                'principiante' => [
                    ['Crear, guardar y compartir documentos', 'Crear un documento desde cero o desde una plantilla, guardarlo en la carpeta correcta o en OneDrive y compartirlo con permisos de solo lectura o edición.', 'case_study'],
                    ['Formato de texto y de párrafo', 'Aplicar fuente, tamaño, negrita, alineación, interlineado y sangrías desde la pestaña Inicio para que un documento se lea con claridad.', 'case_study'],
                    ['Listas, viñetas y tabulaciones', 'Organizar información con listas con viñetas, listas numeradas de varios niveles y tabulaciones alineadas.', 'case_study'],
                    ['Revisión ortográfica y exportar a PDF', 'Revisar ortografía y gramática con la pestaña Revisar y exportar el documento a PDF listo para enviar.', 'theory_quiz'],
                ],
                'intermedio' => [
                    ['Estilos y tabla de contenido automática', 'Dar estructura a un documento con los estilos Título 1 y Título 2 y generar una tabla de contenido que se actualiza sola.', 'case_study'],
                    ['Tablas en Word', 'Insertar y dar formato a tablas, combinar celdas, repetir la fila de encabezado y ordenar su contenido.', 'case_study'],
                    ['Encabezados, pies de página y saltos de sección', 'Numerar páginas, crear encabezados distintos por sección y cambiar la orientación de una sola página con saltos de sección.', 'case_study'],
                    ['Imágenes, formas y ajuste del texto', 'Insertar imágenes y formas, ajustar el texto alrededor y alinear los elementos sin que se muevan al editar.', 'case_study'],
                ],
                'avanzado' => [
                    ['Combinar correspondencia', 'Generar cartas, sobres o etiquetas personalizadas a partir de una lista de Excel con Correspondencia > Iniciar combinación.', 'case_study'],
                    ['Control de cambios y comentarios', 'Revisar documentos en equipo con Control de cambios y comentarios, y aceptar o rechazar cada cambio.', 'case_study'],
                    ['Referencias: notas al pie, citas y bibliografía', 'Insertar notas al pie, citas con un estilo (por ejemplo APA) y una bibliografía automática.', 'case_study'],
                    ['Plantillas y elementos rápidos', 'Crear una plantilla (.dotx) con el formato institucional y guardar bloques reutilizables como elementos rápidos.', 'case_study'],
                ],
                'experto' => [
                    ['Formularios con controles de contenido', 'Diseñar formularios rellenables con controles de contenido desde la pestaña Programador y restringir la edición.', 'case_study'],
                    ['Campos y referencias cruzadas', 'Numerar figuras y tablas con títulos e insertar referencias cruzadas que se actualizan al cambiar el documento.', 'case_study'],
                    ['Macros para tareas repetitivas en Word', 'Grabar una macro, revisar su código VBA y asignarla a un botón para automatizar un formato repetitivo.', 'code_complete'],
                    ['Proteger y finalizar documentos', 'Inspeccionar el documento, quitar información oculta, restringir la edición y marcarlo como final antes de publicarlo.', 'theory_quiz'],
                ],
            ],
            'excel' => [
                'principiante' => [
                    ['Libros, hojas, celdas y la cinta de opciones', 'Moverse con soltura en un libro de Excel: hojas, filas, columnas, celdas, rangos, la barra de fórmulas y la cinta de opciones.', 'theory_quiz'],
                    ['Captura de datos y formatos de número', 'Capturar datos sin errores y aplicar formatos de moneda en quetzales, fecha, porcentaje y decimales.', 'excel_dataset'],
                    ['Fórmulas básicas y referencias relativas', 'Escribir fórmulas con operadores aritméticos, entender el orden de las operaciones y copiarlas con el controlador de relleno.', 'excel_dataset'],
                    ['SUMA, PROMEDIO, MAX, MIN y CONTAR', 'Resumir una lista de datos con las funciones SUMA, PROMEDIO, MAX, MIN y CONTAR y Autosuma.', 'excel_dataset'],
                ],
                'intermedio' => [
                    ['Referencias absolutas y mixtas', 'Fijar celdas con $ (referencias absolutas y mixtas) para copiar fórmulas que usan un valor fijo, como una tasa de IVA.', 'excel_dataset'],
                    ['Funciones lógicas SI, Y, O', 'Tomar decisiones automáticas en una hoja con SI, Y, O y SI anidados.', 'excel_dataset'],
                    ['SUMAR.SI, CONTAR.SI y sus versiones .CONJUNTO', 'Sumar, contar y promediar con una o varias condiciones con SUMAR.SI, CONTAR.SI, SUMAR.SI.CONJUNTO y CONTAR.SI.CONJUNTO.', 'excel_dataset'],
                    ['Ordenar, filtrar y dar formato de tabla', 'Convertir un rango en tabla, ordenar por varios criterios y filtrar para encontrar información rápido.', 'excel_dataset'],
                    ['Gráficos para comunicar resultados', 'Elegir el gráfico adecuado (columnas, líneas, circular) y darle títulos y etiquetas que comuniquen una conclusión.', 'excel_dataset'],
                ],
                'avanzado' => [
                    ['BUSCARX y BUSCARV para cruzar tablas', 'Traer datos de otra tabla con BUSCARX (y reconocer BUSCARV en libros antiguos), con coincidencia exacta y manejo de no encontrados.', 'excel_dataset'],
                    ['Tablas dinámicas y segmentaciones', 'Resumir miles de filas con tablas dinámicas, agrupar por fechas y filtrar con segmentaciones.', 'excel_dataset'],
                    ['Validación de datos y formato condicional', 'Evitar errores de captura con listas desplegables y resaltar automáticamente valores importantes con formato condicional.', 'excel_dataset'],
                    ['Funciones de texto y de fecha', 'Limpiar y combinar textos (IZQUIERDA, EXTRAE, ESPACIOS, CONCAT) y calcular plazos con FECHA, HOY y DIAS.LAB.', 'excel_dataset'],
                ],
                'experto' => [
                    ['Power Query: importar y limpiar datos', 'Importar datos desde archivos y transformarlos con Power Query (quitar columnas, cambiar tipos, combinar consultas) de forma repetible.', 'excel_dataset'],
                    ['Matrices dinámicas: FILTRAR, ORDENAR y UNICOS', 'Crear reportes que se actualizan solos con funciones de matriz dinámica como FILTRAR, ORDENAR y UNICOS.', 'excel_dataset'],
                    ['LET y LAMBDA para fórmulas reutilizables', 'Simplificar fórmulas largas con LET y crear funciones propias con LAMBDA en el Administrador de nombres.', 'excel_dataset'],
                    ['Automatizar tareas con macros y VBA', 'Grabar una macro, leer y modificar su código VBA y ejecutarla con un botón para automatizar un reporte.', 'code_complete'],
                ],
            ],
            'powerpoint' => [
                'principiante' => [
                    ['Crear una presentación con diseños y temas', 'Crear una presentación, elegir un tema y usar los diseños de diapositiva correctos para cada contenido.', 'case_study'],
                    ['Texto, imágenes e iconos', 'Agregar texto breve, imágenes e iconos con buena alineación y contraste para que cada diapositiva se entienda de un vistazo.', 'case_study'],
                    ['Presentar con la vista Moderador', 'Ensayar y presentar con la vista Moderador, las notas del orador y los atajos para avanzar y retroceder.', 'theory_quiz'],
                ],
                'intermedio' => [
                    ['Patrón de diapositivas', 'Definir logotipo, fuentes y colores una sola vez en el Patrón de diapositivas para mantener la coherencia.', 'case_study'],
                    ['Gráficos, tablas y SmartArt', 'Presentar datos y procesos con gráficos, tablas y SmartArt fáciles de leer.', 'case_study'],
                    ['Transiciones y animaciones con propósito', 'Usar transiciones y animaciones solo para guiar la atención, con el Panel de animación y el orden correcto.', 'case_study'],
                ],
                'avanzado' => [
                    ['Transformación y Zoom', 'Crear movimiento fluido entre diapositivas con la transición Transformación y navegar por secciones con Zoom.', 'case_study'],
                    ['Audio, video y grabar la presentación', 'Insertar audio y video, recortarlos y grabar la presentación con narración para enviarla como video.', 'case_study'],
                    ['Contar una historia con datos', 'Estructurar una presentación con mensaje principal, evidencia y llamada a la acción, con una idea por diapositiva.', 'case_study'],
                ],
                'experto' => [
                    ['Plantillas corporativas y accesibilidad', 'Construir una plantilla corporativa (.potx) y verificar la accesibilidad con el Comprobador de accesibilidad y texto alternativo.', 'case_study'],
                    ['Presentaciones interactivas', 'Crear menús y recorridos no lineales con hipervínculos, botones de acción y desencadenadores de animación.', 'case_study'],
                    ['Coautoría y Diseñador', 'Trabajar en equipo sobre la misma presentación en la nube con comentarios, historial de versiones y Diseñador.', 'theory_quiz'],
                ],
            ],
            'outlook' => [
                'principiante' => [
                    ['Redactar y responder correos profesionales', 'Redactar, responder y reenviar correos claros, con asunto preciso, CC y CCO usados correctamente y adjuntos.', 'language_activity'],
                    ['Calendario: citas y reuniones', 'Programar citas y reuniones en el calendario, invitar asistentes y responder invitaciones.', 'case_study'],
                    ['Contactos y grupos', 'Guardar contactos y crear grupos para escribir a un equipo sin olvidar a nadie.', 'theory_quiz'],
                ],
                'intermedio' => [
                    ['Reglas y carpetas para organizar el correo', 'Organizar la bandeja con carpetas, categorías y reglas que mueven o marcan correos automáticamente.', 'case_study'],
                    ['Firmas, plantillas y respuestas rápidas', 'Configurar una firma profesional y reutilizar textos frecuentes con plantillas.', 'language_activity'],
                    ['Tareas y Microsoft To Do', 'Convertir correos en tareas con fecha y seguimiento usando marcas y Microsoft To Do.', 'case_study'],
                ],
                'avanzado' => [
                    ['Compartir calendarios y delegar', 'Compartir el calendario con distintos permisos y dar acceso de delegado para que otra persona gestione reuniones.', 'case_study'],
                    ['Pasos rápidos y búsqueda avanzada', 'Automatizar acciones de varios pasos con Pasos rápidos y encontrar cualquier correo con filtros de búsqueda.', 'case_study'],
                    ['Reuniones de Teams desde Outlook', 'Programar reuniones en línea de Microsoft Teams desde Outlook, con agenda, opciones de reunión y grabación.', 'case_study'],
                ],
            ],
        ];
    }

    /**
     * Mini-quiz de cada fase (herramienta y nivel): situación concreta, 3 opciones y el índice de la correcta.
     *
     * @return array{question: string, options: list<string>, answer: int}
     */
    public static function quiz(string $tool, string $level): array
    {
        $quizzes = [
            'word.principiante' => ['Te piden enviar un oficio que nadie debe poder modificar y que se vea igual en cualquier computadora. ¿Qué haces?', ['Lo envío como .docx', 'Lo exporto a PDF desde Archivo > Exportar', 'Copio el texto en el cuerpo del correo'], 1],
            'word.intermedio' => ['Tu informe de 30 páginas necesita un índice que se actualice al agregar capítulos. ¿Qué haces primero?', ['Aplico los estilos Título 1 y Título 2 a los títulos', 'Escribo el índice a mano con los números de página', 'Pongo los títulos en negrita y tamaño 16'], 0],
            'word.avanzado' => ['Debes enviar la misma carta a 200 clientes de una lista de Excel, cada una con su nombre. ¿Qué usas?', ['Copiar y pegar la carta 200 veces', 'Combinar correspondencia', 'Control de cambios'], 1],
            'word.experto' => ['Antes de publicar un documento quieres quitar comentarios y datos personales ocultos. ¿Qué herramienta usas?', ['Inspeccionar documento', 'Revisión ortográfica', 'Guardar como plantilla'], 0],
            'excel.principiante' => ['En A1:A5 hay ventas diarias. ¿Qué fórmula da el total?', ['=A1+A5', '=SUMA(A1:A5)', '=CONTAR(A1:A5)'], 1],
            'excel.intermedio' => ['La tasa de IVA está en F1 y copiarás =B2*F1 hacia abajo. ¿Cómo debe quedar la fórmula?', ['=B2*F1', '=B2*$F$1', '=$B$2*F1'], 1],
            'excel.avanzado' => ['Tienes 5,000 ventas y necesitas el total por vendedor y mes en minutos. ¿Qué usas?', ['Una tabla dinámica', 'Un SUMA por cada vendedor escrito a mano', 'Un gráfico circular'], 0],
            'excel.experto' => ['Cada semana recibes el mismo archivo con columnas sucias que limpias a mano. ¿Qué te ahorra más tiempo?', ['Repetir la limpieza con cuidado', 'Una consulta de Power Query que se actualiza', 'Pedir que te envíen menos datos'], 1],
            'powerpoint.principiante' => ['Una diapositiva tiene 12 líneas de texto pequeño. ¿Qué la mejora más?', ['Reducir el tamaño de letra para que quepa', 'Dejar las ideas clave y repartir el resto en más diapositivas', 'Agregar una animación a cada línea'], 1],
            'powerpoint.intermedio' => ['Necesitas el logotipo de la empresa en las 40 diapositivas. ¿Dónde lo colocas?', ['En el Patrón de diapositivas', 'Copiándolo en cada diapositiva', 'En las notas del orador'], 0],
            'powerpoint.avanzado' => ['Quieres que un gráfico crezca suavemente de una diapositiva a la siguiente. ¿Qué usas?', ['La transición Transformación', 'Un hipervínculo', 'El Patrón de diapositivas'], 0],
            'powerpoint.experto' => ['Una persona con lector de pantalla recibirá tu presentación. ¿Qué revisas?', ['Que todas las diapositivas tengan animaciones', 'El Comprobador de accesibilidad y el texto alternativo de las imágenes', 'Que el fondo sea oscuro'], 1],
            'outlook.principiante' => ['Escribes a un cliente y quieres que tu jefa reciba copia sin que el cliente lo sepa. ¿Dónde la pones?', ['En Para', 'En CC', 'En CCO'], 2],
            'outlook.intermedio' => ['Todos los correos del proveedor "La Ceiba" deben ir a una carpeta. ¿Qué configuras?', ['Una regla', 'Una firma', 'Una categoría de color'], 0],
            'outlook.avanzado' => ['Tu asistente debe aceptar reuniones en tu nombre. ¿Qué le das?', ['Tu contraseña', 'Acceso de delegado a tu calendario', 'Una copia de tus correos'], 1],
        ];
        [$question, $options, $answer] = $quizzes["{$tool}.{$level}"] ?? ['¿Qué conviene hacer antes de pasar a la siguiente fase?', ['Practicar lo aprendido con un caso real', 'Saltar las prácticas', 'Memorizar los menús'], 0];

        return ['question' => $question, 'options' => $options, 'answer' => $answer];
    }

    /** Herramientas y niveles válidos entre dos niveles (inclusive). */
    public static function levelsBetween(string $from, string $to): array
    {
        $min = self::LEVELS[$from]['order'] ?? 1;
        $max = self::LEVELS[$to]['order'] ?? 4;

        return array_keys(array_filter(self::LEVELS, fn (array $level) => $level['order'] >= $min && $level['order'] <= $max));
    }
}
