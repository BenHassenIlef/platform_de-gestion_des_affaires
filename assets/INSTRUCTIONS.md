# Instructions d'intégration du contrôleur d'image de profil

Ce document explique comment intégrer le contrôleur Stimulus `profile_image_controller.js` dans vos templates Twig Symfony pour synchroniser l'image de profil dans le formulaire avec l'icône de la barre de navigation.

## Prérequis

- Symfony avec AssetMapper configuré
- Stimulus installé et configuré
- Bootstrap (optionnel, mais utilisé dans les exemples)

## Fichiers créés

1. `controllers/profile_image_controller.js` - Le contrôleur Stimulus
2. `styles/profile.css` - Les styles CSS pour la page de profil
3. `profile_example.html` - Un exemple d'utilisation (à ne pas utiliser en production)

## Intégration dans vos templates Twig

### 1. Dans votre template de profil (par exemple `profile.html.twig`)

```twig
{# Dans la section du formulaire de profil #}
<div data-controller="profile-image">
    <form data-profile-image-target="form" data-action="submit->profile-image#saveImage">
        <div class="image-preview-container">
            <img src="{{ asset('images/ph2.png') }}" 
                 alt="Prévisualisation" 
                 class="image-preview" 
                 data-profile-image-target="preview">
        </div>
        
        {{ form_label(form.imageFile, 'Image de profil') }}
        {{ form_widget(form.imageFile, {
            'attr': {
                'data-profile-image-target': 'input',
                'data-action': 'change->profile-image#changeImage'
            }
        }) }}
        
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </form>
</div>
```

### 2. Dans votre template de navigation (par exemple `navbar.html.twig` ou `header.html.twig`)

```twig
{# Dans la section de la barre de navigation où se trouve l'avatar #}
<div class="navbar-avatar">
    <img src="{{ asset(app.user.avatarPath|default('images/ph2.png')) }}" 
         alt="Avatar" 
         class="avatar-icon" 
         data-profile-image-target="icon" 
         data-controller="profile-image">
</div>
```

## Comment ça fonctionne

1. Le contrôleur Stimulus `profile_image_controller.js` utilise quatre cibles :
   - `input` : l'input de type file pour sélectionner une nouvelle image
   - `preview` : l'image de prévisualisation dans le formulaire
   - `icon` : l'icône dans la barre de navigation
   - `form` : le formulaire de profil

2. Lorsque l'utilisateur sélectionne une nouvelle image via l'input file, la méthode `changeImage()` est déclenchée.

3. Cette méthode lit le fichier sélectionné et met à jour à la fois l'image de prévisualisation et l'icône avec la nouvelle image.

4. Lorsque l'utilisateur clique sur le bouton Enregistrer, la méthode `saveImage()` est déclenchée.

5. Cette méthode s'assure que les deux icônes (prévisualisation et avatar dans la navbar) sont synchronisées avec la même image avant de soumettre le formulaire.

## Adaptation à votre projet

Vous devrez peut-être adapter ces exemples en fonction de la structure de votre projet Symfony. Assurez-vous que :

1. Les chemins d'accès aux assets sont corrects
2. Les noms des champs de formulaire correspondent à votre entité User
3. Les attributs data-controller et data-targets sont correctement placés dans vos templates

## Persistance des données

Ce contrôleur gère uniquement l'aspect frontend de la fonctionnalité. Pour que les changements d'image soient persistants :

1. Assurez-vous que votre formulaire de profil est correctement configuré pour télécharger et enregistrer l'image
2. Traitez le fichier téléchargé dans votre contrôleur Symfony
3. Mettez à jour l'entité User avec le nouveau chemin d'image

## Support des navigateurs

Cette fonctionnalité utilise l'API FileReader qui est prise en charge par tous les navigateurs modernes.