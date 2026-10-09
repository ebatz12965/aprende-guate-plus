<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Certificado {{ $certificate->code }} · Aprende Guate</title>
    <style>
        @page { size: A4 landscape; margin: 0; }
        html, body { margin: 0; padding: 0; }
    </style>
</head>
<body>
    @include('certificates.sheet')
</body>
</html>
