/**
 * Module Timer réutilisable et synchronisé
 * Usage: TournamentTimer.init({ containerId: 'timer-container', showControls: true })
 */
const TournamentTimer = (function () {

    let config = {
        idtournoi: null,
        containerId: 'timer-container',
        showControls: true,
        playSound: true,
        pollInterval: 10000, // sync avec le serveur toutes les 10s
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
    let firstLoad = true; // pour éviter de jouer les sons au chargement initial

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
    async function apiCall(action, idtournoi, params = {}) {
        const body = new URLSearchParams({ action, idtournoi, ...params });
        const res = await fetch(config.apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body
        });
        return res.json();
    }

    async function fetchTimerState() {
        try {
            const res = await fetch(`${config.apiUrl}?action=get&idtournoi=${config.idtournoi}`);
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
        const wasStartTime = state.startTime;

        state.duration = parseInt(timer.duration);
        state.startTime = timer.start_time ? parseInt(timer.start_time) : null;
        state.pausedAt = timer.paused_at !== null ? parseInt(timer.paused_at) : null;
        state.status = timer.status;
        state.soundEnabled = !!parseInt(timer.sound_enabled);

        // Met à jour l'input avec la durée courante (en minutes) si présent
        updateInputValue();

        // Détecte un "vrai" nouveau démarrage :
        // - le statut passe à running
        // - OU le start_time a changé (nouveau départ après un stop/start)
        const isNewStart = (state.status === 'running') &&
            (wasStatus !== 'running' || wasStartTime !== state.startTime);

        if (isNewStart) {
            // On vérifie si ce démarrage est réellement récent (évite de rejouer
            // les sons si on rejoint un timer déjà en cours depuis longtemps,
            // par exemple lors du premier chargement de la page)
            const elapsedSinceStart = state.startTime ? (Date.now() - state.startTime) / 1000 : Infinity;

            if (firstLoad) {
                // Au tout premier chargement, on ne joue jamais les sons de façon rétroactive.
                // On marque comme "déjà joués" tous les sons dont le seuil est déjà dépassé.
                const elapsed = getElapsedSecondsFor(state);
                const half = state.duration / 2;
                soundsPlayed.start = elapsed >= 0.5; // considéré comme déjà passé si > 0.5s
                soundsPlayed.middle = elapsed >= half;
                soundsPlayed.end = elapsed >= state.duration;
            } else if (elapsedSinceStart < 2) {
                // Nouveau départ réel et récent (déclenché par l'utilisateur) => reset complet
                soundsPlayed = { start: false, middle: false, end: false };
            } else {
                // Changement de statut détecté mais le départ n'est pas "récent"
                // (resynchronisation serveur) => on ne rejoue pas les sons déjà passés
                const elapsed = getElapsedSecondsFor(state);
                const half = state.duration / 2;
                soundsPlayed.start = elapsed >= 0.5;
                soundsPlayed.middle = elapsed >= half;
                soundsPlayed.end = elapsed >= state.duration;
            }
        }

        // Si le timer est stoppé, on réinitialise les sons pour le prochain départ
        if (state.status === 'stopped') {
            soundsPlayed = { start: false, middle: false, end: false };
        }

        firstLoad = false;

        updateUI();
    }

    // ---- Calcul du temps restant ----
    function getElapsedSecondsFor(s) {
        if (s.status === 'paused') {
            return s.pausedAt;
        }
        if (s.status === 'running' && s.startTime) {
            const now = Date.now();
            const elapsed = (now - s.startTime) / 1000;
            return Math.min(elapsed, s.duration);
        }
        return 0;
    }

    function getElapsedSeconds() {
        return getElapsedSecondsFor(state);
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

    function formatElapsed(totalSeconds) {
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = Math.floor(totalSeconds % 60);
        return `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }

    // ---- Boucle d'affichage (tick local à chaque frame/seconde) ----
    function tick() {
        const remaining = getRemainingSeconds();
        const half = state.duration / 2;

        // Mise à jour affichage
        const el = document.querySelector(`#${config.containerId} .timer-display`);
        if (el) {
            el.innerHTML = ''; // vider
            const line1 = document.createElement('div');
            const line2 = document.createElement('div');
            line1.textContent = formatTime(remaining);
            line2.textContent = formatElapsed(state.duration); // <-- ici, duration au lieu de elapsed
            // line2.style.fontSize = "50%" // <-- ici, duration au lieu de elapsed
            line2.classList.add("timerorigine");

            el.appendChild(line1);
            el.appendChild(line2);
            const couleur = getComputedStyle(line2.parentElement.parentElement).color;
            line2.style.color = couleur;
        }

        // ... reste du code (utilise toujours `elapsed` pour les sons, donc on le garde en variable séparée)
        const elapsed = getElapsedSeconds();

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
                    <input type="number" id="${config.containerId}-input" min="1" placeholder="Minutes" class="timer-input" step="0.5">
                    <button class="timer-btn timer-btn-start" data-action="start">Démarrer</button>
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
                apiCall('start', config.idtournoi, { duration: Math.round(minutes * 60) }).then(fetchTimerState);
            });

            // container.querySelector('[data-action="pause"]').addEventListener('click', () => {
            //     apiCall('pause').then(fetchTimerState);
            // });

            // container.querySelector('[data-action="resume"]').addEventListener('click', () => {
            //     apiCall('resume').then(fetchTimerState);
            // });

            container.querySelector('[data-action="stop"]').addEventListener('click', () => {
                if (confirm('Arrêter le timer ?')) {
                    apiCall('stop', config.idtournoi).then(fetchTimerState);
                }
            });

            container.querySelector(`#${config.containerId}-sound`).addEventListener('change', (e) => {
                const enabled = e.target.checked ? 1 : 0;
                apiCall('toggle_sound', config.idtournoi, { enabled }).then(fetchTimerState);
            });
        }
    }

    function updateInputValue() {
        if (!config.showControls) return;
        const input = document.getElementById(`${config.containerId}-input`);
        if (!input) return;

        // Ne pas écraser la saisie de l'utilisateur si le champ a le focus
        if (document.activeElement === input) return;

        // Ne remplit l'input que si une durée valide existe
        if (state.duration && state.duration > 0) {
            const minutes = state.duration / 60;
            // Affiche joliment (pas de .0 inutile)
            input.value = (minutes % 1 === 0) ? minutes : minutes.toFixed(1);
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

        const remaining = getRemainingSeconds();
        const isFinished = state.status === 'running' && remaining <= 0;

        // Le timer est considéré "actif" (affiche stop) seulement s'il tourne ET n'est pas fini
        const isActive = (state.status === 'running' && !isFinished) || state.status === 'paused';

        if (btnStart) btnStart.style.display = (state.status === 'stopped' || isFinished) ? 'inline-block' : 'none';
        if (btnPause) btnPause.style.display = state.status === 'running' && !isFinished ? 'inline-block' : 'none';
        if (btnResume) btnResume.style.display = state.status === 'paused' ? 'inline-block' : 'none';
        if (btnStop) btnStop.style.display = isActive ? 'inline-block' : 'none';
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
        displayInterval = setInterval(tick, 500); // rafraîchissement fluide
    }

    // ---- Initialisation publique ----
    function init(userConfig = {}) {
        config = { ...config, ...userConfig };
        firstLoad = true;
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