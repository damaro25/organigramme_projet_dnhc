// Point d'entrée de la partie publique.
import './bootstrap';
import Alpine from 'alpinejs';
import { enregistrerOrganigramme } from './organigramme-composant';

enregistrerOrganigramme(Alpine);
window.Alpine = Alpine;
Alpine.start();
