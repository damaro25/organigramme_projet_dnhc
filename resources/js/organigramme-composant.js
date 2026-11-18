import * as d3 from 'd3';
import { OrgChart } from 'd3-org-chart';

const echapper = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

const COULEURS_TAUX = { rouge: '#dc2626', orange: '#ea580c', vert: '#16a34a', gris: '#94a3b8' };

const LARGEUR = { agent: 230, structure: 230, racine: 760 };
const HAUTEUR = { agent: 196, structure: 110, racine: 230 };

function badge(d) {
    if (d.taux === null || d.taux === undefined) return '';
    return `<span class="oc-badge" style="background:${COULEURS_TAUX[d.couleurTaux]}" title="Taux d'exécution moyen de ${d.nbProjets} projet(s)">${d.taux}%</span>`;
}

function carteAgent(d, largeur) {
    return `
    <div class="oc-noeud" style="width:${largeur}px;height:${HAUTEUR.agent}px;border-top-color:${d.couleur}">
        ${badge(d)}
        ${d.admin ? `<button type="button" class="oc-edit js-edit" data-agent="${d.agentId}" title="Modifier la photo ou le poste">✏️</button>` : ''}
        <button type="button" class="oc-photo js-photo" data-agent="${d.agentId}" title="Voir la fiche et les projets">
            <img src="${echapper(d.photo)}" alt="Photo de ${echapper(d.nom)}" loading="lazy">
        </button>
        <div class="oc-nom">${echapper(d.nom)}</div>
        <div class="oc-poste">${echapper(d.poste)}</div>
        <div class="oc-structure" style="color:${d.couleur}">${echapper(d.sigle)}</div>
    </div>`;
}

function caseService(service, groupe) {
    const agents = service.agents.map((a) => `
        <button type="button" class="oc-service-agent js-photo" data-agent="${a.agentId}" title="${echapper(a.nom)} – ${echapper(a.poste)}">
            <img src="${echapper(a.photo)}" alt=""><span>${echapper(a.nom)}</span>
        </button>`).join('');
    return `<div class="oc-service oc-service--${groupe}" title="${echapper(service.nom)}">
        <div class="oc-service-titre">${echapper(service.sigle)}</div>${agents}</div>`;
}

function carteRacine(d) {
    const gauche = (d.cotes?.appui_technique ?? []).map((s) => caseService(s, 'clair')).join('');
    const droite = (d.cotes?.personnel_appui ?? []).map((s) => caseService(s, 'bleu')).join('');
    return `
    <div class="oc-racine" style="width:${LARGEUR.racine}px;height:${HAUTEUR.racine}px">
        <div class="oc-cote">
            <div class="oc-cote-titre">Services d'appui technique</div>${gauche}
        </div>
        <div class="oc-racine-centre">${carteAgent(d, LARGEUR.agent)}</div>
        <div class="oc-cote">
            <div class="oc-cote-titre">Personnel d'appui</div>${droite}
        </div>
    </div>`;
}

function carteStructure(d) {
    return `
    <div class="oc-noeud oc-noeud--structure" style="width:${LARGEUR.structure}px;height:${HAUTEUR.structure}px;border-color:${d.couleur}">
        <div class="oc-sigle">${echapper(d.sigle)}</div>
        <div class="oc-poste">${echapper(d.nom)}</div>
    </div>`;
}

const typeNoeud = (d) => (d.estRacine ? 'racine' : d.type);

