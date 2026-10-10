@props(['module'])
@if (app(\App\Support\Modules::class)->underMaintenance($module))
    <span class="ml-2 inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-800 ring-1 ring-inset ring-amber-200">En mantenimiento</span>
@endif
