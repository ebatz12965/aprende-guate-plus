<?php

return [
    /*
    | Verificación en dos pasos obligatoria para quien tenga permisos de administración.
    | Por defecto se exige en producción; en local se puede activar con ADMIN_REQUIRE_TWO_FACTOR=true.
    */
    'require_two_factor' => (bool) env('ADMIN_REQUIRE_TWO_FACTOR', env('APP_ENV') === 'production'),
];
