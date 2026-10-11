@props(['url'])
{{-- Encabezado de todos los correos: logo y nombre de Aprende Guate (no depende de APP_NAME). --}}
@php($logo = rtrim((string) config('app.url'), '/').'/images/aprende-guate-email-logo.png')
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<img src="{{ $logo }}" class="logo" width="44" height="44" alt="" style="vertical-align: middle;">
<span class="brand-name">Aprende Guate</span>
</a>
</td>
</tr>
