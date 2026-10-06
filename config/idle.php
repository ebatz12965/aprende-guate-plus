<?php

/*
| Cierre de sesión por inactividad. Solo cuenta lo que la persona hace (navegar, escribir, hacer clic):
| las consultas automáticas de fondo (chat de mentoría, tablero de IA, estados de generación) no cuentan.
| Quien tiene permisos de administración tiene un límite más corto porque su sesión vale más.
*/
return [
    'minutes' => (int) env('SESSION_IDLE_MINUTES', 30),
    'admin_minutes' => (int) env('SESSION_IDLE_ADMIN_MINUTES', 15),
    // Aviso "¿Sigues ahí?" antes del cierre.
    'warning_seconds' => 120,
];
