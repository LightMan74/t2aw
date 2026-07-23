/**
 * phase_final_matchs.js — Liste et gestion de TOUS les matchs de phase finale
 * (toutes phases confondues) pour un tournoi donné.
 * Basé sur le même format/logique que matchs.js, adapté à la table matchs_phase_finale.
 */

let matchsPFData = [];
const modifiedMatchsPF = new Set();

function afficherMessageListePF(texte, type) {
    const div = document.getElementById('message-liste-matchs-pf');
    if (!div) return;
    div.innerHTML = `<div class="msg ${type}">${texte}</div>`;
    setTimeout(() => { div.innerHTML = ''; }, 5000);
}

async function chargerMatchsPF() {
    const id_tournoi = document.getElementById('input-tournoi-id')?.value;
    if (!id_tournoi) return;

    try {
        const res = await fetch(`api/phase_finale/get_matchs_all.php?id_tournoi=${id_tournoi}`);
        const data = await res.json();

        if (data.success) {
            matchsPFData = data.matchs;
            modifiedMatchsPF.clear();
            afficherTablePF();
        } else {
            afficherMessageListePF(data.message || data.error || 'Erreur', 'error');
        }
    } catch (err) {
        afficherMessageListePF('Erreur réseau : ' + err.message, 'error');
    }
    togglematchtermine();
}

const STATUS_CYCLE_PF = ['planifie', 'en_cours', 'termine'];
const STATUS_LABELS_PF = {
    planifie: 'Planifié',
    en_cours: 'En jeu',
    termine: 'Terminé'
};

function marquerModifiePF(index) {
    modifiedMatchsPF.add(index);
    majIconeSavePF(index);
}

function majIconeSavePF(index) {
    const icone = document.getElementById(`save-icon-pf-${index}`);
    if (!icone) return;
    if (modifiedMatchsPF.has(index)) {
        icone.textContent = '💾';
        icone.classList.add('non-sauvegarde');
        icone.title = 'Modifié — non sauvegardé';
    } else {
        icone.textContent = '';
        icone.classList.remove('non-sauvegarde');
        icone.title = 'Aucune modification en attente';
    }
}

