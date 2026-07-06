// js/generation.js

let matchsActuels = [];

function afficherMessage(texte, type) {
    const div = document.getElementById('message');
    div.innerHTML = `<div class="msg ${type}">${texte}</div>`;
    setTimeout(() => div.innerHTML = '', 5000);
}

function chargerMatchs() {
    const id_tournoi = document.getElementById('id_tournoi').value;

    const formData = new FormData();
    formData.append('id_tournoi', id_tournoi);

    fetch('api/generer_matchs.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                matchsActuels = data.matchs;
                afficherListeMatchs();
                afficherMessage('Ordre généré avec succès', 'success');
            } else {
                afficherMessage(data.error, 'error');
            }
        })
        .catch(err => afficherMessage('Erreur : ' + err, 'error'));
}

function afficherListeMatchs() {
    const container = document.getElementById('liste-matchs');
    container.innerHTML = '';

    matchsActuels.forEach((m, index) => {
        const div = document.createElement('div');
        div.className = 'match-item';
        div.draggable = true;
        div.dataset.index = index;

        div.innerHTML = `
            <span><strong>${m.nom_categorie}</strong> - ${m.nom_poule} - Match ${m.num_match_poule}</span>
            <span>${m.nom_equipe_1} vs ${m.nom_equipe_2}</span>
        `;

        div.addEventListener('dragstart', dragStart);
        div.addEventListener('dragover', dragOver);
        div.addEventListener('drop', drop);
        div.addEventListener('dragend', dragEnd);

        container.appendChild(div);
    });
}

let dragSrcIndex = null;

function dragStart(e) {
    dragSrcIndex = parseInt(this.dataset.index);
    this.classList.add('dragging');
}

function dragOver(e) {
    e.preventDefault();
}

function drop(e) {
    e.preventDefault();
    const targetIndex = parseInt(this.dataset.index);

    if (dragSrcIndex === null || dragSrcIndex === targetIndex) return;

    const item = matchsActuels[dragSrcIndex];
    matchsActuels.splice(dragSrcIndex, 1);
    matchsActuels.splice(targetIndex, 0, item);

    afficherListeMatchs();
}

function dragEnd() {
    this.classList.remove('dragging');
}

function validerOrdre() {
    const id_tournoi = document.getElementById('id_tournoi').value;

    fetch('api/sauvegarder_ordre.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id_tournoi: id_tournoi,
            matchs: matchsActuels
        })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                afficherMessage('Matchs enregistrés en base de données !', 'success');
            } else {
                afficherMessage(data.error, 'error');
            }
        })
        .catch(err => afficherMessage('Erreur : ' + err, 'error'));
}
