<x-mail::layout>
{{-- Encabezado --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
Aprende Guate
</x-mail::header>
</x-slot:header>

{{-- Cuerpo --}}
{!! $slot !!}

{{-- Texto secundario --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Pie --}}
<x-slot:footer>
<x-mail::footer>
**Aprende Guate** · Plataforma educativa de Guatemala<br>
[{{ preg_replace('#^https?://#', '', rtrim((string) config('app.url'), '/')) }}]({{ config('app.url') }})<br>
Recibes este correo porque tienes una cuenta en Aprende Guate.<br>
© {{ date('Y') }} Aprende Guate. Todos los derechos reservados.
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
