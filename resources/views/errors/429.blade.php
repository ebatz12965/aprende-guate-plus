@php($wait = isset($exception) && method_exists($exception, 'getHeaders') ? (int) ($exception->getHeaders()['Retry-After'] ?? 0) : 0)
@include('errors.layout', [
    'code' => 429,
    'title' => 'Vas muy rápido',
    'message' => 'Hiciste muchas solicitudes en poco tiempo. Es una protección de la plataforma, no un problema con tu cuenta.',
    'hint' => $wait > 0 ? 'Podrás intentarlo de nuevo en '.($wait >= 120 ? ceil($wait / 60).' minutos' : $wait.' segundos').'.' : 'Espera un momento antes de volver a intentarlo.',
    'actions' => [['Volver', 'back', true]],
])
