// assets/controllers/sidebar_controller.js
import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus Controller : Sidebar
 * - Ferme automatiquement la sidebar mobile après un clic sur un lien.
 * - Gère l'overlay (backdrop) de fermeture.
 */
export default class extends Controller {

    connect() {
        // On surveille le bouton hamburger de la topbar pour afficher l'overlay
        const toggleBtn = document.querySelector('[data-bs-target=".sidebar"]');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                setTimeout(() => {
                    this.#syncBackdrop();
                }, 50); // Petite attente pour laisser Bootstrap ouvrir la sidebar
            });
        }
    }

    /**
     * Appelé par data-action="click->sidebar#closeOnMobile" sur chaque lien.
     * Ferme la sidebar uniquement sur mobile (quand elle est en mode collapse).
     */
    closeOnMobile() {
        if (window.innerWidth < 768) {
            this.close();
        }
    }

    /**
     * Ferme la sidebar (utilisé aussi par le backdrop).
     */
    close() {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar && sidebar.classList.contains('show')) {
            // On utilise l'API Bootstrap pour fermer proprement
            const bsCollapse = window.bootstrap?.Collapse.getInstance(sidebar);
            if (bsCollapse) {
                bsCollapse.hide();
            } else {
                sidebar.classList.remove('show');
            }
        }
        this.#hideBackdrop();
    }

    /**
     * Synchronise la visibilité du backdrop avec l'état de la sidebar.
     */
    #syncBackdrop() {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar && sidebar.classList.contains('show')) {
            this.#showBackdrop();
        } else {
            this.#hideBackdrop();
        }
    }

    #showBackdrop() {
        const backdrop = document.getElementById('sidebarBackdrop');
        if (backdrop) backdrop.classList.add('show');
    }

    #hideBackdrop() {
        const backdrop = document.getElementById('sidebarBackdrop');
        if (backdrop) backdrop.classList.remove('show');
    }
}
