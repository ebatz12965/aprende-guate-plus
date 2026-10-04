<?php

return [
    'architecture' => [
        'level' => 'Inicial a avanzado',
        'title' => 'Ruta profesional de Microsoft Office',
        'summary' => 'Programa progresivo para trabajar con documentos, hojas de cálculo, presentaciones y automatización en contextos laborales.',
        'total_hours' => 72,
        'modules' => [
            ['title' => 'Nivel básico: entorno y productividad', 'description' => 'Domina la interfaz, archivos, formatos y hábitos de trabajo seguros.', 'skill_type' => 'ofimatica', 'topics' => [
                ['title' => 'Organización de archivos y carpetas', 'learning_objective' => 'Crear una estructura ordenada, localizar archivos y aplicar nombres profesionales.', 'practice_type' => 'case_study'],
                ['title' => 'Word: documentos profesionales', 'learning_objective' => 'Crear un documento con estilos, encabezados, tablas y exportación a PDF.', 'practice_type' => 'case_study'],
                ['title' => 'Excel: celdas, formatos y fórmulas básicas', 'learning_objective' => 'Registrar datos y calcular resultados con referencias y funciones básicas.', 'practice_type' => 'excel_dataset'],
            ]],
            ['title' => 'Nivel básico: comunicación visual', 'description' => 'Prepara documentos y presentaciones claros para el trabajo.', 'skill_type' => 'ofimatica', 'topics' => [
                ['title' => 'PowerPoint: estructura de una presentación', 'learning_objective' => 'Construir una presentación breve con jerarquía visual y notas del expositor.', 'practice_type' => 'case_study'],
                ['title' => 'Word: informes con tablas e imágenes', 'learning_objective' => 'Integrar información y evidencias en un informe legible.', 'practice_type' => 'case_study'],
                ['title' => 'Excel: ordenar, filtrar y validar datos', 'learning_objective' => 'Limpiar una tabla y controlar la calidad de los datos antes de analizarlos.', 'practice_type' => 'excel_dataset'],
            ]],
            ['title' => 'Nivel intermedio: análisis de datos', 'description' => 'Convierte datos operativos en información útil para decidir.', 'skill_type' => 'ofimatica', 'topics' => [
                ['title' => 'Excel: funciones condicionales y de búsqueda', 'learning_objective' => 'Resolver consultas con SI, SUMAR.SI.CONJUNTO, BUSCARX y funciones de texto.', 'practice_type' => 'excel_dataset'],
                ['title' => 'Excel: tablas dinámicas', 'learning_objective' => 'Resumir ventas, gastos o indicadores por categorías y periodos.', 'practice_type' => 'excel_dataset'],
                ['title' => 'Excel: gráficos e indicadores', 'learning_objective' => 'Elegir gráficos adecuados y construir un tablero básico para una reunión.', 'practice_type' => 'excel_dataset'],
            ]],
            ['title' => 'Nivel intermedio: reportes integrados', 'description' => 'Combina Word, Excel y PowerPoint para comunicar resultados.', 'skill_type' => 'ofimatica', 'topics' => [
                ['title' => 'Excel: limpieza y transformación de datos', 'learning_objective' => 'Preparar datos repetitivos para análisis mediante Power Query.', 'practice_type' => 'excel_dataset'],
                ['title' => 'Word: combinación de correspondencia', 'learning_objective' => 'Generar comunicaciones personalizadas desde una lista de destinatarios.', 'practice_type' => 'case_study'],
                ['title' => 'PowerPoint: contar una historia con datos', 'learning_objective' => 'Presentar hallazgos con una secuencia clara, visual y orientada a decisiones.', 'practice_type' => 'case_study'],
            ]],
            ['title' => 'Nivel avanzado: automatización', 'description' => 'Reduce tareas repetitivas y crea soluciones mantenibles.', 'skill_type' => 'ofimatica', 'topics' => [
                ['title' => 'Excel: modelos y escenarios', 'learning_objective' => 'Construir un modelo con supuestos, escenarios y controles de consistencia.', 'practice_type' => 'excel_dataset'],
                ['title' => 'Excel: macros y VBA con seguridad', 'learning_objective' => 'Automatizar una tarea controlada y documentar sus riesgos y permisos.', 'practice_type' => 'case_study'],
                ['title' => 'Office: proyecto integrador', 'learning_objective' => 'Entregar un paquete profesional con datos, informe y presentación ejecutiva.', 'practice_type' => 'case_study'],
            ]],
        ],
    ],
];
