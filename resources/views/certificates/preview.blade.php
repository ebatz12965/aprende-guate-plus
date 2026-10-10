{{-- Vista previa del certificado en la página: la misma hoja del PDF, escalada al ancho disponible (proporción A4 horizontal). --}}
<div x-data="{ scale: 0, fit() { const width = this.$refs.sheet.offsetWidth; if (width) this.scale = this.$el.clientWidth / width; } }"
    x-init="$nextTick(() => fit()); new ResizeObserver(() => fit()).observe($el)"
    class="relative aspect-[297/210] w-full overflow-hidden rounded-xl bg-white shadow-[0_8px_30px_-12px_rgba(2,6,23,0.35)] ring-1 ring-slate-200"
    role="img" aria-label="Certificado de {{ $certificate->recipient_name }}: {{ $certificate->title }}">
    <div x-ref="sheet" class="absolute left-0 top-0 w-max origin-top-left" :style="'transform: scale(' + scale + ')'" aria-hidden="true">
        @include('certificates.sheet')
    </div>
</div>
