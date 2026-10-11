@php($customMessage = \App\Support\ErrorPage::customMessage($exception ?? null))
@include('errors.layout', [
    'code' => isset($exception) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 400,
    'title' => 'No se pudo completar la solicitud',
    'message' => $customMessage ?? 'Algo en la solicitud no es correcto. Vuelve atrás e inténtalo de nuevo.',
    'actions' => [['Volver', 'back', true], ['Ir al inicio', url('/'), false]],
])
