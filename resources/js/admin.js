// Point d'entrée de l'espace d'administration : Livewire 3 fournit Alpine.
import './bootstrap';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';
import Sortable from 'sortablejs';
import Chart from 'chart.js/auto';
import { enregistrerOrganigramme } from './organigramme-composant';

window.Sortable = Sortable;

const TYPES_PHOTO = ['image/jpeg', 'image/png', 'image/webp'];
const TAILLE_MAX_PHOTO = 2 * 1024 * 1024;

/**
 * Éditeur de photo : glisser-déposer ou « Parcourir », aperçu et recadrage carré (Cropper.js),
 * envoi de l'image recadrée, suppression pour revenir à l'avatar par défaut.
 */
Alpine.data('editeurPhoto', ({ urlEnvoi, urlSuppression, photo, avatarDefaut, aPhoto }) => ({
    photo,
    aPhoto,
    cropper: null,
    apercu: null,
    survol: false,
    envoi: false,
    erreur: null,

    choisir(fichiers) {
        this.erreur = null;
        const fichier = fichiers?.[0];
        if (!fichier) return;
        if (!TYPES_PHOTO.includes(fichier.type)) { this.erreur = 'La photo doit être au format JPG, PNG ou WEBP.'; return; }
        if (fichier.size > TAILLE_MAX_PHOTO) { this.erreur = 'La photo ne doit pas dépasser 2 Mo.'; return; }

        const lecteur = new FileReader();
        lecteur.onload = (e) => {
            this.apercu = e.target.result;
            this.$nextTick(() => {
                this.cropper?.destroy();
                this.cropper = new Cropper(this.$refs.image, {
                    aspectRatio: 1, viewMode: 1, autoCropArea: 1, dragMode: 'move', background: false,
                });
            });
        };
        lecteur.readAsDataURL(fichier);
    },

    annuler() {
        this.cropper?.destroy();
        this.cropper = null;
        this.apercu = null;
        if (this.$refs.fichier) this.$refs.fichier.value = '';
    },

    async enregistrer() {
        if (!this.cropper) return;
        this.envoi = true;
        this.erreur = null;
        const blob = await new Promise((ok) => this.cropper
            .getCroppedCanvas({ width: 768, height: 768, imageSmoothingQuality: 'high' })
            .toBlob(ok, 'image/jpeg', 0.92));
        const donnees = new FormData();
        donnees.append('photo', blob, 'photo.jpg');
        try {
            const { data } = await window.axios.post(urlEnvoi, donnees, { headers: { Accept: 'application/json' } });
            this.photo = data.photo + '?v=' + Date.now();
            this.aPhoto = true;
            this.annuler();
            this.$dispatch('notification', { message: data.message });
            this.$dispatch('photo-modifiee', { photo: data.photo });
        } catch (e) {
            this.erreur = e.response?.data?.errors?.photo?.[0] ?? e.response?.data?.message ?? "L'envoi a échoué.";
        } finally {
            this.envoi = false;
        }
    },

    async supprimer() {
        if (!confirm('Supprimer la photo et revenir à l\'avatar par défaut ?')) return;
        this.envoi = true;
        try {
            const { data } = await window.axios.delete(urlSuppression, { headers: { Accept: 'application/json' } });
            this.photo = avatarDefaut;
            this.aPhoto = false;
            this.$dispatch('notification', { message: data.message });
            this.$dispatch('photo-modifiee', { photo: data.photo });
        } catch (e) {
            this.erreur = e.response?.data?.message ?? 'La suppression a échoué.';
        } finally {
            this.envoi = false;
        }
    },
}));

/** Affectation des agents à un projet : sélection multiple avec recherche et choix du rôle. */
/**
 * Sélecteur de plusieurs fichiers (formulaire du projet) : chaque choix ou dépôt
 * s'ajoute à la liste au lieu de la remplacer, chaque fichier peut être retiré,
 * formats, tailles et nombre sont vérifiés avant l'envoi.
 */
