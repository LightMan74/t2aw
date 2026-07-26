const STORAGE_KEY = 'tabRotatorSettings';
class TabRotator {
    constructor(options = {}) {
        this.mainTabSelector = options.mainTabSelector || '.tab-btn';
        this.mainContentSelector = options.mainContentSelector || '.tab-content';
        this.subTabSelector = options.subTabSelector || '.sous-tab-btn';
        this.subContentSelector = options.subContentSelector || '.sous-onglet-content';
        this.activeClass = options.activeClass || 'active';
        this.scrollDisabled = options.scrollDisabled || false;

        this.mainInterval = options.mainInterval || 10000;
        this.subInterval = options.subInterval || 10000;

        // Paramètres du scroll automatique (valeurs par défaut AVANT _loadSettings)
        this.scrollSpeed = options.scrollSpeed || 30;
        this.scrollPauseAtStart = options.scrollPauseAtStart || 6000;
        this.scrollPauseAtEnd = options.scrollPauseAtEnd || 1000;

        this.pauseOnHover = options.pauseOnHover !== false;

        this.noScrollTabs = options.noScrollTabs || [];
        this.noScrollSelectors = options.noScrollSelectors || [];

        this.allMainTabs = [];
        this.mainTabsToRotate = [];
        this.currentTab = null;

        this.mainTimer = null;
        this.subTimer = null;
        this.isPaused = false;

        // Charge les réglages sauvegardés AVANT de créer le scroller
        this._loadSettings();

        this.scroller = new AutoScroller({
            scrollSpeed: this.scrollSpeed,
            scrollPauseAtStart: this.scrollPauseAtStart,
            scrollPauseAtEnd: this.scrollPauseAtEnd
        });

        this.init();
    }

    _loadSettings() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return;
            const saved = JSON.parse(raw);

            if (typeof saved.mainInterval === 'number') this.mainInterval = saved.mainInterval;
            if (typeof saved.subInterval === 'number') this.subInterval = saved.subInterval;
            if (typeof saved.scrollDisabled === 'boolean') this.scrollDisabled = saved.scrollDisabled;
            if (typeof saved.scrollSpeed === 'number') this.scrollSpeed = saved.scrollSpeed;
            if (typeof saved.scrollPauseAtStart === 'number') this.scrollPauseAtStart = saved.scrollPauseAtStart;
            if (typeof saved.scrollPauseAtEnd === 'number') this.scrollPauseAtEnd = saved.scrollPauseAtEnd;

