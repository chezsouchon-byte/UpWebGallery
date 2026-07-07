# Commandes Git pour publier UpWebGallery

## 1. Installer Git si besoin
Télécharger Git pour Windows :
https://git-scm.com/download/win

## 2. Ouvrir PowerShell dans le dossier UpWebGallery

## 3. Initialiser Git

```powershell
git init
git add .
git commit -m "Initial commit - UpWebGallery v1.3.0 stable"
```

## 4. Créer un dépôt vide sur GitHub

Nom conseillé :

```text
UpWebGallery
```

Ne coche pas README, .gitignore ou licence sur GitHub, ils sont déjà inclus.

## 5. Lier ton dépôt GitHub

Remplace USERNAME par ton pseudo GitHub :

```powershell
git branch -M main
git remote add origin https://github.com/USERNAME/UpWebGallery.git
git push -u origin main
```

## 6. Pour les prochaines versions

```powershell
git add .
git commit -m "Update UpWebGallery"
git push
```
