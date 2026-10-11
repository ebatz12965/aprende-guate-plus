@include('errors.layout', [
    'code' => 419,
    'title' => 'La página expiró',
    'message' => 'Pasó mucho tiempo desde que abriste esta página y, por seguridad, ya no se puede enviar. Lo que escribiste no se guardó.',
    'hint' => 'Recarga la página e inténtalo de nuevo. Si tu sesión se cerró, vuelve a iniciar sesión.',
    'actions' => [['Recargar la página', 'reload', true], ['Ir al inicio', url('/'), false]],
])