function afficherTablePF() {
    matchcount = 0;
    const corps = document.getElementById('corps-table-pf');
    if (!corps) { console.error('Élément #corps-table-pf introuvable'); return; }
    corps.innerHTML = '';


    const tr2 = document.createElement('tr');
    tr2.innerHTML = `<td colspan="9" id="numbermatchshidden"></td>`;
    corps.appendChild(tr2);

    matchsPFData.forEach((m, index) => {
        const tr = document.createElement('tr');
        const catClass = (typeof getCategorieColorClassById === 'function')
            ? getCategorieColorClassById(m.id_categorie)
            : '';
        tr.className = 'status-' + (m.statut_match ?? '');
        tr.id = `row-pf-${index}`;

        const scoresEquipe1 = String(m.score1 ?? '0*0*0').split('*');
        const scoresEquipe2 = String(m.score2 ?? '0*0*0').split('*');
        const s1set1 = scoresEquipe1[0] ?? 0;
        const s1set2 = scoresEquipe1[1] ?? 0;
        const s1set3 = scoresEquipe1[2] ?? 0;
        const s2set1 = scoresEquipe2[0] ?? 0;
        const s2set2 = scoresEquipe2[1] ?? 0;
        const s2set3 = scoresEquipe2[2] ?? 0;

        const hiddenSets = (typeof tournoi_troissets_match !== 'undefined' && tournoi_troissets_match > 1) ? '' : 'hidden';

        const statusActuel = m.statut_match ?? 'planifie';
        const nom1 = m.nom_equipe1 ?? (m.source_team1 ?? '???');
        const nom2 = m.nom_equipe2 ?? (m.source_team2 ?? '???');

        // --- Calcul de la plage de classement (uniquement pour les phases "classement_complet") ---
        let ligneClassement = '';
        if (m.type_bracket === 'classement_complet' && m.nb_equipes_arrondi) {
            const roundIndexReel = m.round; // round tel que stocké en base (0 = premier tour)
            const skNum = Number(m.sub_group);
            const range = calculerPlageClassement(roundIndexReel, skNum, Number(m.nb_equipes_arrondi));
            ligneClassement = `${range}`;
        }

        tr.innerHTML = ` 
            <td class="categorie-badge ${catClass}">${m.nom_categorie ?? ''}</td>
            <td>${++matchcount ?? ''}</td>
            <td>
            <span style="margin: auto;" class="tour-label">Classement ${ligneClassement}</span><br>
            <span style="margin: auto;">${getLibelleTourPF(m.round, m.round_max_phase)}</span></br>
            <span style="margin: auto;" class="tour-label">(${m.match_code ?? ''})</span></td>
            <td>${nom1}</td>
            <td>
            <span style="display: block ruby;">
            <input type="number" min="0" value="${s1set1}" id="scorepf1s1-${index}"> - <input type="number" min="0" value="${s2set1}" id="scorepf2s1-${index}"><span ${hiddenSets}>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
            <input  type="number" min="0" value="${s1set2}" id="scorepf1s2-${index}"> - <input type="number" min="0" value="${s2set2}" id="scorepf2s2-${index}">&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
            <input type="number" min="0" value="${s1set3}" id="scorepf1s3-${index}"> - <input type="number" min="0" value="${s2set3}" id="scorepf2s3-${index}"></span>
            </span>
            </td>
            <td>${nom2}</td>
            <td><input type="number" min="1" value="${m.terrain ?? ''}" id="terrain-pf-${index}"></td>
            <td>
                <span class="status-badge status-badge-${statusActuel}" 
                    id="status-badge-pf-${index}" 
                    onclick="cyclerStatusPF(${index})"
                    title="Cliquer pour changer rapidement le statut">
                    ${STATUS_LABELS_PF[statusActuel] ?? statusActuel}
                </span>
                <select id="status-pf-${index}" style="margin-left:5px;" hidden>
                    <option value="planifie" ${statusActuel === 'planifie' ? 'selected' : ''}>Planifié</option>
                    <option value="en_cours" ${statusActuel === 'en_cours' ? 'selected' : ''}>En_cours</option>
                    <option value="termine" ${statusActuel === 'termine' ? 'selected' : ''}>Terminé</option>
                </select>
            </td>
            <td>
                <input type="time" step="60" value="${(m.heure_debut ?? '').substring(0, 5)}" id="hdebut-pf-${index}">
                <button type="button" class="btn-now" id="btn-now-pf-${index}" title="Mettre à l'heure actuelle et décaler les matchs suivants sur ce terrain" onclick="mettreHeureActuellePFListe(${index})">⏱️</button>
            </td>
            <td>
                <span class="save-icon" id="save-icon-pf-${index}" title="Aucune modification en attente" onclick="sauvegarderLignePF(${index})"></span>
            </td>
        `;
        corps.appendChild(tr);

        const champsEditables = [
            `terrain-pf-${index}`, `status-pf-${index}`, `hdebut-pf-${index}`,
            `scorepf1s1-${index}`, `scorepf1s2-${index}`, `scorepf1s3-${index}`,
            `scorepf2s1-${index}`, `scorepf2s2-${index}`, `scorepf2s3-${index}`,
        ];

        champsEditables.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', () => marquerModifiePF(index));
            el.addEventListener('change', () => {
                marquerModifiePF(index);
                if (id === `status-pf-${index}`) {
                    majBadgeStatusPF(index, el.value);
                }
            });
        });
    });
}

function majBadgeStatusPF(index, nouveauStatus) {
    const badge = document.getElementById(`status-badge-pf-${index}`);
    const select = document.getElementById(`status-pf-${index}`);
    const tr = document.getElementById(`row-pf-${index}`);

    if (badge) {
        badge.textContent = STATUS_LABELS_PF[nouveauStatus] ?? nouveauStatus;
        badge.className = `status-badge status-badge-${nouveauStatus}`;
    }
    if (select) {
        select.value = nouveauStatus;
    }
    if (tr) {
        tr.className = 'status-' + nouveauStatus;
    }
}

function cyclerStatusPF(index) {
    const select = document.getElementById(`status-pf-${index}`);
    if (!select) return;
    const actuel = select.value;
    const idxCycle = STATUS_CYCLE_PF.indexOf(actuel);
    const suivant = STATUS_CYCLE_PF[(idxCycle + 1) % STATUS_CYCLE_PF.length];

    majBadgeStatusPF(index, suivant);
    marquerModifiePF(index);
}

function construireScoresDepuisInputsPF(index) {
    const score1 = [
        document.getElementById(`scorepf1s1-${index}`)?.value ?? 0,
        document.getElementById(`scorepf1s2-${index}`)?.value ?? 0,
        document.getElementById(`scorepf1s3-${index}`)?.value ?? 0,
    ].join('*');

    const score2 = [
        document.getElementById(`scorepf2s1-${index}`)?.value ?? 0,
        document.getElementById(`scorepf2s2-${index}`)?.value ?? 0,
        document.getElementById(`scorepf2s3-${index}`)?.value ?? 0,
    ].join('*');

    return { score1, score2 };
}

