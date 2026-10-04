import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

const toastQueueKey = 'aprendeGuateToastQueue';

const ensureToastRegion = () => {
    let region = document.getElementById('ag-toast-region');
    if (!region) {
        region = document.createElement('div');
        region.id = 'ag-toast-region';
        region.className = 'pointer-events-none fixed right-4 top-4 z-[100] flex w-[min(22rem,calc(100vw-2rem))] flex-col gap-3';
        region.setAttribute('aria-live', 'polite');
        document.body.appendChild(region);
    }
    return region;
};

const renderToast = ({ message, type = 'info', duration = 5200 }) => {
    const region = ensureToastRegion();
    const toast = document.createElement('div');
    const palette = {
        success: 'border-emerald-200 bg-emerald-50 text-emerald-900',
        error: 'border-rose-200 bg-rose-50 text-rose-900',
        info: 'border-blue-200 bg-white text-slate-800',
    }[type] || 'border-blue-200 bg-white text-slate-800';
    toast.className = `ag-toast pointer-events-auto flex items-start gap-3 rounded-2xl border p-4 text-sm shadow-xl shadow-slate-900/10 ${palette}`;
    toast.innerHTML = `<span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-current opacity-70"></span><p class="min-w-0 flex-1 leading-5"></p><button type="button" class="rounded-lg p-1 text-current opacity-60 transition hover:bg-black/5 hover:opacity-100" aria-label="Cerrar notificación">×</button>`;
    toast.querySelector('p').textContent = message;
    const close = () => {
        toast.classList.add('ag-toast-leaving');
        window.setTimeout(() => toast.remove(), 180);
    };
    toast.querySelector('button').addEventListener('click', close);
    region.appendChild(toast);
    window.setTimeout(close, duration);
};

window.agToast = (message, options = {}) => {
    if (!message) return;
    if (document.hidden) {
        const queue = JSON.parse(sessionStorage.getItem(toastQueueKey) || '[]');
        queue.push({ message, ...options });
        sessionStorage.setItem(toastQueueKey, JSON.stringify(queue.slice(-5)));
        return;
    }
    renderToast({ message, ...options });
};

window.addEventListener('ag:toast', (event) => window.agToast(event.detail?.message, event.detail || {}));
document.addEventListener('visibilitychange', () => {
    if (document.hidden) return;
    const queue = JSON.parse(sessionStorage.getItem(toastQueueKey) || '[]');
    sessionStorage.removeItem(toastQueueKey);
    queue.forEach((toast, index) => window.setTimeout(() => renderToast(toast), index * 120));
});

