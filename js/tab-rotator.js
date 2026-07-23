class TabRotator {
    constructor(options = {}) {
        this.mainTabSelector = options.mainTabSelector || '.tab-btn';
        this.mainContentSelector = options.mainContentSelector || '.tab-content';
        this.subTabSelector = options.subTabSelector || '.sous-tab-btn';
        this.subContentSelector = options.subContentSelector || '.sous-onglet-content';
        this.activeClass = options.activeClass || 'active';

        this.mainInterval = options.mainInterval || 8000;
        this.subInterval = options.subInterval || 4000;

        this.pauseOnHover = options.pauseOnHover !== false;

        this.allMainTabs = [];
        this.mainTabsToRotate = [];
        this.currentTab = null; // <-- référence DOM, plus un index

        this.mainTimer = null;
        this.subTimer = null;
        this.isPaused = false;

        this.init();
    }

    init() {
        this.allMainTabs = Array.from(document.querySelectorAll(this.mainTabSelector));

        if (this.allMainTabs.length === 0) {
            console.warn('TabRotator: aucun onglet principal trouvé');
            return;
        }

        this.mainTabsToRotate = [...this.allMainTabs];

        if (this.pauseOnHover) {
            const container = document.querySelector('.tabs-container') || document.body;
            container.addEventListener('mouseenter', () => this.pause());
            container.addEventListener('mouseleave', () => this.resume());
        }

        this.allMainTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                this.currentTab = tab;
                this.resetMainTimer();
            });
        });

        // Onglet de départ = celui actuellement actif dans le DOM, ou le premier
        this.currentTab = this.allMainTabs.find(t => t.classList.contains(this.activeClass))
            || this.mainTabsToRotate[0];

        this.start();
    }

    setMainRotationList(dataTabValues) {
        if (!dataTabValues || dataTabValues.length === 0) {
            this.mainTabsToRotate = [...this.allMainTabs];
        } else {
            this.mainTabsToRotate = this.allMainTabs.filter(tab =>
                dataTabValues.includes(tab.dataset.tab)
            );
        }

        // Si l'onglet actuellement affiché a été désélectionné,
        // on continue le cycle avec le suivant valide plutôt que de tout reset
        if (!this.mainTabsToRotate.includes(this.currentTab)) {
            if (this.mainTabsToRotate.length > 0) {
                this.currentTab = this.mainTabsToRotate[0];
                this.start(); // on doit relancer car l'onglet actif n'existe plus dans la liste
            } else {
                this.stop(); // plus aucun onglet sélectionné
            }
        }
        // Sinon : on NE TOUCHE À RIEN, le cycle en cours continue normalement
        // et le prochain calcul d'index se basera sur la nouvelle liste
    }

    start() {
        this.stop();
        this.goToMainTab(this.currentTab);
    }

    stop() {
        clearTimeout(this.mainTimer);
        clearTimeout(this.subTimer);
    }

    goToMainTab(tab) {
        if (!tab) return;

        this.currentTab = tab;
        tab.click();

        setTimeout(() => {
            const activeMainContent = document.querySelector(
                this.mainContentSelector + '.' + this.activeClass
            );

            const subTabs = activeMainContent
                ? Array.from(activeMainContent.querySelectorAll(this.subTabSelector))
                : [];

            if (subTabs.length > 1) {
                this.runSubRotation(activeMainContent, subTabs, () => {
                    this.scheduleNextMainTab();
                });
            } else {
                this.mainTimer = setTimeout(() => this.scheduleNextMainTab(), this.mainInterval);
            }
        }, 100);
    }

    runSubRotation(mainContent, subTabs, onComplete) {
        let subIndex = 0;

        const showNextSub = () => {
            if (this.isPaused) {
                this.subTimer = setTimeout(showNextSub, 300);
                return;
            }

            if (subIndex >= subTabs.length) {
                onComplete();
                return;
            }

            subTabs[subIndex].click();
            subIndex++;

            this.subTimer = setTimeout(showNextSub, this.subInterval);
        };

        showNextSub();
    }

    scheduleNextMainTab() {
        if (this.mainTabsToRotate.length === 0) return;

        if (this.isPaused) {
            this.mainTimer = setTimeout(() => this.scheduleNextMainTab(), 300);
            return;
        }

        // Calcule le prochain onglet à partir de la liste ACTUELLE (toujours à jour)
        const currentIndex = this.mainTabsToRotate.indexOf(this.currentTab);
        const nextIndex = currentIndex === -1
            ? 0 // l'onglet courant n'est plus dans la liste -> repart du début
            : (currentIndex + 1) % this.mainTabsToRotate.length;

        this.goToMainTab(this.mainTabsToRotate[nextIndex]);
    }

    resetMainTimer() {
        this.start();
    }

    pause() {
        this.isPaused = true;
    }

    resume() {
        this.isPaused = false;
    }
}

// generate-rotation-config.js

function generateRotationConfig(rotator, containerSelector = '#config-rotation') {
    const container = document.querySelector(containerSelector);
    if (!container) return;

    const mainTabs = document.querySelectorAll('.tab-btn');

    container.innerHTML = ''; // reset

    mainTabs.forEach(tab => {
        const value = tab.dataset.tab;
        const label = tab.textContent.trim();

        const wrapper = document.createElement('label');
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.value = value;
        checkbox.checked = true; // coché par défaut

        wrapper.appendChild(checkbox);
        wrapper.appendChild(document.createTextNode(' ' + label));
        container.appendChild(wrapper);

        checkbox.addEventListener('change', () => {
            const selected = Array.from(
                container.querySelectorAll('input:checked')
            ).map(cb => cb.value);

            rotator.setMainRotationList(selected);
        });
    });
}