async function sauvegarderLignePF(index) {
    if (!modifiedMatchsPF.has(index)) {
        afficherMessageListePF('Aucune modification pour ce match', 'success');
        return;
    }

    const m = matchsPFData[index];
    if (!m) return;

    const terrain = document.getElementById(`terrain-pf-${index}`)?.value ?? '';
    const statut_match = document.getElementById(`status-pf-${index}`)?.value ?? m.statut_match;
    const heure_debut = document.getElementById(`hdebut-pf-${index}`)?.value ?? '';
    const { score1, score2 } = construireScoresDepuisInputsPF(index);

    try {
        const res = await fetch('api/phase_finale/saisir_score.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                match_id: m.id,
                score1,
                score2,
                statut_match,
                terrain: terrain || null,
                heure_debut: heure_debut || null,
            }),
        });

        const data = await res.json();

        if (data.success) {
            modifiedMatchsPF.delete(index);
            majIconeSavePF(index);
            afficherMessageListePF('Match sauvegardé ✓', 'success');
            await chargerMatchsPF();
            await togglematchtermine();
        } else {
            afficherMessageListePF(data.message || data.error || 'Erreur de sauvegarde', 'error');
        }
    } catch (err) {
        afficherMessageListePF('Erreur réseau : ' + err.message, 'error');
    }
}

async function autosaveandreloadListePF() {
    if (modifiedMatchsPF.size === 0) {
        afficherMessageListePF('Aucune modification à sauvegarder', 'success');
        return;
    }

    let nbOk = 0;
    const indexesASauver = [...modifiedMatchsPF];

    for (const index of indexesASauver) {
        const m = matchsPFData[index];
        if (!m) continue;

        const terrain = document.getElementById(`terrain-pf-${index}`)?.value ?? '';
        const statut_match = document.getElementById(`status-pf-${index}`)?.value ?? m.statut_match;
        const heure_debut = document.getElementById(`hdebut-pf-${index}`)?.value ?? '';
        const { score1, score2 } = construireScoresDepuisInputsPF(index);

        try {
            const res = await fetch('api/phase_finale/saisir_score.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    match_id: m.id,
                    score1,
                    score2,
                    statut_match,
                    terrain: terrain || null,
                    heure_debut: heure_debut || null,
                }),
            });

            const data = await res.json();
            if (data.success) {
                modifiedMatchsPF.delete(index);
                nbOk++;
            }
        } catch (err) {
            console.error('Erreur sauvegarde match PF', index, err);
        }
    }

    afficherMessageListePF(`${nbOk} match(s) sauvegardé(s) ✓`, 'success');
    await chargerMatchsPF();
    await togglematchtermine();
}

// ---------- Gestion heure manuelle / décalage ----------

function formatHeureMinutePFListe(date) {
    const h = String(date.getHours()).padStart(2, '0');
    const m = String(date.getMinutes()).padStart(2, '0');
    return `${h}:${m}`;
}

function ajouterMinutesPFListe(heureStr, minutesAAjouter) {
    const [h, m] = heureStr.split(':').map(Number);
    const date = new Date();
    date.setHours(h, m, 0, 0);
    date.setMinutes(date.getMinutes() + minutesAAjouter);
    return formatHeureMinutePFListe(date);
}

function toggleHeureManuelleListePF() {
    const checkbox = document.getElementById('heureManuelleCheckboxListePF');
    const input = document.getElementById('heureManuelleInputListePF');
    if (!checkbox || !input) return;
    input.style.display = checkbox.checked ? 'inline-block' : 'none';

    if (checkbox.checked && !input.value) {
        input.value = formatHeureMinutePFListe(new Date());
    }
}

let tempsDeMatchPFListe = null;

async function chargerTempsDeMatchPFListe() {
    const id_tournoi = document.getElementById('input-tournoi-id')?.value;
    if (!id_tournoi) return;
    try {
        const res = await fetch(`api/get_parametres.php?id_tournoi=${id_tournoi}`);
        const data = await res.json();
        if (data.success && data.temps_de_match) {
            tempsDeMatchPFListe = parseInt(data.temps_de_match, 10) || 20;
        } else {
            tempsDeMatchPFListe = 20;
        }
    } catch (err) {
        tempsDeMatchPFListe = 20;
    }
}

