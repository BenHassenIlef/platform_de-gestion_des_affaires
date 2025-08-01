import { Controller } from '@hotwired/stimulus';

/*
 * Profile Image Controller
 *
 * This controller handles the synchronization between the profile image input
 * and the profile icon display.
 */
export default class extends Controller {
    static targets = ['input', 'preview', 'icon', 'form'];
    
    // Variable pour stocker l'URL de l'image temporaire
    imageUrl = null;

    connect() {
        console.log('Profile Image Controller connected');
    }

    // Cette méthode est déclenchée lorsque l'input file change
    changeImage() {
        if (!this.hasInputTarget || !this.hasPreviewTarget || !this.hasIconTarget) {
            console.error('Missing required targets for profile image controller');
            return;
        }

        const input = this.inputTarget;
        const preview = this.previewTarget;
        const icon = this.iconTarget;

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = (e) => {
                // Mettre à jour la prévisualisation et l'icône avec la nouvelle image
                this.imageUrl = e.target.result;
                preview.src = this.imageUrl;
                icon.src = this.imageUrl;
            };
            
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Cette méthode est déclenchée lors de la soumission du formulaire
    saveImage(event) {
        // Empêcher la soumission par défaut du formulaire si nécessaire
        // event.preventDefault();
        
        // Assurez-vous que les deux icônes sont synchronisées
        if (this.hasIconTarget && this.hasPreviewTarget && this.imageUrl) {
            this.iconTarget.src = this.imageUrl;
            this.previewTarget.src = this.imageUrl;
            
            console.log('Image synchronisée lors de la sauvegarde');
        }
        
        // Si vous voulez continuer la soumission du formulaire, ne pas appeler preventDefault()
        // Sinon, vous pouvez implémenter ici une soumission AJAX personnalisée
    }
}