@include('errors.layout', [
    'code' => isset($exception) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500,
    'title' => 'Algo salió mal de nuestro lado',
    'message' => 'La plataforma no pudo responder en este momento. Ya quedó registrado para revisarlo.',
    'reference' => \App\Support\ErrorReference::get(),
    'actions' => [['Intentar de nuevo', 'reload', true], ['Ir al inicio', url('/'), false]],
])
