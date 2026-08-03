<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado de Finalización</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-200 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-4xl bg-white rounded-lg shadow-2xl mx-4 my-8 p-8 md:p-12 border-8 border-blue-800">
        <div class="text-center">
            <p class="text-sm font-semibold uppercase text-gray-500 tracking-widest">Certificado de Finalización</p>
            <h1 class="text-4xl md:text-5xl font-bold text-blue-800 mt-4">GuateAprende</h1>
        </div>

        <div class="text-center mt-12">
            <p class="text-lg text-gray-600">Se otorga este certificado a</p>
            <p class="text-3xl md:text-4xl font-bold text-gray-800 mt-2">{{ $certificate->user->name }}</p>
        </div>

        <div class="text-center mt-10">
            <p class="text-lg text-gray-600">Por haber completado satisfactoriamente el curso</p>
            <p class="text-2xl md:text-3xl font-semibold text-gray-700 mt-2">"{{ $certificate->course->title }}"</p>
        </div>

        <div class="flex justify-between items-end mt-16">
            <div class="text-left">
                <p class="text-sm text-gray-500">Fecha de Emisión</p>
                <p class="font-semibold text-gray-700">{{ $certificate->issued_at->format('d/m/Y') }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">Código de Verificación</p>
                <p class="font-mono text-xs text-gray-600">{{ $certificate->code }}</p>
            </div>
        </div>
    </div>
</body>
</html>
