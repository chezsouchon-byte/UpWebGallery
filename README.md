# UpWebGallery

**UpWebGallery** est une galerie photo haut de gamme en un seul fichier PHP.

Objectif : déposer `index.php`, créer un dossier `/photos/`, puis laisser le script générer automatiquement un portfolio responsive, avec navigation, lightbox, SEO/GEO, EXIF, cache et menu mobile.

## Version stable

```text
v1.4.0 — Premium UX stable
```

## Installation

```text
/index.php
/photos/
```

Ajoutez vos images dans `/photos/`.

Formats supportés :

```text
jpg, jpeg, png, gif, webp, avif
```

Les sous-dossiers sont détectés automatiquement.

## Nouveautés v1.4.0

- Direction visuelle plus premium
- Hero éditorial amélioré
- Fond plus sobre et photographique
- Barre de progression de lecture
- Bloc de présentation de collection active
- Micro-interactions plus discrètes
- Mobile affiné
- Conservation de la base stable v1.3.0

## Logo

Le script détecte automatiquement :

```text
/logo.svg
/logo.png
/assets/logo.svg
/assets/logo.png
```

Sinon, il affiche le texte défini dans `$logoText`.

## Cache

Le cache est automatique via `/cache/`.

Si le dossier n’est pas inscriptible, le script continue sans cache.
