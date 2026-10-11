@include('errors.layout', [
    'code' => 413,
    'title' => 'El archivo es demasiado grande',
    'message' => 'Puedes subir archivos de hasta 10 MB. Reduce el tamaño (por ejemplo, guardándolo como PDF) y vuelve a intentarlo.',
    'actions' => [['Volver', 'back', true]],
])
