// Tablero de IA del panel de administración. Consulta los datos cada 5 segundos mientras la pestaña está visible.
const NS = 'http://www.w3.org/2000/svg';
const config = JSON.parse(document.getElementById('ai-pulse-config')?.textContent || 'null');
const root = document.querySelector('[data-pulse-root]');

if (config && root) {
    const q = (selector) => document.querySelector(selector);
    const css = (name) => getComputedStyle(q('.ai-pulse')).getPropertyValue(name).trim();
    const el = (tag, attrs = {}, text) => {
        const node = document.createElement(tag);
        Object.entries(attrs).forEach(([key, value]) => node.setAttribute(key, value));
        if (text !== undefined) node.textContent = text;
        return node;
    };
    const svg = (tag, attrs = {}) => {
        const node = document.createElementNS(NS, tag);
        Object.entries(attrs).forEach(([key, value]) => node.setAttribute(key, value));
        return node;
    };
    const short = (n) => (n >= 1e6 ? (n / 1e6).toFixed(n >= 1e7 ? 0 : 1) + ' M' : n >= 1e3 ? (n / 1e3).toFixed(n >= 1e4 ? 0 : 1) + ' k' : String(Math.round(n)));
    const money = (n) => (n === null || n === undefined ? '—' : 'US$ ' + n.toFixed(n < 10 ? 2 : 0));
    const number = (n) => Number(n).toLocaleString('es-GT');
    const providerColor = { openai: '--pulse-s1', gemini: '--pulse-s2', anthropic: '--pulse-s3', deepseek: '--pulse-s4' };
    const colorOf = (id) => css(providerColor[id] || '--pulse-s1');
    const rangeName = { 60: 'la última hora', 1440: 'hoy', 10080: 'los últimos 7 días', 43200: 'los últimos 30 días' };
    const state = { range: 60, provider: '', table: false, data: config.initial, updated: Date.now(), seen: new Set(), timer: null };

    // Curva suave que pasa por cada punto sin salirse del rango entre dos puntos.
    const smooth = (pts) => pts.map((p, i) => {
        if (!i) return `M${p[0].toFixed(1)} ${p[1].toFixed(1)}`;
        const a = pts[i - 2] || pts[i - 1]; const b = pts[i - 1]; const d = pts[i + 1] || p;
        const lo = Math.min(b[1], p[1]); const hi = Math.max(b[1], p[1]);
        const c1 = [b[0] + (p[0] - a[0]) / 6, Math.min(hi, Math.max(lo, b[1] + (p[1] - a[1]) / 6))];
        const c2 = [p[0] - (d[0] - b[0]) / 6, Math.min(hi, Math.max(lo, p[1] - (d[1] - b[1]) / 6))];
        return `C${c1[0].toFixed(1)} ${c1[1].toFixed(1)} ${c2[0].toFixed(1)} ${c2[1].toFixed(1)} ${p[0].toFixed(1)} ${p[1].toFixed(1)}`;
    }).join(' ');

    function sparkline(values, height = 34) {
        const s = svg('svg', { viewBox: `0 0 100 ${height}`, preserveAspectRatio: 'none', 'aria-hidden': 'true', class: 'ai-pulse-spark' });
        const max = Math.max(...values, 1); const color = css('--pulse-s1');
        const pts = values.map((v, i) => [i / Math.max(1, values.length - 1) * 100, height - 4 - v / max * (height - 8)]);
        const line = pts.map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1)).join(' ');
        s.append(svg('path', { d: `${line} L100 ${height} L0 ${height} Z`, fill: color, 'fill-opacity': '.12' }));
        s.append(svg('path', { d: line, fill: 'none', stroke: color, 'stroke-width': '2', 'vector-effect': 'non-scaling-stroke', 'stroke-linejoin': 'round' }));
        const last = pts[pts.length - 1];
        s.append(svg('path', { d: `M${last[0]} ${last[1]} L${last[0]} ${last[1]}`, stroke: color, 'stroke-width': '7', 'stroke-linecap': 'round', 'vector-effect': 'non-scaling-stroke' }));
        return s;
    }

    function renderKpis(data) {
        const k = data.kpis; const r = rangeName[data.range];
        const delta = (value) => value === null || value === undefined ? 'Sin datos del periodo anterior' : (value >= 0 ? '▲ ' : '▼ ') + Math.abs(value) + ' % frente al periodo anterior';
        const items = [
            ['Tokens', short(k.tokens), delta(k.deltas.tokens), k.spark.tokens, () => openDetail('overview')],
            ['Solicitudes', number(k.requests), delta(k.deltas.requests), k.spark.requests, () => openDetail('overview')],
            ['Costo estimado', data.has_prices ? money(k.cost) : '—', data.has_prices ? delta(k.deltas.cost) : 'Agrega precios en el orquestador', k.spark.tokens, () => openDetail('overview')],
            ['Solicitudes correctas', k.ok_rate === null ? '—' : k.ok_rate + ' %', k.failed + ' con fallo en ' + r, k.spark.ok, () => openDetail('failures')],
        ];
        q('[data-pulse-kpis]').replaceChildren(...items.map(([label, value, note, series, open]) => {
            const card = el('button', { type: 'button', class: 'ai-pulse-kpi' });
            card.append(el('span', { class: 'ai-pulse-kpi-label' }, label), el('span', { class: 'ai-pulse-kpi-value' }, value), el('span', { class: 'ai-pulse-kpi-note' }, note), sparkline(series));
            card.addEventListener('click', open);
            return card;
        }));
    }

    function renderArea(data) {
        const box = q('[data-pulse-area]'); const tip = q('[data-pulse-tip]');
        const points = data.timeline; const n = points.length;
        const shown = data.providers.filter((p) => (!state.provider || p.id === state.provider) && (p.tokens > 0 || !state.provider));
        const W = 1000, H = 240, L = 46, R = 10, T = 10, B = 26; const iw = W - L - R, ih = H - T - B;
        const totals = points.map((p) => shown.reduce((sum, s) => sum + (p.by[s.id] || 0), 0));
        const raw = Math.max(...totals, 1) * 1.08; const step = Math.pow(10, Math.floor(Math.log10(raw))); const max = Math.ceil(raw / step) * step;
        const x = (i) => L + i / Math.max(1, n - 1) * iw; const y = (v) => T + ih - v / max * ih;
        const label = (iso) => { const d = new Date(iso); return data.range <= 1440 ? d.toLocaleTimeString('es-GT', { hour: '2-digit', minute: '2-digit' }) : d.toLocaleDateString('es-GT', { day: 'numeric', month: 'short' }); };
        const chart = svg('svg', { viewBox: `0 0 ${W} ${H}`, role: 'img', 'aria-label': 'Tokens por proveedor en ' + rangeName[data.range] });
        for (let g = 0; g <= 4; g++) {
            const v = max / 4 * g;
            chart.append(svg('line', { x1: L, x2: W - R, y1: y(v), y2: y(v), stroke: css('--pulse-grid'), 'stroke-width': '1' }));
            const t = svg('text', { x: L - 8, y: y(v) + 4, 'text-anchor': 'end', class: 'ai-pulse-axis' }); t.textContent = short(v); chart.append(t);
        }
        [0, Math.floor(n / 3), Math.floor(2 * n / 3), n - 1].forEach((i, index) => {
            const t = svg('text', { x: x(i), y: H - 6, 'text-anchor': index === 0 ? 'start' : index === 3 ? 'end' : 'middle', class: 'ai-pulse-axis' });
            t.textContent = index === 3 ? 'ahora' : label(points[i].t); chart.append(t);
        });
        const base = points.map(() => 0);
        shown.forEach((p) => {
            const top = points.map((point, i) => base[i] + (point.by[p.id] || 0));
            if (top.every((v, i) => v === base[i])) return;
            const d = smooth(top.map((v, i) => [x(i), y(v)])) + ' L' + smooth(base.map((v, i) => [x(i), y(v)]).reverse()).slice(1) + ' Z';
            chart.append(svg('path', { d, fill: colorOf(p.id), 'fill-opacity': '.85', stroke: css('--pulse-surface'), 'stroke-width': '1.5', 'stroke-linejoin': 'round' }));
            top.forEach((v, i) => { base[i] = v; });
        });
        const hair = svg('line', { y1: T, y2: T + ih, stroke: css('--pulse-ink'), 'stroke-width': '1', 'stroke-dasharray': '3 3', opacity: '0' });
        chart.append(hair, svg('circle', { cx: x(n - 1), cy: y(totals[n - 1]), r: '4', fill: css('--pulse-ink'), stroke: css('--pulse-surface'), 'stroke-width': '2' }));
        const hit = svg('rect', { x: L, y: T, width: iw, height: ih, fill: 'transparent' }); chart.append(hit);
        box.querySelector('svg')?.remove(); box.prepend(chart);
        hit.addEventListener('pointermove', (event) => {
            const r = chart.getBoundingClientRect(); const i = Math.max(0, Math.min(n - 1, Math.round(((event.clientX - r.left) / r.width * W - L) / iw * (n - 1))));
            hair.setAttribute('x1', x(i)); hair.setAttribute('x2', x(i)); hair.setAttribute('opacity', '.5');
            tip.replaceChildren(el('div', { class: 'ai-pulse-tip-title' }, label(points[i].t)));
            [...shown].reverse().forEach((p) => {
                const row = el('div', { class: 'ai-pulse-tip-row' }); const name = el('em'); const key = el('i'); key.style.background = colorOf(p.id);
                name.append(key, document.createTextNode(p.name)); row.append(name, el('strong', {}, short(points[i].by[p.id] || 0))); tip.append(row);
            });
            const total = el('div', { class: 'ai-pulse-tip-row' }); total.append(el('em', {}, 'Total'), el('strong', {}, short(totals[i]))); tip.append(total);
            tip.style.left = Math.min(x(i) / W * r.width + 12, r.width - 170) + 'px'; tip.classList.add('is-on');
        });
        hit.addEventListener('pointerleave', () => { tip.classList.remove('is-on'); hair.setAttribute('opacity', '0'); });

        q('[data-pulse-legend]').replaceChildren(...shown.map((p) => {
            const item = el('button', { type: 'button', class: 'ai-pulse-legend-item' }); const swatch = el('b'); swatch.style.background = colorOf(p.id);
            item.append(swatch, document.createTextNode(p.name + ' · ' + short(p.tokens))); item.addEventListener('click', () => openDetail('provider', p.id)); return item;
        }));
        const table = el('table', { class: 'ai-pulse-feed' }); const head = el('tr');
        head.append(el('th', {}, 'Momento'), ...shown.map((p) => el('th', { class: 'text-right' }, p.name)), el('th', { class: 'text-right' }, 'Total'));
        const thead = el('thead'); thead.append(head); const tbody = el('tbody');
        points.forEach((point, i) => {
            if (i % Math.ceil(n / 12) && i !== n - 1) return;
            const tr = el('tr'); tr.append(el('td', {}, label(point.t)), ...shown.map((p) => el('td', { class: 'text-right' }, short(point.by[p.id] || 0))), el('td', { class: 'text-right' }, short(totals[i]))); tbody.append(tr);
        });
        table.append(thead, tbody); q('[data-pulse-area-table]').replaceChildren(table);
    }

    function bars(target, rows, open, empty) {
        const max = Math.max(...rows.map((r) => r.value), 1);
        if (!rows.length) { q(target).replaceChildren(el('p', { class: 'text-sm text-slate-600' }, empty)); return; }
        q(target).replaceChildren(...rows.map((r) => {
            const b = el('button', { type: 'button', class: 'ai-pulse-bar', 'aria-label': `${r.name}: ${r.label}` });
            const name = el('span', { class: 'ai-pulse-bar-name' }, r.name); name.append(el('small', {}, r.sub));
            const track = el('span', { class: 'ai-pulse-bar-track' }); const fill = el('span', { class: 'ai-pulse-bar-fill' }); fill.style.width = Math.max(2, r.value / max * 100) + '%'; track.append(fill);
            b.append(name, track, el('span', { class: 'ai-pulse-bar-num' }, r.label)); b.addEventListener('click', () => open(r.key)); return b;
        }));
    }

    function renderRankings(data) {
        const none = 'Sin consumo en ' + rangeName[data.range] + '.';
        bars('[data-pulse-users]', data.users.map((u) => ({ key: String(u.id), name: u.name, sub: `${u.role} · ${number(u.requests)} solicitudes`, value: u.tokens, label: short(u.tokens) })), (key) => openDetail('user', key), none);
        bars('[data-pulse-models]', data.models.map((m) => ({ key: m.id, name: m.id, sub: m.provider_name + (data.has_prices ? ' · ' + money(m.cost) : ' · ' + number(m.requests) + ' solicitudes'), value: m.tokens, label: short(m.tokens) })), (key) => openDetail('model', key), none);
        bars('[data-pulse-agents]', data.agents.map((a) => ({ key: a.id, name: a.name, sub: number(a.requests) + ' solicitudes', value: a.tokens, label: short(a.tokens) })), (key) => openDetail('agent', key), none);
    }

    function renderStatus(data) {
        const s = data.status; const total = s.ok + s.failed; const rate = total ? (s.ok / total * 100).toFixed(1) : null;
        const wrap = el('div', { class: 'grid gap-3' });
        wrap.append(el('p', { class: 'text-3xl font-extrabold tabular-nums text-slate-900' }, rate === null ? '—' : rate + ' %'), el('p', { class: 'text-sm text-slate-600' }, total ? `${number(s.ok)} correctas de ${number(total)}${s.processing ? ' · ' + s.processing + ' en curso' : ''}` : 'Sin solicitudes en ' + rangeName[data.range]));
        const meter = el('div', { class: 'ai-pulse-meter', role: 'img', 'aria-label': (rate ?? 0) + ' % de solicitudes correctas' });
        const ok = el('span', { class: 'is-ok' }); ok.style.flex = String(s.ok || 0.0001); const bad = el('span', { class: 'is-bad' }); bad.style.flex = String(s.failed || 0.0001);
        meter.append(ok, bad); wrap.append(meter);
        const list = el('ul', { class: 'ai-pulse-list' });
        if (!s.reasons.length) list.append(el('li', {}, 'Sin fallos en ' + rangeName[data.range]));
        s.reasons.forEach((r) => { const li = el('li'); li.append(el('span', {}, '⚠ ' + r.reason), el('strong', {}, number(r.count))); list.append(li); });
        wrap.append(list);
        if (s.failed) { const open = el('button', { type: 'button', class: 'justify-self-start text-sm font-bold text-blue-700 hover:text-blue-900' }, 'Ver solicitudes con fallo'); open.addEventListener('click', () => openDetail('failures')); wrap.append(open); }
        q('[data-pulse-status]').replaceChildren(wrap);
    }

    function renderHeat(data) {
        const days = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom']; const max = Math.max(...data.heat.flat(), 1);
        const steps = ['--pulse-seq-1', '--pulse-seq-2', '--pulse-seq-3', '--pulse-seq-4', '--pulse-seq-5', '--pulse-seq-6', '--pulse-seq-7'];
        const grid = el('div', { class: 'ai-pulse-heat', role: 'img', 'aria-label': 'Solicitudes por día de la semana y hora en los últimos 30 días' });
        grid.append(el('span'));
        for (let h = 0; h < 24; h++) grid.append(el('span', { class: 'text-center' }, h % 6 === 0 ? String(h) : ''));
        data.heat.forEach((row, d) => {
            grid.append(el('span', {}, days[d]));
            row.forEach((v, h) => { const cell = el('span', { class: 'ai-pulse-cell', title: `${days[d]} ${h}:00 · ${v} solicitudes` }); cell.style.background = v ? css(steps[Math.min(6, Math.floor(v / max * 6.99))]) : css('--pulse-empty'); grid.append(cell); });
        });
        const scale = el('div', { class: 'ai-pulse-scale' }); scale.append(document.createTextNode('Menos'), ...steps.map((s) => { const b = el('b'); b.style.background = css(s); return b; }), document.createTextNode('Más'));
        q('[data-pulse-heat]').replaceChildren(grid, scale);
    }

    function renderFeed(data) {
        const rows = data.feed.map((r) => {
            const tr = el('tr', state.seen.size && !state.seen.has(r.id) ? { class: 'is-new' } : {});
            const status = el('td'); status.append(el('span', { class: 'ai-pulse-chip ' + (r.status === 'failed' ? 'is-bad' : r.status === 'processing' ? 'is-wait' : 'is-ok') }, r.status === 'failed' ? '⚠ ' + r.reason : r.status === 'processing' ? 'En curso' : '✓ Correcta'));
            tr.append(el('td', {}, r.time), el('td', {}, r.user), el('td', {}, r.agent), el('td', {}, r.model), el('td', { class: 'text-right' }, number(r.tokens)), el('td', { class: 'text-right' }, r.seconds === null ? '—' : r.seconds + ' s'), status);
            tr.tabIndex = 0; tr.addEventListener('click', () => openDetail('request', String(r.id))); tr.addEventListener('keydown', (e) => { if (e.key === 'Enter') openDetail('request', String(r.id)); });
            return tr;
        });
        q('[data-pulse-feed]').replaceChildren(...rows);
        if (!rows.length) { const tr = el('tr'); tr.append(el('td', { colspan: '7', class: 'text-slate-600' }, 'Aún no hay solicitudes registradas.')); q('[data-pulse-feed]').append(tr); }
        state.seen = new Set(data.feed.map((r) => r.id));
    }

    function render(data) {
        state.data = data; renderKpis(data); renderArea(data); renderRankings(data); renderStatus(data); renderHeat(data); renderFeed(data);
    }

    async function refresh() {
        root.classList.add('is-loading');
        try {
            const url = new URL(config.dataUrl); url.searchParams.set('range', state.range); if (state.provider) url.searchParams.set('provider', state.provider);
            // Consulta de fondo: no cuenta como actividad para el cierre por inactividad.
            const response = await fetch(url, { headers: { Accept: 'application/json', 'X-Background-Request': '1' } });
            if (response.ok) { render(await response.json()); state.updated = Date.now(); }
        } catch (error) { /* Se reintenta en la siguiente consulta; el tablero conserva los últimos datos. */ }
        root.classList.remove('is-loading');
    }

    // ---- Detalle ----
    const drawer = q('[data-pulse-drawer]'); let lastFocus = null;
    async function openDetail(type, key = '') {
        lastFocus = document.activeElement;
        const url = new URL(config.detailUrl); url.searchParams.set('type', type); url.searchParams.set('key', key); url.searchParams.set('range', state.range); if (state.provider) url.searchParams.set('provider', state.provider);
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) return;
        const d = await response.json();
        q('[data-detail-kind]').textContent = d.kind; q('[data-detail-title]').textContent = d.title;
        q('[data-detail-stats]').replaceChildren(...d.stats.map(([label, value]) => { const s = el('div', { class: 'ai-pulse-stat' }); s.append(el('span', {}, label), el('strong', {}, value)); return s; }));
        q('[data-detail-chart-wrap]').hidden = !d.series.length; if (d.series.length) { const spark = sparkline(d.series, 80); spark.style.height = '80px'; q('[data-detail-chart]').replaceChildren(spark); }
        q('[data-detail-list-title]').textContent = d.list_title;
        q('[data-detail-list]').replaceChildren(...d.list.map(([label, value]) => { const li = el('li'); li.append(el('span', {}, label), el('strong', {}, value)); return li; }));
        q('[data-detail-recent-wrap]').hidden = !d.recent.length;
        q('[data-detail-recent]').replaceChildren(...d.recent.map((r) => { const li = el('li'); const b = el('button', { type: 'button' }); b.append(el('span', {}, r.label), el('strong', {}, r.value)); b.addEventListener('click', () => openDetail('request', String(r.id))); li.append(b); return li; }));
        drawer.hidden = false; requestAnimationFrame(() => drawer.classList.add('is-open')); drawer.querySelector('[role=dialog]').focus();
    }
    const closeDetail = () => { drawer.classList.remove('is-open'); setTimeout(() => { drawer.hidden = true; }, 250); lastFocus?.focus(); };
    drawer.querySelectorAll('[data-pulse-close]').forEach((node) => node.addEventListener('click', closeDetail));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !drawer.hidden) closeDetail(); });

    // ---- Filtros y ciclo en vivo ----
    q('[data-pulse-ranges]').addEventListener('click', (e) => {
        const button = e.target.closest('button[data-range]'); if (!button) return;
        state.range = Number(button.dataset.range); q('[data-pulse-ranges]').querySelectorAll('button').forEach((b) => b.setAttribute('aria-pressed', String(b === button)));
        state.seen = new Set(); refresh();
    });
    q('[data-pulse-provider]').addEventListener('change', (e) => { state.provider = e.target.value; state.seen = new Set(); refresh(); });
    q('[data-pulse-table-toggle]').addEventListener('click', (e) => {
        state.table = !state.table; e.target.textContent = state.table ? 'Ver gráfica' : 'Ver tabla';
        q('[data-pulse-area]').hidden = state.table; q('[data-pulse-area-table]').hidden = !state.table;
    });
    const loop = () => { clearInterval(state.timer); state.timer = setInterval(() => { if (!document.hidden) refresh(); }, 5000); };
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
    setInterval(() => { const seconds = Math.round((Date.now() - state.updated) / 1000); q('[data-pulse-updated]').textContent = 'En vivo · actualizado hace ' + seconds + ' s'; }, 1000);
    new MutationObserver(() => render(state.data)).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    render(config.initial); loop();
}
