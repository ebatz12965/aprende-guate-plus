@include('errors.layout', [
    'code' => 405,
    'title' => 'Esta acción no se puede hacer así',
    'message' => 'La página se abrió de una forma que no está permitida. Vuelve atrás e inténtalo desde el botón o el formulario.',
    'actions' => [['Volver', 'back', true], ['Ir al inicio', url('/'), false]],
])
