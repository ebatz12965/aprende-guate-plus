{{-- Correo armado con una plantilla editable (App\Support\EmailTemplates). Sin sangría: el Markdown la tomaría como código. --}}
<x-mail::message>
@if (filled($heading))
# {{ $heading }}
@endif

@foreach ($paragraphs as $paragraph)
{!! $paragraph !!}

@endforeach
@if ($transcriptHtml)
{!! $transcriptHtml !!}

@endif
@if ($actionUrl)
<x-mail::button :url="$actionUrl">
{{ $actionLabel }}
</x-mail::button>
@endif

@foreach ($closing as $paragraph)
{!! $paragraph !!}

@endforeach
@if ($actionUrl)
<x-slot:subcopy>
Si el botón «{{ $actionLabel }}» no funciona, copia y pega este enlace en tu navegador: <span class="break-all">[{{ $actionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endif
</x-mail::message>
