{{-- Reproductor de los extractos animados de Office (se incluye una vez por página). --}}
<style>@keyframes wt-fade { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }</style>
<script nonce="{{ Vite::cspNonce() }}">
function officeWalkthrough(wt, audio, tabs) {
    const APPS = { excel: ['Excel', '#107C41', 'X'], word: ['Word', '#185ABD', 'W'], powerpoint: ['PowerPoint', '#C43E1C', 'P'], outlook: ['Outlook', '#0F6CBD', 'O'] };
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const colIndex = (letters) => [...letters].reduce((n, l) => n * 26 + l.charCodeAt(0) - 64, 0);
    const colName = (n) => { let s = ''; while (n > 0) { n--; s = String.fromCharCode(65 + (n % 26)) + s; n = Math.floor(n / 26); } return s; };
    const parseRange = (range) => {
        const m = /^([A-Z]{1,2})(\d{1,3})(?::([A-Z]{1,2})(\d{1,3}))?$/.exec(range || '');
        if (!m) return null;
        const c1 = colIndex(m[1]), r1 = +m[2], c2 = m[3] ? colIndex(m[3]) : c1, r2 = m[4] ? +m[4] : r1;
        return { c1: Math.min(c1, c2), c2: Math.max(c1, c2), r1: Math.min(r1, r2), r2: Math.max(r1, r2) };
    };
    return {
        wt, audio, tabs, steps: wt.steps, step: -1, playing: false, muted: false, typed: null, pressed: false, fresh: [], slideAnim: false,
        cursor: { x: 24, y: 24 }, timer: null, sound: null, token: 0,
        app: wt.app, appName: (APPS[wt.app] || APPS.excel)[0], color: (APPS[wt.app] || APPS.excel)[1], appLetter: (APPS[wt.app] || APPS.excel)[2],
        get voiceLabel() {
            if (this.muted) return 'Solo subtítulos';
            return this.audio.some(Boolean) ? 'Narración con voz natural' : ('speechSynthesis' in window ? 'Voz del navegador' : 'Solo subtítulos');
        },
        get grid() {
            let cols = Math.max(1, ...this.wt.sheet.map((row) => row.length)), rows = Math.max(this.wt.sheet.length, 1);
            this.steps.forEach((s) => { const r = parseRange(s.target); if (r) { cols = Math.max(cols, r.c2); rows = Math.max(rows, r.r2); } });
            return { cols: Array.from({ length: Math.min(cols, 10) }, (_, i) => colName(i + 1)), rows: Array.from({ length: Math.min(rows + 1, 16) }, (_, i) => i + 1) };
        },
        /** Estado de la escena después de aplicar los pasos hasta el actual (así se puede ir y volver). */
        get state() {
            const st = { tab: '', control: '', selection: '', cells: {}, formulas: {}, formats: {}, table: '', tableRows: 0, chart: '', paragraphs: [...this.wt.document.paragraphs], title: this.wt.document.title, pformats: {}, fields: { para: '', cc: '', asunto: this.wt.document.title }, selected: '' };
            if (this.app === 'outlook') { const meta = this.wt.document.meta || ''; st.fields.para = (/Para:\s*([^·]+)/i.exec(meta) || [])[1]?.trim() || ''; st.fields.cc = (/CC:\s*([^·]+)/i.exec(meta) || [])[1]?.trim() || ''; }
            this.wt.sheet.forEach((row, r) => row.forEach((value, c) => { st.cells[colName(c + 1) + (r + 1)] = value; }));
            for (let i = 0; i <= this.step && i < this.steps.length; i++) {
                const s = this.steps[i], current = i === this.step;
                if (s.tab) st.tab = s.tab;
                // El botón se muestra en la cinta solo durante el paso que lo usa.
                st.control = current ? (s.control || '') : '';
                if (s.target) { st.selection = s.target; st.selected = s.target; }
                const done = !current || this.typed === null;
                if (this.app === 'excel') {
                    if (s.action === 'type' && done) { st.formulas[s.target] = s.value; Object.assign(st.cells, s.results || {}); }
                    if (s.action === 'fill' && done) Object.assign(st.cells, s.results || {});
                    if (s.action === 'format') {
                        const range = parseRange(s.target);
                        if (s.value === 'tabla') { st.table = s.target; st.tableRows = range ? range.r2 : 0; }
                        else if (s.value === 'grafico') st.chart = s.target;
                        else if (range) for (let c = range.c1; c <= range.c2; c++) for (let r = range.r1; r <= range.r2; r++) (st.formats[colName(c) + r] ||= []).push(s.value);
                    }
                } else {
                    const key = s.target === 'titulo' ? 'titulo' : (/^\d+$/.test(s.target) ? +s.target - 1 : s.target);
                    if (s.action === 'type' && done) {
                        if (key === 'titulo') st.title = s.value;
                        else if (typeof key === 'number') st.paragraphs[key] = s.value;
                        else if (key in st.fields) st.fields[key] = s.value;
                    }
                    if (s.action === 'format' && s.value) (st.pformats[key] ||= []).push(s.value);
                }
            }
            return st;
        },
        get formulaBar() {
            const s = this.steps[this.step];
            if (s && s.action === 'type' && this.typed !== null) return this.typed;
            const sel = this.state.selection.split(':')[0];
            return this.state.formulas[sel] || this.state.cells[sel] || '';
        },
        get chartBars() {
            const range = parseRange(this.state.chart);
            if (!range) return [];
            const values = [];
            for (let r = range.r1; r <= range.r2; r++) for (let c = range.c1; c <= range.c2; c++) { const v = parseFloat(String(this.state.cells[colName(c) + r] || '').replace(/[Q,\s]/g, '')); if (!isNaN(v)) values.push(v); }
            const max = Math.max(...values, 1);
            return values.slice(0, 12).map((v) => Math.max(6, Math.round(v / max * 100)));
        },
        cellText(c, r) {
            const s = this.steps[this.step], ref = c + r;
            if (s && s.action === 'type' && this.typed !== null && s.target === ref) return this.typed;
            const value = this.state.cells[ref] ?? '';
            const formats = this.state.formats[ref] || [];
            if (formats.includes('moneda') && this.isNumber(value)) return 'Q ' + Number(String(value).replace(/,/g, '')).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            if (formats.includes('porcentaje') && this.isNumber(value)) { const n = Number(value); return (n <= 1 ? n * 100 : n).toLocaleString('es-GT', { maximumFractionDigits: 1 }) + ' %'; }
            return value;
        },
        isNumber(value) { return value !== '' && !isNaN(Number(String(value).replace(/[Q,\s]/g, ''))); },
        inRange(c, r, range) { const p = parseRange(range); return p && colIndex(c) >= p.c1 && colIndex(c) <= p.c2 && r >= p.r1 && r <= p.r2; },
        inSelection(c, r) { return this.step >= 0 && this.inRange(c, r, this.state.selection); },
        paragraphText(i) {
            const s = this.steps[this.step];
            const key = s && (/^\d+$/.test(s.target) ? +s.target - 1 : s.target);
            return s && s.action === 'type' && this.typed !== null && key === i ? this.typed : this.state.paragraphs[i];
        },
        fieldText(field) {
            const s = this.steps[this.step];
            return s && s.action === 'type' && this.typed !== null && s.target === field ? this.typed : this.state.fields[field];
        },
        paragraphClass(i) {
            const f = this.state.pformats[i] || [], selected = String(this.state.selected) === String(typeof i === 'number' ? i + 1 : i) && this.step >= 0;
            return [selected ? 'bg-sky-100' : '', f.includes('negrita') ? 'font-bold' : '', f.includes('cursiva') ? 'italic' : '', f.includes('subrayado') ? 'underline' : '',
                f.includes('titulo1') ? 'text-xl font-bold text-[#1F3864]' : '', f.includes('titulo2') ? 'text-base font-bold text-[#2F5496]' : '', f.includes('centrar') ? 'text-center' : '', f.includes('derecha') ? 'text-right' : '',
                f.includes('vinetas') ? 'list-item ml-5 list-disc' : '', f.includes('numeracion') ? 'list-item ml-5 list-decimal' : '', f.includes('resaltar') ? 'bg-yellow-200' : ''].join(' ');
        },
        go(index, autoplay = false) {
            if (index < 0 || index >= this.steps.length) return;
            const token = ++this.token;
            this.stopSound();
            window.clearTimeout(this.timer);
            if (!autoplay) this.playing = false;
            this.step = index;
            const s = this.steps[index];
            this.fresh = []; this.typed = null; this.pressed = false;
            if (this.app === 'powerpoint' && ['Animaciones', 'Transiciones'].includes(s.tab) && s.action === 'click') { this.slideAnim = false; this.$nextTick(() => { this.slideAnim = true; }); }
            this.$nextTick(() => this.moveCursor(s));
            if (s.action === 'click' || s.action === 'format') window.setTimeout(() => { if (token === this.token) this.pressed = true; }, reduced ? 0 : 700);
            if (s.action === 'type' && !reduced) this.type(s.value, token, () => this.markFresh(s));
            else this.markFresh(s);
            this.narrate(s, index, token);
        },
        type(text, token, done) {
            let i = 0;
            this.typed = '';
            const tick = () => {
                if (token !== this.token) return;
                this.typed = text.slice(0, ++i);
                if (i < text.length) this.timer = window.setTimeout(tick, Math.max(25, Math.min(70, 1800 / text.length)));
                else window.setTimeout(() => { if (token === this.token) { this.typed = null; done(); } }, 350);
            };
            this.timer = window.setTimeout(tick, reduced ? 0 : 650);
        },
        markFresh(s) { this.fresh = Object.keys(s.results || {}); },
        moveCursor(s) {
            const stage = this.$refs.stage;
            if (!stage) return;
            const key = s.action === 'tab' ? 'tab:' + s.tab : (['click', 'format'].includes(s.action) && s.control ? 'control' : (this.app === 'excel' ? 'cell:' + (s.target || '').split(':')[0] : 'par:' + s.target));
            const el = stage.querySelector(`[data-wt="${CSS.escape(key)}"]`) || (s.tab ? stage.querySelector(`[data-wt="${CSS.escape('tab:' + s.tab)}"]`) : null);
            if (!el) return;
            const a = stage.getBoundingClientRect(), b = el.getBoundingClientRect();
            this.cursor = { x: Math.round(b.left - a.left + Math.min(b.width / 2, 40)), y: Math.round(b.top - a.top + b.height / 2) };
        },
        narrate(s, index, token) {
            const next = () => { if (token !== this.token || !this.playing) return; this.timer = window.setTimeout(() => { if (token !== this.token || !this.playing) return; index < this.steps.length - 1 ? this.go(index + 1, true) : (this.playing = false); }, 700); };
            const words = s.narration.split(/\s+/).length;
            const byTime = () => { this.timer = window.setTimeout(next, Math.max(2500, words / 2.6 * 1000)); };
            if (this.muted || !this.playing) { if (this.playing) byTime(); return; }
            if (this.audio[index]) {
                this.sound = new Audio(this.audio[index]);
                this.sound.onended = next;
                this.sound.play().catch(byTime);
                return;
            }
            if ('speechSynthesis' in window) {
                const u = new SpeechSynthesisUtterance(s.narration);
                const voices = window.speechSynthesis.getVoices().filter((v) => v.lang.toLowerCase().startsWith('es'));
                u.voice = voices.find((v) => /419|MX|US|GT/i.test(v.lang)) || voices[0] || null;
                u.lang = u.voice?.lang || 'es-MX'; u.rate = 1;
                u.onend = next; u.onerror = byTime;
                window.speechSynthesis.speak(u);
                return;
            }
            byTime();
        },
        stopSound() {
            if (this.sound) { this.sound.onended = null; this.sound.pause(); this.sound = null; }
            if ('speechSynthesis' in window) window.speechSynthesis.cancel();
        },
        play() { this.playing = true; this.go(this.step >= this.steps.length - 1 ? 0 : this.step + 1, true); },
        pause() { this.playing = false; this.token++; this.stopSound(); window.clearTimeout(this.timer); this.typed = null; },
        toggleSound() { this.muted = !this.muted; if (this.muted) this.stopSound(); },
        destroy() { this.pause(); },
    };
}
</script>
