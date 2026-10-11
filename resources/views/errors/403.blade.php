@php($customMessage = \App\Support\ErrorPage::customMessage($exception ?? null))
@include('errors.layout', [
    'code' => 403,
    'title' => 'No tienes acceso a esta página',
    'message' => $customMessage ?? 'Tu cuenta no tiene permiso para ver esto. Si crees que es un error, pide ayuda a quien administra la plataforma.',
    'actions' => [['Volver', 'back', true], ['Ir al inicio', url('/'), false]],
])
