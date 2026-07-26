import { Controller } from '@hotwired/stimulus';

/*
 * Contrôleur Stimulus pour intercepter les suppressions et afficher
 * une belle modale Bootstrap au lieu d'un alert() système laid.
 */
export default class extends Controller {
    static values = {
        title: String,
        text: String
    }

    connect() {
        // Optionnel: préparer le DOM si besoin
    }

    confirm(event) {
        event.preventDefault();
        
        const confirmTitle = this.titleValue || 'Confirmation requise';
        const confirmText = this.textValue || 'Êtes-vous sûr de vouloir effectuer cette action ?';

        // Création dynamique de la modale Bootstrap pour éviter de polluer le DOM initial
        const modalHtml = `
            <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-danger text-white border-0">
                            <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i> ${confirmTitle}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body py-4">
                            <p class="mb-0 fs-5">${confirmText}</p>
                            <p class="text-muted small mt-2 mb-0">Cette action est irréversible.</p>
                        </div>
                        <div class="modal-footer border-0 bg-light">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="button" class="btn btn-danger" id="confirmModalBtn">Oui, supprimer</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modalElement = document.getElementById('confirmModal');
        
        // eslint-disable-next-line no-undef
        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        // Quand on clique sur Confirmer
        document.getElementById('confirmModalBtn').addEventListener('click', () => {
            modal.hide();
            // Soumission réelle du formulaire
            this.element.submit();
        });

        // Nettoyage du DOM après fermeture
        modalElement.addEventListener('hidden.bs.modal', () => {
            modalElement.remove();
        });
    }
}