async function mettreHeureActuellePFListe(index) {
    if (tempsDeMatchPFListe === null) {
        await chargerTempsDeMatchPFListe();
    }

    const m = matchsPFData[index];
    if (!m) return;

    const terrainInput = document.getElementById(`terrain-pf-${index}`);
    const terrain = terrainInput?.value ?? m.terrain;

    if (!terrain) {
        afficherMessageListePF('Aucun terrain défini pour ce match', 'error');
        return;
    }

    const statusActuelSelect = document.getElementById(`status-pf-${index}`);
    const statutActuel = statusActuelSelect?.value ?? m.statut_match;

    if (statutActuel === 'termine') {
        afficherMessageListePF('Ce match est déjà terminé, heure non modifiée', 'error');
        return;
    }

    const tousLesTerrains = document.getElementById('decalageTousTerrainsListePF')?.checked ?? false;

    const heureManuelleCheckbox = document.getElementById('heureManuelleCheckboxListePF');
    const heureManuelleInput = document.getElementById('heureManuelleInputListePF');

    let nouvelleHeureMatch;
    if (heureManuelleCheckbox?.checked && heureManuelleInput?.value) {
        nouvelleHeureMatch = heureManuelleInput.value;
    } else {
        nouvelleHeureMatch = formatHeureMinutePFListe(new Date());
    }

    const hdebutInput = document.getElementById(`hdebut-pf-${index}`);
    if (hdebutInput) {
        hdebutInput.value = nouvelleHeureMatch;
        marquerModifiePF(index);
    }

    let nbModifies = 0;

    const terrainsATraiter = tousLesTerrains
        ? [...new Set(matchsPFData.map((mm, idx) => document.getElementById(`terrain-pf-${idx}`)?.value ?? mm.terrain))]
        : [terrain];

    for (const terrainCourant of terrainsATraiter) {
        const matchsTerrain = matchsPFData
            .map((mm, idx) => ({ mm, idx }))
            .filter(({ mm, idx }) => {
                const t = document.getElementById(`terrain-pf-${idx}`)?.value ?? mm.terrain;
                return String(t) === String(terrainCourant);
            })
            .sort((a, b) => (a.idx - b.idx));

        const positionActuelle = matchsTerrain.findIndex(({ idx }) => idx === index);
        let heurePrecedente = nouvelleHeureMatch;

        const depart = String(terrainCourant) === String(terrain) ? positionActuelle + 1 : 0;

        for (let i = depart; i < matchsTerrain.length; i++) {
            const { mm, idx } = matchsTerrain[i];
            const statusSelect = document.getElementById(`status-pf-${idx}`);
            const statutCourant = statusSelect?.value ?? mm.statut_match;

            const hInput = document.getElementById(`hdebut-pf-${idx}`);

            if (statutCourant === 'en_cours' || statutCourant === 'termine') {
                if (hInput?.value) {
                    heurePrecedente = hInput.value;
                }
                continue;
            }

            const nouvelleHeure = ajouterMinutesPFListe(heurePrecedente, tempsDeMatchPFListe);

            if (hInput) {
                hInput.value = nouvelleHeure;
                marquerModifiePF(idx);
                nbModifies++;
            }

            heurePrecedente = nouvelleHeure;
        }
    }

    const portee = tousLesTerrains ? 'tous les terrains' : `le terrain ${terrain}`;
    afficherMessageListePF(`Heures mises à jour ✓ (${nbModifies} match(s) décalé(s) sur ${portee})`, 'success');
}

// ---------- Initialisation ----------

// Déduit le libellé du tour (1/8, Quart, Demi, Finale...) à partir du round
// et du round max de la phase finale concernée.
function getLibelleTourPF(round, roundMax) {
    const roundsRestants = roundMax - round; // 0 = dernier round (finale)

    switch (roundsRestants) {
        case 0: return 'Finale';
        case 1: return 'Demi';
        case 2: return 'Quart';
        case 3: return '1/8';
        case 4: return '1/16';
        case 5: return '1/32';
        case 6: return '1/64';
        case 7: return '1/128';
        default: return `Tour ${round}`;
    }
}

function calculerPlageClassement(round, subKey, nbreTeam) {
    const nbBranches = Math.pow(2, round);
    const tailleGroupe = nbreTeam / nbBranches;
    const index = subKey - 1;
    const debut = Math.floor(index * tailleGroupe) + 1;
    const fin = Math.floor((index + 1) * tailleGroupe);
    return debut + ' - ' + fin;
}


function togglematchtermine() {
    const lignesTerminees = document.querySelectorAll('tr.status-termine');
    const checkbox = document.getElementById('matchtermineCheckbox');
    let countmachshide = 0;
    if (checkbox.checked) {
        lignesTerminees.forEach(ligne => {
            ligne.style.display = 'none';
            countmachshide++;
        });
    } else {
        lignesTerminees.forEach(ligne => {
            ligne.style.display = '';
        });
    }
    console.log(countmachshide);
    document.getElementById('numbermatchshidden').textContent = countmachshide + " Matchs terminé caché.";
}

// document.addEventListener('DOMContentLoaded', () => {
//     chargerMatchsPF();
//     chargerTempsDeMatchPFListe();
//     // document.getElementById('matchtermineCheckbox').checked = true;
//     togglematchtermine();
// });

document.addEventListener('DOMContentLoaded', async () => {
    await chargerMatchsPF();
    await chargerTempsDeMatchPFListe(); // si elle aussi est async
    // document.getElementById('matchtermineCheckbox').checked = true;
    // togglematchtermine();
});