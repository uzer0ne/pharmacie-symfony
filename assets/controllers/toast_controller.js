import { Controller } from '@hotwired/stimulus';
import { Toast } from 'bootstrap';

/*
 * Contrôleur Stimulus pour afficher automatiquement les Toasts Bootstrap
 * et les masquer au bout de 3 secondes.
 */
export default class extends Controller {
    connect() {
        // Initialisation du Toast Bootstrap
        this.toast = new Toast(this.element, {
            autohide: true,
            delay: 3000 // 3 secondes
        });
        
        // Affichage immédiat
        this.toast.show();
    }
}
