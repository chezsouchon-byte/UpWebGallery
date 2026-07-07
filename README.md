# UpWebGallery

**UpWebGallery** est une galerie photo haut de gamme en un seul fichier PHP.

Objectif : déposer `index.php`, créer un dossier `/photos/`, puis laisser le script générer automatiquement un portfolio propre, responsive, avec navigation, lightbox, SEO/GEO, EXIF, cache et menu mobile.

## Version stable

```text
v1.3.0 — Stable
```

## Installation simple

Structure minimale :

```text
/index.php
/photos/
```

Ajoutez vos images dans `/photos/`.

Formats supportés :

```text
jpg, jpeg, png, gif, webp, avif
```

Les sous-dossiers sont détectés automatiquement :

```text
/photos/
  portrait/
  voyage/
  noir-et-blanc/
```

## Dossiers

```text
/photos/   Contient les images et collections
/cache/    Cache automatique du scan, créé/utilisé si possible
```

## Configuration

Les réglages principaux sont en haut de `index.php` :

```php
$siteTitle
$siteSubtitle
$siteDescription
$logoText
$contactEmail
$socialLinks
```

## Logo

Le script détecte automatiquement un logo si l’un de ces fichiers existe :

```text
/logo.svg
/logo.png
/assets/logo.svg
/assets/logo.png
```

Sinon, il affiche le texte défini dans `$logoText`.

## Cache

Le cache est automatique.

Par défaut :

```php
$cacheEnabled = true;
$cacheTtl = 600;
```

Si le dossier `/cache/` n’est pas inscriptible, le script continue sans cache.

## GitHub

Les vraies photos ne sont pas incluses par défaut dans GitHub.  
Le dossier `/photos/` contient seulement un `.gitkeep`.

C’est volontaire pour éviter de publier des photos personnelles ou lourdes.

## Auteur

Projet préparé pour UP-WEB / Sébastien Souchon.
