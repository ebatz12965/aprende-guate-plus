@php($customMessage = \App\Support\ErrorPage::customMessage($exception ?? null))
@include('errors.layout', [
    'code' => 404,
    'title' => 'No encontramos esta página',
    'message' => $customMessage ?? 'Puede que el enlace esté mal escrito o que la página ya no exista.',
    'hint' => 'Revisa la dirección o vuelve al inicio para seguir con tu ruta.',
    'actions' => [['Ir al inicio', url('/'), true], ['Volver', 'back', false]],
])