window.addEventListener('DOMContentLoaded', () => {
    const generationTasksKey = 'aprendeGuateGenerationTasks';
    const readGenerationTasks = () => JSON.parse(localStorage.getItem(generationTasksKey) || '[]');
    const writeGenerationTasks = (tasks) => localStorage.setItem(generationTasksKey, JSON.stringify(tasks.slice(-8)));

    const pollGenerationTasks = async () => {
        const tasks = readGenerationTasks();
        if (!tasks.length) return;
        const remaining = [];
        for (const task of tasks) {
            try {
                const response = await fetch(task.statusUrl, {headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'}, cache: 'no-store'});
                if (!response.ok) { remaining.push(task); continue; }
                const data = await response.json();
                const status = data[`${task.kind}_status`];
                if (status === 'completed') {
                    window.agToast(`La ${task.kind === 'lesson' ? 'lección' : 'práctica'} de «${data.title || task.title}» ya está lista.`, {type: 'success'});
                } else if (status === 'failed') {
                    window.agToast(`No se pudo generar la ${task.kind === 'lesson' ? 'lección' : 'práctica'} de «${data.title || task.title}».`, {type: 'error'});
                } else {
                    remaining.push(task);
                }
            } catch (error) {
                remaining.push(task);
            }
        }
        writeGenerationTasks(remaining);
    };

    window.setInterval(pollGenerationTasks, 3500);
    pollGenerationTasks();

    document.querySelectorAll('[data-generation-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');
            if (!button || button.disabled) return;
            if (form.dataset.generationStatusUrl) {
                const kind = /práctica/i.test(button.textContent || '') ? 'practice' : 'lesson';
                const tasks = readGenerationTasks().filter((task) => task.statusUrl !== form.dataset.generationStatusUrl || task.kind !== kind);
                tasks.push({statusUrl: form.dataset.generationStatusUrl, kind, title: form.dataset.generationTitle || 'tu tema'});
                writeGenerationTasks(tasks);
            }
            button.disabled = true;
            button.dataset.originalText = button.textContent.trim();
            button.innerHTML = '<span class="ag-spinner inline-block align-[-.15em] mr-2"></span>Generando…';
            button.classList.add('opacity-80', 'cursor-wait');
        });
    });

    document.querySelectorAll('.student-content pre').forEach((pre) => {
        const metadata = pre.nextElementSibling?.textContent?.toLowerCase() || '';
        if (!metadata.includes('csv') && !metadata.includes('text')) return;
        const content = pre.textContent.trim();
        if (metadata.includes('csv')) {
            const rows = content.split(/\r?\n/).filter(Boolean).map((row) => row.split(',').map((cell) => cell.trim()));
            if (!rows.length) return;
            const wrapper = document.createElement('div');
            wrapper.className = 'overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50 p-3';
            const table = document.createElement('table');
            table.className = 'min-w-full text-left text-xs';
            const head = document.createElement('thead');
            head.className = 'border-b border-slate-200 text-slate-600';
            const headRow = document.createElement('tr');
            rows[0].forEach((cell) => { const th = document.createElement('th'); th.className = 'px-3 py-2 font-extrabold'; th.textContent = cell; headRow.appendChild(th); });
            head.appendChild(headRow);
            const body = document.createElement('tbody');
            body.className = 'divide-y divide-slate-200 text-slate-700';
            rows.slice(1).forEach((row) => { const tr = document.createElement('tr'); row.forEach((cell) => { const td = document.createElement('td'); td.className = 'px-3 py-2'; td.textContent = cell; tr.appendChild(td); }); body.appendChild(tr); });
            table.append(head, body);
            wrapper.appendChild(table);
            pre.replaceWith(wrapper);
        } else {
            pre.className = 'mt-5 whitespace-pre-wrap rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-700';
        }
    });

    const hlsVideos = [...document.querySelectorAll('video[data-hls-source]')];

    hlsVideos.filter((video) => video.canPlayType('application/vnd.apple.mpegurl')).forEach((video) => {
        video.src = video.dataset.hlsSource;
    });

    const hlsCandidates = hlsVideos.filter((video) => !video.canPlayType('application/vnd.apple.mpegurl'));
    if (hlsCandidates.length) {
        import('hls.js').then(({ default: Hls }) => {
            if (!Hls.isSupported()) return;

            hlsCandidates.forEach((video) => {
                const player = new Hls();
                player.loadSource(video.dataset.hlsSource);
                player.attachMedia(video);
            });
        });
    }

    const taskKey = 'aprendeGuateVideoTask';
    const showProgress = (label, percent, state = 'processing') => {
        let bubble = document.getElementById('video-progress-bubble');
        if (!bubble) {
            bubble = document.createElement('div');
            bubble.id = 'video-progress-bubble';
            bubble.className = 'video-progress-bubble';
            bubble.innerHTML = '<div class="flex items-start gap-3"><span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-sky-500"></span><div class="min-w-0 flex-1"><p class="video-progress-title text-sm font-bold text-slate-900"></p><p class="video-progress-label mt-1 text-xs text-slate-500"></p><div class="video-progress-track"><div class="video-progress-fill"></div></div><p class="video-progress-percent mt-2 text-right text-xs font-bold text-sky-700"></p></div></div>';
            document.body.appendChild(bubble);
        }
        bubble.querySelector('.video-progress-title').textContent = state === 'failed' ? 'Error al procesar el video' : 'Video de la clase';
        bubble.querySelector('.video-progress-label').textContent = label;
        bubble.querySelector('.video-progress-fill').style.width = `${percent}%`;
        bubble.querySelector('.video-progress-percent').textContent = `${percent}%`;
        if (state === 'failed') bubble.querySelector('.video-progress-fill').classList.replace('bg-sky-600', 'bg-rose-600');
        return bubble;
    };

    const pollVideoTask = () => {
        const task = JSON.parse(localStorage.getItem(taskKey) || 'null');
        if (!task?.classId) return;

        fetch(`/admin/classes/${task.classId}/video-status`, { headers: { Accept: 'application/json' } })
            .then((response) => response.ok ? response.json() : Promise.reject())
            .then((data) => {
                if (data.status === 'ready') {
                    showProgress('Procesamiento terminado. El video ya está disponible.', 100, 'ready');
                    localStorage.removeItem(taskKey);
                    window.setTimeout(() => document.getElementById('video-progress-bubble')?.remove(), 6000);
                    return;
                }
                if (data.status === 'failed') {
                    showProgress(data.error || 'El procesamiento falló.', data.progress || 0, 'failed');
                    localStorage.removeItem(taskKey);
                    return;
                }
                const label = data.status === 'queued' ? 'Video en cola para procesarse.' : 'Preparando versiones 360p, 480p y 720p.';
                showProgress(label, data.progress || 0);
                window.setTimeout(pollVideoTask, 3000);
            })
            .catch(() => window.setTimeout(pollVideoTask, 5000));
    };

    document.querySelectorAll('[data-video-upload-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const input = form.querySelector('input[name="video_file"]');
            if (!input?.files?.length) return;

            event.preventDefault();
            const request = new XMLHttpRequest();
            request.open(form.method || 'POST', form.action);
            request.setRequestHeader('Accept', 'application/json');
            request.upload.addEventListener('progress', (progress) => {
                if (progress.lengthComputable) showProgress('Subiendo video desde tu computadora…', Math.round((progress.loaded / progress.total) * 100), 'uploading');
            });
            request.addEventListener('load', () => {
                if (request.status >= 200 && request.status < 300) {
                    const response = JSON.parse(request.responseText);
                    localStorage.setItem(taskKey, JSON.stringify({ classId: response.classId }));
                    showProgress('Archivo recibido. Preparando las calidades…', 0);
                    window.location.assign(response.redirect);
                } else {
                    showProgress('No se pudo subir el video. Revisa el archivo e inténtalo de nuevo.', 0, 'failed');
                }
            });
            request.addEventListener('error', () => showProgress('La conexión se interrumpió durante la carga.', 0, 'failed'));
            request.send(new FormData(form));
        });
    });

    pollVideoTask();
});