Alpine.data('selecteurFichiers', ({ extensions, tailleMax, tailleMaxLisible, nombreMax }) => ({
    fichiers: [],
    erreurs: [],
    survol: false,

    ajouter(liste) {
        this.erreurs = [];
        for (const f of Array.from(liste)) {
            const ext = f.name.split('.').pop().toLowerCase();
            if (! extensions.includes(ext)) {
                this.erreurs.push(`« ${f.name} » : format non accepté.`);
            } else if (f.size > tailleMax) {
                this.erreurs.push(`« ${f.name} » dépasse ${tailleMaxLisible}.`);
            } else if (this.fichiers.length >= nombreMax) {
                this.erreurs.push(`${nombreMax} fichiers maximum : « ${f.name} » n'a pas été ajouté.`);
            } else if (! this.fichiers.some((g) => g.name === f.name && g.size === f.size)) {
                this.fichiers.push(f);
            }
        }
        this.synchroniser();
    },

    retirer(i) {
        this.fichiers.splice(i, 1);
        this.synchroniser();
    },

    // Recopie la liste dans le champ natif : c'est lui qui part avec le formulaire.
    synchroniser() {
        const dt = new DataTransfer();
        this.fichiers.forEach((f) => dt.items.add(f));
        this.$refs.champ.files = dt.files;
    },

    taille(octets) {
        return octets < 1024 * 1024
            ? Math.max(1, Math.round(octets / 1024)) + ' Ko'
            : (octets / 1024 / 1024).toFixed(1).replace('.', ',') + ' Mo';
    },
}));

Alpine.data('affectation', ({ agents, selection }) => ({
    agents,
    selection,
    recherche: '',
    ouvert: false,

    get resultats() {
        const t = this.normaliser(this.recherche);
        const pris = new Set(this.selection.map((s) => s.agent_id));
        return this.agents
            .filter((a) => !pris.has(a.id) && (t === '' || this.normaliser(`${a.nom} ${a.poste} ${a.structure}`).includes(t)))
            .slice(0, 12);
    },
    agent(id) { return this.agents.find((a) => a.id === id) ?? { nom: '?', poste: '', structure: '' }; },
    ajouter(a) {
        this.selection.push({ agent_id: a.id, role: this.selection.some((s) => s.role === 'responsable') ? 'membre' : 'responsable' });
        this.recherche = '';
    },
    retirer(i) { this.selection.splice(i, 1); },
    normaliser: (t) => (t ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim(),
}));

/** Fenêtre de modification rapide d'un nœud de l'organigramme (photo et poste). */
Alpine.data('editionNoeud', ({ urlPhoto, urlPoste, avatarDefaut }) => ({
    urlPhoto,
    urlPoste,
    avatarDefaut,
    ouvert: false,
    agent: null,
    poste: '',
    envoi: false,
    erreur: null,
    ouvrir(detail) {
        this.agent = detail;
        this.poste = detail.poste ?? '';
        this.erreur = null;
        this.ouvert = true;
    },
    url(modele) { return modele.replace('__ID__', this.agent.agentId); },
    async enregistrerPoste() {
        this.envoi = true;
        this.erreur = null;
        try {
            const { data } = await window.axios.patch(this.url(urlPoste), { poste: this.poste }, { headers: { Accept: 'application/json' } });
            this.$dispatch('notification', { message: data.message });
            window.dispatchEvent(new CustomEvent('organigramme-recharger'));
            this.ouvert = false;
        } catch (e) {
            this.erreur = e.response?.data?.errors?.poste?.[0] ?? e.response?.data?.message ?? "L'enregistrement a échoué.";
        } finally {
            this.envoi = false;
        }
    },
}));

/** Messages de confirmation (toasts). */
Alpine.data('notifications', () => ({
    liste: [],
    ajouter(message) {
        if (!message) return;
        const id = Date.now() + Math.random();
        this.liste.push({ id, message });
        setTimeout(() => { this.liste = this.liste.filter((n) => n.id !== id); }, 3500);
    },
}));

/** Graphique comparant les divisions : taux d'exécution moyen et nombre de projets. */
Alpine.data('graphiqueDivisions', ({ libelles, taux, nombres, couleurs }) => ({
    init() {
        new Chart(this.$refs.canvas, {
            type: 'bar',
            data: {
                labels: libelles,
                datasets: [
                    { label: "Taux d'exécution moyen (%)", data: taux, backgroundColor: couleurs, borderRadius: 6, yAxisID: 'y' },
                    { label: 'Nombre de projets', data: nombres, type: 'line', borderColor: '#374151', backgroundColor: '#374151', pointRadius: 5, yAxisID: 'y1' },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: (c) => `${c.dataset.label} : ${c.formattedValue}` } } },
                scales: {
                    y: { min: 0, max: 100, ticks: { callback: (v) => v + ' %' }, title: { display: true, text: 'Taux' } },
                    y1: { position: 'right', min: 0, ticks: { precision: 0 }, grid: { drawOnChartArea: false }, title: { display: true, text: 'Projets' } },
                },
            },
        });
    },
}));

enregistrerOrganigramme(Alpine);

Livewire.start();
