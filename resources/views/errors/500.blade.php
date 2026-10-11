@include('errors.layout', [
    'code' => 500,
    'title' => 'Algo salió mal de nuestro lado',
    'message' => 'Ocurrió un error inesperado en la plataforma. No es tu culpa y ya quedó registrado para revisarlo.',
    'hint' => 'Puedes intentarlo de nuevo en un momento.',
    'reference' => \App\Support\ErrorReference::get(),
    'actions' => [['Intentar de nuevo', 'reload', true], ['Ir al inicio', url('/'), false]],
])
