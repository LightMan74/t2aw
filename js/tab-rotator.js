class TabRotator {
    constructor(options = {}) {
        this.mainTabSelector = options.mainTabSelector || '.tab-btn';
        this.mainContentSelector = options.mainContentSelector || '.tab-content';
        this.subTabSelector = options.subTabSelector || '.sous-tab-btn';
        this.subContentSelector = options.subContentSelector || '.sous-onglet-content';
        this.activeClass = options.activeClass || 'active';

        this.mainInterval = options.mainInterval || 80000;
        this.subInterval = options.subInterval || 40000;

        this.pauseOnHover = options.pauseOnHover !== false;

        this.allMainTabs = [];
        this.mainTabsToRotate = [];
        this.currentTab = null;

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

        this.currentTab = this.allMainTabs.find(t => t.classList.contains(this.activeClass))
            || this.mainTabsToRotate[0];

        this.start();
    }

    /**
     * Met à jour la liste des onglets principaux qui doivent tourner.
     * Si l'onglet actuellement affiché n'est plus dans la liste,
     * on passe au suivant valide. Sinon le cycle en cours continue.
     * @param {string[]} dataTabValues - liste des data-tab values cochées
     */
    setMainRotationList(dataTabValues) {
        if (!dataTabValues || dataTabValues.length === 0) {
            this.mainTabsToRotate = [...this.allMainTabs];
        } else {
            this.mainTabsToRotate = this.allMainTabs.filter(tab =>
                dataTabValues.includes(tab.dataset.tab)
            );
        }

        if (!this.mainTabsToRotate.includes(this.currentTab)) {
            if (this.mainTabsToRotate.length > 0) {
                this.currentTab = this.mainTabsToRotate[0];
                this.start();
            } else {
                this.stop();
            }
        }
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

        const currentIndex = this.mainTabsToRotate.indexOf(this.currentTab);
        const nextIndex = currentIndex === -1
            ? 0
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


/**
 * Génère le menu de configuration des onglets (checkboxes masquable au survol).
 * @param {TabRotator} rotator - instance de TabRotator
 * @param {string} containerSelector - sélecteur du conteneur déjà présent dans le HTML
 */
function generateRotationConfig(rotator, containerSelector) {
    const container = document.querySelector(containerSelector);
    if (!container) return;

    const mainTabs = document.querySelectorAll('.tab-btn');

    container.innerHTML = '';

    mainTabs.forEach(tab => {
        const value = tab.dataset.tab;
        const label = tab.textContent.trim();

        const wrapper = document.createElement('label');
        wrapper.className = 'rotation-menu-item';

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.value = value;
        checkbox.checked = true;

        const text = document.createTextNode(' - ' + label);

        wrapper.appendChild(checkbox);
        wrapper.appendChild(text);
        container.appendChild(wrapper);
        container.appendChild(document.createElement('br'));

        checkbox.addEventListener('change', () => {
            const selected = Array.from(
                container.querySelectorAll('input:checked')
            ).map(cb => cb.value);

            rotator.setMainRotationList(selected);
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('.rotation-menu-container');
    const toggleBtn = document.querySelector('.rotation-menu-toggle');
    const panel = document.querySelector('.rotation-menu-panel');

    toggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        container.classList.toggle('open');
    });

    panel.addEventListener('click', (e) => {
        e.stopPropagation();
    });

    document.addEventListener('click', (e) => {
        if (!container.contains(e.target)) {
            container.classList.remove('open');
        }
    });
});