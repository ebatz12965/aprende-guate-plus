{{--
    Extracto animado de la biblioteca de Office ($walkthrough, $audio = URL por paso o null, $tabs = pestañas de la cinta).
    Una ventana simulada de Excel, Word, PowerPoint u Outlook que se anima paso a paso con subtítulos y narración
    (audio de ElevenLabs si existe; si no, la voz del navegador). Toda la escena es HTML: nítida y liviana.
--}}
<div x-data="officeWalkthrough(@js($walkthrough), @js($audio), @js($tabs))" class="text-slate-800">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h3 tabindex="-1" data-slide-title class="text-2xl font-extrabold text-slate-900 outline-none">Míralo en acción</h3>
        <p class="text-sm font-semibold tabular-nums text-slate-600" aria-live="polite" x-text="step < 0 ? steps.length + ' pasos' : 'Paso ' + (step + 1) + ' de ' + steps.length"></p>
    </div>
    <p class="mt-2 max-w-2xl leading-7 text-slate-600" x-text="wt.intro"></p>

    {{-- Ventana simulada del programa --}}
    <div x-ref="stage" class="relative mt-4 select-none overflow-hidden rounded-xl border border-slate-300 bg-white text-[13px] text-slate-800 shadow-sm" aria-hidden="true">
        <div class="flex items-center gap-3 px-3 py-1.5 text-white" :style="'background:' + color">
            <span class="grid h-5 w-5 place-items-center rounded bg-white/20 text-[11px] font-black" x-text="appLetter"></span>
            <span class="truncate text-xs font-semibold" x-text="(wt.file_name || 'Documento') + ' · ' + appName"></span>
        </div>
        {{-- Cinta de opciones --}}
        <div class="border-b border-slate-200 bg-slate-50">
            <div class="flex gap-0.5 overflow-hidden px-2 pt-1 text-xs">
                <template x-for="tab in tabs" :key="tab">
                    <span :data-wt="'tab:' + tab" class="whitespace-nowrap rounded-t px-2.5 py-1.5 transition-colors"
                        :class="state.tab === tab ? 'bg-white font-bold shadow-[0_-1px_0_0_inset] ' : 'text-slate-600'" :style="state.tab === tab ? 'color:' + color : ''" x-text="tab"></span>
                </template>
            </div>
            <div class="flex h-14 items-center gap-2 bg-white px-3">
                <template x-if="state.control">
                    <span data-wt="control" class="inline-flex items-center gap-2 rounded-md border-2 px-3 py-1.5 text-xs font-bold transition" :style="'border-color:' + color + ';color:' + color" :class="pressed ? 'scale-95 bg-slate-100' : 'bg-white'">
                        <span class="h-4 w-4 rounded-sm" :style="'background:' + color + '33'"></span><span x-text="state.control"></span>
                    </span>
                </template>
                <template x-for="n in (state.control ? 4 : 6)"><span class="h-8 w-14 rounded bg-slate-100"></span></template>
            </div>
        </div>

        {{-- Excel: cuadro de nombres, barra de fórmulas y hoja --}}
        <template x-if="app === 'excel'">
            <div>
                <div class="flex items-center gap-2 border-b border-slate-200 px-2 py-1 font-mono text-xs">
                    <span class="w-14 rounded border border-slate-200 px-1.5 py-0.5 text-center" x-text="state.selection.split(':')[0] || 'A1'"></span>
                    <span class="italic text-slate-500">fx</span>
                    <span class="min-h-5 flex-1 truncate rounded border border-slate-200 px-1.5 py-0.5" x-text="formulaBar"></span>
                </div>
                <div class="overflow-x-auto">
                    <table class="border-collapse font-mono text-xs tabular-nums">
                        <thead><tr><th class="w-8 border border-slate-200 bg-slate-100"></th><template x-for="c in grid.cols"><th class="min-w-[5.5rem] border border-slate-200 bg-slate-100 px-2 py-0.5 font-semibold text-slate-600" x-text="c"></th></template></tr></thead>
                        <tbody>
                            <template x-for="r in grid.rows">
                                <tr>
                                    <th class="border border-slate-200 bg-slate-100 px-1 font-semibold text-slate-600" x-text="r"></th>
                                    <template x-for="c in grid.cols">
                                        <td :data-wt="'cell:' + c + r" class="h-6 max-w-[9rem] truncate border border-slate-200 px-2 transition-colors duration-300"
                                            :class="[inSelection(c, r) ? 'bg-emerald-50 outline outline-2 -outline-offset-2 outline-emerald-600' : '', r === 1 ? 'font-bold' : '', fresh.includes(c + r) ? 'bg-amber-100' : '', isNumber(cellText(c, r)) ? 'text-right' : '', (state.formats[c + r] || []).includes('negrita') ? 'font-bold' : '', state.table && r <= state.tableRows && inRange(c, r, state.table) ? (r === 1 ? 'bg-emerald-700 text-white' : (r % 2 ? 'bg-emerald-50' : '')) : '']"
                                            x-text="cellText(c, r)"></td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <template x-if="state.chart">
                    <div class="m-3 flex h-28 w-64 items-end gap-2 rounded border border-slate-200 bg-white p-3">
                        <template x-for="(bar, i) in chartBars" :key="i"><span class="flex-1 rounded-t transition-all duration-700" :style="'height:' + bar + '%;background:' + color"></span></template>
                    </div>
                </template>
            </div>
        </template>

        {{-- Word: una página --}}
        <template x-if="app === 'word'">
            <div class="bg-slate-100 px-4 py-5 sm:px-10">
                <div class="mx-auto min-h-[14rem] max-w-xl bg-white px-6 py-6 shadow sm:px-10">
                    <template x-for="(p, i) in state.paragraphs" :key="i">
                        <p :data-wt="'par:' + (i + 1)" class="mb-2 leading-6 transition-all duration-300" :class="paragraphClass(i)" x-text="paragraphText(i)"></p>
                    </template>
                </div>
            </div>
        </template>

        {{-- PowerPoint: miniaturas y diapositiva --}}
        <template x-if="app === 'powerpoint'">
            <div class="flex gap-3 bg-slate-100 p-3">
                <div class="hidden w-20 shrink-0 space-y-2 sm:block"><template x-for="n in 3"><div class="aspect-video rounded border bg-white" :class="n === 1 ? 'border-2' : 'border-slate-300'" :style="n === 1 ? 'border-color:' + color : ''"></div></template></div>
                <div class="relative aspect-video flex-1 overflow-hidden rounded bg-white p-5 shadow sm:p-8" :class="slideAnim ? 'animate-[wt-fade_.7s_ease-out]' : ''">
                    <div class="absolute inset-x-0 top-0 h-2" :style="'background:' + color"></div>
                    <p data-wt="par:titulo" class="text-lg font-extrabold leading-tight sm:text-2xl" :class="paragraphClass('titulo')" x-text="state.title"></p>
                    <ul class="mt-3 space-y-1.5 sm:mt-5">
                        <template x-for="(p, i) in state.paragraphs" :key="i"><li :data-wt="'par:' + (i + 1)" class="flex gap-2 leading-6 transition-all duration-300" :class="paragraphClass(i)"><span :style="'color:' + color">•</span><span x-text="paragraphText(i)"></span></li></template>
                    </ul>
                    <p x-show="wt.document.meta" class="absolute bottom-2 right-3 text-[10px] text-slate-400" x-text="wt.document.meta"></p>
                </div>
            </div>
        </template>

        {{-- Outlook: mensaje nuevo --}}
        <template x-if="app === 'outlook'">
            <div class="space-y-1.5 p-4">
                <template x-for="field in ['para', 'cc', 'asunto']" :key="field">
                    <div class="flex items-center gap-2 border-b border-slate-200 pb-1.5">
                        <span class="w-16 text-xs font-semibold capitalize text-slate-500" x-text="field === 'cc' ? 'CC' : field"></span>
                        <span :data-wt="'par:' + field" class="min-h-5 flex-1 transition-colors" :class="state.selected === field ? 'bg-sky-50' : ''" x-text="fieldText(field)"></span>
                    </div>
                </template>
                <div class="pt-2">
                    <template x-for="(p, i) in state.paragraphs" :key="i"><p :data-wt="'par:' + (i + 1)" class="mb-1.5 leading-6 transition-all" :class="paragraphClass(i)" x-text="paragraphText(i)"></p></template>
                </div>
            </div>
        </template>

        {{-- Puntero --}}
        <svg x-show="step >= 0" class="pointer-events-none absolute left-0 top-0 z-10 h-6 w-6 drop-shadow transition-transform duration-700 ease-out motion-reduce:transition-none" :style="'transform: translate(' + cursor.x + 'px,' + cursor.y + 'px)'" viewBox="0 0 24 24"><path d="M4 2l14 9-6.5 1.5L15 20l-3 1.5-3.5-7.5L4 18z" fill="#0f172a" stroke="#fff" stroke-width="1.5" stroke-linejoin="round"/></svg>
    </div>

    {{-- Subtítulos: siempre visibles, así el extracto se entiende sin sonido --}}
    <p class="mt-3 min-h-[3.5rem] max-w-2xl rounded-xl bg-slate-900 px-4 py-3 text-sm leading-6 text-white" aria-live="polite"
        x-text="step < 0 ? 'Pulsa Reproducir para ver el paso a paso.' : steps[step].narration"></p>

    <div class="mt-3 flex flex-wrap items-center gap-2">
        <button type="button" @click="playing ? pause() : play()" class="inline-flex min-h-11 items-center rounded-xl bg-blue-600 px-5 text-sm font-bold text-white transition hover:bg-blue-700"
            x-text="playing ? 'Pausar' : (step >= steps.length - 1 ? 'Ver de nuevo' : (step < 0 ? 'Reproducir' : 'Continuar'))"></button>
        <button type="button" @click="go(step - 1)" :disabled="step <= 0" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:border-blue-400 disabled:opacity-40">Paso anterior</button>
        <button type="button" @click="go(step + 1)" :disabled="step >= steps.length - 1" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:border-blue-400 disabled:opacity-40">Paso siguiente</button>
        <button type="button" @click="toggleSound()" :aria-pressed="(! muted).toString()" class="inline-flex min-h-11 items-center rounded-xl px-3 text-sm font-bold text-slate-600 underline-offset-4 hover:underline" x-text="muted ? 'Activar voz' : 'Silenciar voz'"></button>
        <span class="ml-auto text-xs text-slate-500" x-text="voiceLabel"></span>
    </div>
    <p x-show="step === steps.length - 1 && ! playing && wt.summary" x-cloak class="mt-3 max-w-2xl rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm leading-6 text-emerald-950"><span class="font-bold">Listo: </span><span x-text="wt.summary"></span></p>
</div>
