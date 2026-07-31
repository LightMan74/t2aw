/**
 * Module Timer réutilisable et synchronisé
 * Usage: TournamentTimer.init({ containerId: 'timer-container', showControls: true })
 */
const TournamentTimer = (function () {

    let config = {
        containerId: 'timer-container',
        showControls: true,
        playSound: true,
        pollInterval: 10000, // sync avec le serveur toutes les 1s
        apiUrl: 'api/timer.php'
    };

    let state = {
        duration: 0,
        startTime: null,
        pausedAt: null,
        status: 'stopped',
        soundEnabled: true,
        localOffset: 0 // décalage entre horloge serveur/client
    };

    let displayInterval = null;
    let pollTimeout = null;
    let soundsPlayed = { start: false, middle: false, end: false };

    // ---- Sons ----
    const sounds = {
        start: new Audio('sound/start.mp3'),
        middle: new Audio('sound/1minute.mp3'),
        end: new Audio('sound/stop.mp3')
    };

    function playSound(name) {
        if (config.showControls) {
            if (!state.soundEnabled) return;
            try {
                sounds[name].currentTime = 0;
                sounds[name].play();
            } catch (e) {
                console.warn('Erreur lecture son:', e);
            }
        }
    }

    // ---- Communication API ----
    async function apiCall(action, params = {}) {
        const body = new URLSearchParams({ action, ...params });
        const res = await fetch(config.apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body
        });
        return res.json();
    }

    async function fetchTimerState() {
        try {
            const res = await fetch(`${config.apiUrl}?action=get`);
            const data = await res.json();
            if (data.success) {
                updateState(data.timer);
            }
        } catch (e) {
            console.error('Erreur sync timer:', e);
        }
    }

    function updateState(timer) {
        const wasStatus = state.status;

        state.duration = parseInt(timer.duration);
        state.startTime = timer.start_time ? parseInt(timer.start_time) : null;
        state.pausedAt = timer.paused_at !== null ? parseInt(timer.paused_at) : null;
        state.status = timer.status;
        state.soundEnabled = !!parseInt(timer.sound_enabled);

        // reset des sons si nouveau départ
        if (wasStatus !== 'running' && state.status === 'running' && state.pausedAt === null) {
            soundsPlayed = { start: false, middle: false, end: false };
        }

        updateUI();
    }

    // ---- Calcul du temps restant ----
    function getElapsedSeconds() {
        if (state.status === 'paused') {
            return state.pausedAt;
        }
        if (state.status === 'running' && state.startTime) {
            const now = Date.now();
            const elapsed = (now - state.startTime) / 1000;
            return Math.min(elapsed, state.duration);
        }
        return 0;
    }

    function getRemainingSeconds() {
        return Math.max(0, state.duration - getElapsedSeconds());
    }

    function formatTime(seconds) {
        seconds = Math.max(0, Math.ceil(seconds));
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    }

    // ---- Boucle d'affichage (tick local à chaque frame/seconde) ----
    function tick() {
        const remaining = getRemainingSeconds();
        const elapsed = getElapsedSeconds();
        const half = state.duration / 2;

        // Mise à jour affichage
        const el = document.querySelector(`#${config.containerId} .timer-display`);
        if (el) el.textContent = formatTime(remaining);

        // Gestion classes visuelles (couleur proche de la fin)
        const container = document.getElementById(config.containerId);
        if (container) {
            container.classList.toggle('timer-warning', remaining <= 60 && remaining > 10 && state.status === 'running');
            container.classList.toggle('timer-danger', remaining <= 10 && state.status === 'running');
        }

        if (state.status === 'running') {
            // Son de départ
            if (!soundsPlayed.start && elapsed >= 0) {
                soundsPlayed.start = true;
                playSound('start');
            }
            // Son milieu (50%)
            if (!soundsPlayed.middle && elapsed >= half) {
                soundsPlayed.middle = true;
                playSound('middle');
            }
            // Son de fin
            if (!soundsPlayed.end && remaining <= 0) {
                soundsPlayed.end = true;
                playSound('end');
            }
        }

        updateButtonsUI();
    }

    // ---- UI ----
    function buildUI() {
        const container = document.getElementById(config.containerId);
        if (!container) {
            console.error(`Container #${config.containerId} introuvable`);
            return;
        }

        let controlsHtml = '';
        if (config.showControls) {
            controlsHtml = `
                <div class="timer-controls">
                    <input type="number" id="${config.containerId}-input" min="1" placeholder="Minutes" class="timer-input">
                    <button class="timer-btn timer-btn-start" data-action="start">Démarrer</button>
                    <button class="timer-btn timer-btn-pause" data-action="pause">Pause</button>
                    <button class="timer-btn timer-btn-resume" data-action="resume">Reprendre</button>
                    <button class="timer-btn timer-btn-stop" data-action="stop">Arrêter</button>
                    <label class="timer-sound-toggle">
                        <input type="checkbox" id="${config.containerId}-sound" checked>
                        Son
                    </label>
                </div>
            `;
        }

        container.innerHTML = `
            <div class="timer-display">00:00</div>
            ${controlsHtml}
        `;

        if (config.showControls) {
            container.querySelector('[data-action="start"]').addEventListener('click', () => {
                const input = document.getElementById(`${config.containerId}-input`);
                const minutes = parseFloat(input.value);
                if (!minutes || minutes <= 0) {
                    alert('Veuillez entrer une durée valide');
                    return;
                }
                apiCall('start', { duration: Math.round(minutes * 60) }).then(fetchTimerState);
            });

            container.querySelector('[data-action="pause"]').addEventListener('click', () => {
                apiCall('pause').then(fetchTimerState);
            });

            container.querySelector('[data-action="resume"]').addEventListener('click', () => {
                apiCall('resume').then(fetchTimerState);
            });

            container.querySelector('[data-action="stop"]').addEventListener('click', () => {
                if (confirm('Arrêter le timer ?')) {
                    apiCall('stop').then(fetchTimerState);
                }
            });

            container.querySelector(`#${config.containerId}-sound`).addEventListener('change', (e) => {
                const enabled = e.target.checked ? 1 : 0;
                apiCall('toggle_sound', { enabled }).then(fetchTimerState);
            });
        }
    }

    function updateButtonsUI() {
        if (!config.showControls) return;
        const container = document.getElementById(config.containerId);
        if (!container) return;

        const btnStart = container.querySelector('[data-action="start"]');
        const btnPause = container.querySelector('[data-action="pause"]');
        const btnResume = container.querySelector('[data-action="resume"]');
        const btnStop = container.querySelector('[data-action="stop"]');
        const soundCheckbox = container.querySelector(`#${config.containerId}-sound`);

        if (btnStart) btnStart.style.display = state.status === 'stopped' ? 'inline-block' : 'none';
        if (btnPause) btnPause.style.display = state.status === 'running' ? 'inline-block' : 'none';
        if (btnResume) btnResume.style.display = state.status === 'paused' ? 'inline-block' : 'none';
        if (btnStop) btnStop.style.display = state.status !== 'stopped' ? 'inline-block' : 'none';
        if (soundCheckbox) soundCheckbox.checked = state.soundEnabled;
    }

    function updateUI() {
        tick();
    }

    // ---- Polling serveur (synchronisation) ----
    function startPolling() {
        pollTimeout = setInterval(fetchTimerState, config.pollInterval);
    }

    function startDisplayLoop() {
        displayInterval = setInterval(tick, 250); // rafraîchissement fluide
    }

    // ---- Initialisation publique ----
    function init(userConfig = {}) {
        config = { ...config, ...userConfig };
        buildUI();
        fetchTimerState();
        startPolling();
        startDisplayLoop();
    }

    function destroy() {
        clearInterval(pollTimeout);
        clearInterval(displayInterval);
    }

    return { init, destroy, fetchTimerState };
})();