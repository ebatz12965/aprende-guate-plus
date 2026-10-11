@include('errors.layout', [
    'code' => 401,
    'title' => 'Necesitas iniciar sesión',
    'message' => 'Para ver esta página primero tienes que entrar con tu cuenta.',
    'actions' => [['Iniciar sesión', url('/login'), true], ['Ir al inicio', url('/'), false]],
])