            this._savedSelectedTabs = Array.isArray(saved.selectedTabs) ? saved.selectedTabs : null;
        } catch (e) {
            console.warn('TabRotator: impossible de charger les réglages', e);
        }
    }

    _saveSettings(selectedTabs) {
        const data = {
            mainInterval: this.mainInterval,
            subInterval: this.subInterval,
            scrollDisabled: this.scrollDisabled,
            scrollSpeed: this.scrollSpeed,
            scrollPauseAtStart: this.scrollPauseAtStart,
            scrollPauseAtEnd: this.scrollPauseAtEnd,
            selectedTabs: selectedTabs || this.mainTabsToRotate.map(t => t.dataset.tab)
        };
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        } catch (e) {
            console.warn('TabRotator: impossible de sauvegarder les réglages', e);
        }
    }

    init() {
        this.allMainTabs = Array.from(document.querySelectorAll(this.mainTabSelector));

        if (this.allMainTabs.length === 0) {
            console.warn('TabRotator: aucun onglet principal trouvé');
            return;
        }

        this.mainTabsToRotate = [...this.allMainTabs];

        if (this._savedSelectedTabs && this._savedSelectedTabs.length > 0) {
            this.mainTabsToRotate = this.allMainTabs.filter(tab =>
                this._savedSelectedTabs.includes(tab.dataset.tab)
            );
            if (this.mainTabsToRotate.length === 0) {
                this.mainTabsToRotate = [...this.allMainTabs];
            }
        }

        if (this.pauseOnHover) {
            const container = document.querySelector('.tabs-container') || document.body;
            // container.addEventListener('mouseenter', () => this.pause());
            // container.addEventListener('mouseleave', () => this.resume());
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

        this._saveSettings(dataTabValues);
    }

    start() {
        this.stop();
        this.goToMainTab(this.currentTab);
    }

    stop() {
        clearTimeout(this.mainTimer);
        clearTimeout(this.subTimer);
        this.scroller.stop();
    }

    _isNoScrollTab(tabEl) {
        if (!tabEl) return false;

        const value = tabEl.dataset.tab || tabEl.dataset.sousTab;
        if (value && this.noScrollTabs.includes(value)) {
            return true;
        }

        return this.noScrollSelectors.some(selector => {
            try {
                return tabEl.matches(selector);
            } catch (e) {
                return false;
            }
        });
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
                if (this.scrollDisabled || this._isNoScrollTab(tab) || this._isNoScrollTab(activeMainContent)) {
                    this.mainTimer = setTimeout(() => this.scheduleNextMainTab(), this.mainInterval);
                } else {
                    this.scroller.startFor(activeMainContent || document.body, () => {
                        this.mainTimer = setTimeout(() => this.scheduleNextMainTab(), this.mainInterval);
                    });
                }
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

            const currentSubTab = subTabs[subIndex];
            currentSubTab.click();
            subIndex++;

            const activeSubContent = document.querySelector(
                this.subContentSelector + '.' + this.activeClass
            );

            if (this.scrollDisabled || this._isNoScrollTab(currentSubTab) || this._isNoScrollTab(activeSubContent)) {
                this.subTimer = setTimeout(showNextSub, this.subInterval);
            } else {
                this.scroller.startFor(activeSubContent || mainContent, () => {
                    this.subTimer = setTimeout(showNextSub, this.subInterval);
                });
            }
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

    setMainInterval(ms) {
        this.mainInterval = ms;
        this._saveSettings();
    }

    setSubInterval(ms) {
        this.subInterval = ms;
        this._saveSettings();
    }

    setScrollDisabled(disabled) {
        this.scrollDisabled = disabled;
        if (disabled) {
            this.scroller.stop();
        }
        this._saveSettings();
    }

    // --- Setters pour les paramètres du scroll automatique ---
    setScrollPauseAtStart(ms) {
        this.scrollPauseAtStart = ms;
        if (this.scroller) {
            this.scroller.pauseAtStart = ms;
        }
        console.log('scrollPauseAtStart mis à jour:', ms);
        this._saveSettings();
    }

    setScrollPauseAtEnd(ms) {
        this.scrollPauseAtEnd = ms;
        if (this.scroller) {
            this.scroller.pauseAtEnd = ms;
        }
        console.log('scrollPauseAtEnd mis à jour:', ms);
        this._saveSettings();
    }

    setScrollSpeed(pxPerSec) {
        this.scrollSpeed = pxPerSec;
        if (this.scroller) {
            this.scroller.scrollSpeed = pxPerSec;
        }
        console.log('scrollSpeed mis à jour:', pxPerSec);
        this._saveSettings();
    }
}


/**
 * AutoScroller
 */
class AutoScroller {
    constructor(options = {}) {
        this.scrollSpeed = options.scrollSpeed || 40;
        this.pauseAtStart = options.scrollPauseAtStart || 500;
        this.pauseAtEnd = options.scrollPauseAtEnd || 2000;
        this.rafId = null;
        this.timeoutId = null;
        this.running = false;
    }

    _findScrollables(root) {
        const elements = root.querySelectorAll('*');
        return Array.from(elements).filter(el => {
            const style = window.getComputedStyle(el);
            const isScrollable = style.overflowY === 'auto' || style.overflowY === 'scroll';
            const hasContent = el.scrollHeight > el.clientHeight;
            return isScrollable && hasContent;
        });
    }

    startFor(root, onFinished) {
        this.stop();

        const scrollables = this._findScrollables(root);

        if (scrollables.length === 0) {
            if (onFinished) onFinished();
            return;
        }

        scrollables.forEach(el => { el.scrollTop = 0; });

        this.timeoutId = setTimeout(() => {
            this._scrollAll(scrollables, onFinished);
        }, this.pauseAtStart);
    }

    _scrollAll(scrollables, onFinished) {
        this.running = true;
        let index = 0;

        const scrollOne = () => {
            if (!this.running) return;

            if (index >= scrollables.length) {
                this.timeoutId = setTimeout(() => {
                    this.running = false;
                    if (onFinished) onFinished();
                }, this.pauseAtEnd);
                return;
            }

            this._scrollDown(scrollables[index], scrollOne);
            index++;
        };

        scrollOne();
    }

    _scrollDown(el, next) {
        const total = el.scrollHeight - el.clientHeight;
        if (total <= 0) {
            next();
            return;
        }

        let last = 0;
        const step = (timestamp) => {
            if (!this.running) return;

            if (!last) last = timestamp;
            const delta = (timestamp - last) / 1000;
            last = timestamp;

            el.scrollTop += this.scrollSpeed * delta;

            if (el.scrollTop >= total) {
                el.scrollTop = total;
                next();
            } else {
                this.rafId = requestAnimationFrame(step);
            }
        };

        this.rafId = requestAnimationFrame(step);
    }

    stop() {
        if (this.rafId) {
            cancelAnimationFrame(this.rafId);
            this.rafId = null;
        }
        if (this.timeoutId) {
            clearTimeout(this.timeoutId);
            this.timeoutId = null;
        }
        this.running = false;
    }
}


/**
 * Génère le menu de configuration
 */
function generateRotationConfig(rotator, containerSelector) {
    const container = document.querySelector(containerSelector);
    if (!container) return;

    const mainTabs = document.querySelectorAll('.tab-btn');

    container.innerHTML = '';

    const timerWrapper = document.createElement('div');
    timerWrapper.className = 'rotation-menu-timers';

    const mainLabel = document.createElement('label');
    mainLabel.textContent = 'Temps onglet principal (sec) : ';
    const mainInput = document.createElement('input');
    mainInput.type = 'number';
    mainInput.min = '1';
    mainInput.value = rotator.mainInterval / 1000;
    mainInput.className = 'rotation-menu-input';

    mainInput.addEventListener('change', () => {
        const seconds = parseFloat(mainInput.value);
        if (!isNaN(seconds) && seconds > 0) {
            rotator.setMainInterval(seconds * 1000);
        }
    });

    mainLabel.appendChild(mainInput);

    const subLabel = document.createElement('label');
    subLabel.textContent = 'Temps sous-onglet (sec) : ';
    const subInput = document.createElement('input');
    subInput.type = 'number';
    subInput.min = '1';
    subInput.value = rotator.subInterval / 1000;
    subInput.className = 'rotation-menu-input';

    subInput.addEventListener('change', () => {
        const seconds = parseFloat(subInput.value);
        if (!isNaN(seconds) && seconds > 0) {
            rotator.setSubInterval(seconds * 1000);
        }
    });

    subLabel.appendChild(subInput);

    // --- Temps de pause au début du scroll ---
    const subLabel1 = document.createElement('label');
    subLabel1.textContent = 'Temps debut de scroll (sec) : ';
    const subInput1 = document.createElement('input');
    subInput1.type = 'number';
    subInput1.min = '0';
    subInput1.step = '1.0';
    subInput1.value = rotator.scrollPauseAtStart / 1000;
    subInput1.className = 'rotation-menu-input';

    subInput1.addEventListener('change', () => {
        const seconds = parseFloat(subInput1.value);
        if (!isNaN(seconds) && seconds >= 0) {
            rotator.setScrollPauseAtStart(seconds * 1000);
        }
    });

    subLabel1.appendChild(subInput1);

    // --- Temps de pause à la fin du scroll ---
    const subLabel2 = document.createElement('label');
    subLabel2.textContent = 'Temps fin de scroll (sec) : ';
    const subInput2 = document.createElement('input');
    subInput2.type = 'number';
    subInput2.min = '0';
    subInput2.step = '1.0';
    subInput2.value = rotator.scrollPauseAtEnd / 1000;
    subInput2.className = 'rotation-menu-input';

    subInput2.addEventListener('change', () => {
        const seconds = parseFloat(subInput2.value);
        if (!isNaN(seconds) && seconds >= 0) {
            rotator.setScrollPauseAtEnd(seconds * 1000);
        }
    });

    subLabel2.appendChild(subInput2);

    // --- Vitesse de scroll (px/sec) ---
    const subLabel3 = document.createElement('label');
    subLabel3.textContent = 'Vitesse de scroll (px/sec) : ';
    const subInput3 = document.createElement('input');
    subInput3.type = 'number';
    subInput3.min = '1';
    subInput3.value = rotator.scrollSpeed;
    subInput3.className = 'rotation-menu-input';

    subInput3.addEventListener('change', () => {
        const speed = parseFloat(subInput3.value);
        if (!isNaN(speed) && speed > 0) {
            rotator.setScrollSpeed(speed);
        }
    });

    subLabel3.appendChild(subInput3);

    timerWrapper.appendChild(mainLabel);
    timerWrapper.appendChild(document.createElement('br'));
    timerWrapper.appendChild(subLabel);
    timerWrapper.appendChild(document.createElement('br'));
    timerWrapper.appendChild(subLabel1);
    timerWrapper.appendChild(document.createElement('br'));
    timerWrapper.appendChild(subLabel2);
    timerWrapper.appendChild(document.createElement('br'));
    timerWrapper.appendChild(subLabel3);

    container.appendChild(timerWrapper);

    // --- Checkbox pour désactiver le scroll auto ---
    const scrollWrapper = document.createElement('label');
    scrollWrapper.className = 'rotation-menu-item';

    const scrollCheckbox = document.createElement('input');
    scrollCheckbox.type = 'checkbox';
    scrollCheckbox.checked = rotator.scrollDisabled;

    const scrollText = document.createTextNode(' - Bloquer le scroll auto');

    scrollWrapper.appendChild(scrollCheckbox);
    scrollWrapper.appendChild(scrollText);
    container.appendChild(scrollWrapper);
    container.appendChild(document.createElement('br'));
    container.appendChild(document.createElement('hr'));
    container.appendChild(document.createElement('br'));

    scrollCheckbox.addEventListener('change', () => {
        rotator.setScrollDisabled(scrollCheckbox.checked);
    });

    // --- Liste des onglets ---
    mainTabs.forEach(tab => {
        const value = tab.dataset.tab;
        const label = tab.textContent.trim();

        const wrapper = document.createElement('label');
        wrapper.className = 'rotation-menu-item';

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.value = value;
        checkbox.checked = rotator.mainTabsToRotate.some(t => t.dataset.tab === value);

        const text = document.createTextNode(' - ' + label);

        wrapper.appendChild(checkbox);
        wrapper.appendChild(text);
        container.appendChild(wrapper);
        container.appendChild(document.createElement('br'));

        checkbox.addEventListener('change', () => {
            const selected = Array.from(
                container.querySelectorAll('.rotation-menu-item input[type="checkbox"][value]')
            ).filter(cb => cb.checked).map(cb => cb.value);

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