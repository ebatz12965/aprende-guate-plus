@php($customMessage = \App\Support\ErrorPage::customMessage($exception ?? null))
@include('errors.layout', [
    'code' => 503,
    'title' => 'Estamos en mantenimiento',
    'message' => $customMessage ?? 'Estamos mejorando Aprende Guate. Volveremos en unos minutos; tu avance está guardado.',
    'actions' => [['Intentar de nuevo', 'reload', true]],
])
