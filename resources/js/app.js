import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

window.addEventListener('DOMContentLoaded', () => {
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