export function enregistrerOrganigramme(Alpine) {
Alpine.data('organigramme', ({ urlDonnees, urlFiche, admin = false }) => ({
    chart: null,
    noeuds: [],
    chargement: true,
    erreur: null,
    recherche: '',
    suggestions: [],
    indexSuggestion: 0,
    panneau: { ouvert: false, chargement: false, donnees: null, onglet: 'agent' },

    async init() {
        try {
            const { data } = await window.axios.get(urlDonnees);
            this.noeuds = data.noeuds.map((n) => ({ ...n, admin: admin && !!n.admin, visibleInitial: n._expanded }));
            this.dessiner();
        } catch (e) {
            this.erreur = "Impossible de charger l'organigramme.";
        } finally {
            this.chargement = false;
        }

        // Clic sur une photo : ouvre le panneau sans déplier ni replier le nœud.
        this.$refs.arbre.addEventListener('click', (ev) => {
            const edit = ev.target.closest('.js-edit');
            const photo = ev.target.closest('.js-photo');
            if (edit) {
                ev.stopPropagation();
                this.$dispatch('editer-agent', this.trouverAgent(Number(edit.dataset.agent)));
            } else if (photo) {
                ev.stopPropagation();
                this.ouvrirFiche(Number(photo.dataset.agent));
            }
        }, true);

        window.addEventListener('organigramme-recharger', () => this.recharger());
    },

    dessiner() {
        this.chart = new OrgChart()
            .container(this.$refs.arbre)
            .data(this.noeuds)
            .compact(false)
            .nodeWidth((n) => LARGEUR[typeNoeud(n.data)])
            .nodeHeight((n) => HAUTEUR[typeNoeud(n.data)])
            .childrenMargin(() => 70)
            .siblingsMargin(() => 24)
            .neighbourMargin(() => 40)
            .onNodeClick((n) => {
                if (n.children || n._children) this.chart.onButtonClick({ stopPropagation() {} }, n);
            })
            .buttonContent(({ node }) => `<div class="oc-bouton">${node.children ? '−' : '+'} <span>${node.data._directSubordinates}</span></div>`)
            .nodeContent((n) => {
                const d = n.data;
                if (d.estRacine) return carteRacine(d);
                return d.type === 'structure' ? carteStructure(d) : carteAgent(d, LARGEUR.agent);
            })
            .nodeUpdate(function (n) {
                d3.select(this).select('.node-rect')
                    .attr('stroke', n.data._highlighted ? '#f59e0b' : 'none')
                    .attr('stroke-width', n.data._highlighted ? 8 : 0)
                    .attr('rx', 14);
            })
            .linkUpdate(function (n) {
                const fonctionnel = n.data.lien === 'fonctionnel';
                d3.select(this)
                    .attr('stroke', n.data._upToTheRootHighlighted ? '#f59e0b' : '#64748b')
                    .attr('stroke-width', n.data._upToTheRootHighlighted ? 3 : 1.5)
                    .attr('stroke-dasharray', fonctionnel ? '8,6' : null);
            })
            .render()
            .fit();
    },

    async recharger() {
        const { data } = await window.axios.get(urlDonnees);
        this.noeuds = data.noeuds.map((n) => ({ ...n, admin: admin && !!n.admin, visibleInitial: n._expanded }));
        this.chart.data(this.noeuds).render();
    },

    trouverAgent(agentId) {
        const n = this.noeuds.find((x) => x.agentId === agentId);
        return n ? { agentId, nom: n.nom, poste: n.poste, photo: n.photo, droits: n.droits ?? {} } : { agentId, droits: {} };
    },

    /* ---------- Barre d'outils ---------- */
    zoomer(sens) { sens > 0 ? this.chart.zoomIn() : this.chart.zoomOut(); },
    ajuster() { this.chart.fit(); },
    toutDeplier() { this.chart.expandAll().fit(); },
    toutReplier() {
        // Retour à l'état initial : replié au niveau des sections.
        this.chart.clearHighlighting();
        const donnees = this.chart.data();
        donnees.forEach((n) => { n._expanded = n.visibleInitial; });
        this.chart.data(donnees).render().fit();
    },

    /* ---------- Recherche ---------- */
    chercher() {
        const terme = this.normaliser(this.recherche);
        this.indexSuggestion = 0;
        if (terme.length < 2) { this.suggestions = []; return; }

        const agents = [];
        this.noeuds.forEach((n) => {
            if (n.type === 'agent') agents.push({ agentId: n.agentId, noeudId: n.id, nom: n.nom, poste: n.poste, sigle: n.sigle, photo: n.photo });
            if (n.estRacine) {
                Object.values(n.cotes ?? {}).flat().forEach((s) => s.agents.forEach((a) =>
                    agents.push({ agentId: a.agentId, noeudId: n.id, nom: a.nom, poste: a.poste, sigle: s.sigle, photo: a.photo })));
            }
        });
        this.suggestions = agents
            .filter((a) => this.normaliser(`${a.nom} ${a.poste} ${a.sigle}`).includes(terme))
            .slice(0, 8);
    },

    choisir(s) {
        if (!s) return;
        this.recherche = s.nom;
        this.suggestions = [];
        this.chart.clearHighlighting();
        this.chart.setExpanded(s.noeudId).setCentered(s.noeudId).setHighlighted(s.noeudId).render();
        this.ouvrirFiche(s.agentId);
    },

    normaliser: (t) => (t ?? '').toString().normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim(),

    /* ---------- Panneau latéral ---------- */
    async ouvrirFiche(agentId) {
        this.panneau = { ouvert: true, chargement: true, donnees: null, onglet: 'agent' };
        try {
            const { data } = await window.axios.get(urlFiche.replace('__ID__', agentId));
            this.panneau.donnees = data;
        } catch (e) {
            this.panneau.donnees = { erreur: 'Fiche indisponible.' };
        } finally {
            this.panneau.chargement = false;
        }
    },

    fermerFiche() { this.panneau.ouvert = false; },

    couleurBarre: (c) => ({ rouge: 'bg-red-600', orange: 'bg-orange-500', vert: 'bg-green-600' }[c] ?? 'bg-slate-400'),
    couleurTexte: (c) => ({ rouge: 'text-red-700', orange: 'text-orange-600', vert: 'text-green-700' }[c] ?? 'text-slate-500'),
    classeStatut: (s) => ({ non_demarre: 'bg-slate-100 text-slate-700', en_cours: 'bg-blue-100 text-blue-800', termine: 'bg-green-100 text-green-800', suspendu: 'bg-amber-100 text-amber-800' }[s] ?? ''),
}));
}
