# Icônes PWA

Ce dossier doit contenir les icônes nécessaires pour l'application PWA.

## Icônes requises

Pour que l'application PWA fonctionne correctement, vous devez créer les icônes suivantes :

- `icon-16x16.png` (16x16 pixels)
- `icon-32x32.png` (32x32 pixels)
- `icon-72x72.png` (72x72 pixels)
- `icon-96x96.png` (96x96 pixels)
- `icon-128x128.png` (128x128 pixels)
- `icon-144x144.png` (144x144 pixels)
- `icon-152x152.png` (152x152 pixels)
- `icon-192x192.png` (192x192 pixels)
- `icon-384x384.png` (384x384 pixels)
- `icon-512x512.png` (512x512 pixels)

## Comment générer les icônes

### Option 1 : Utiliser un générateur en ligne

1. Visitez [PWA Asset Generator](https://github.com/onderceylan/pwa-asset-generator) ou [RealFaviconGenerator](https://realfavicongenerator.net/)
2. Téléchargez une icône de base (512x512 pixels minimum)
3. Générez toutes les tailles nécessaires
4. Placez les fichiers dans ce dossier

### Option 2 : Créer manuellement

1. Créez une icône de base de 512x512 pixels avec un fond transparent ou coloré
2. Utilisez un outil comme ImageMagick, GIMP, ou Photoshop pour redimensionner :
   ```bash
   # Exemple avec ImageMagick
   convert icon-512x512.png -resize 16x16 icon-16x16.png
   convert icon-512x512.png -resize 32x32 icon-32x32.png
   # ... etc
   ```

### Option 3 : Utiliser un script automatique

Vous pouvez utiliser un script Node.js avec `sharp` ou `jimp` pour générer automatiquement toutes les tailles.

## Design recommandé

- **Couleur de fond** : #0d6efd (bleu primaire de Bootstrap)
- **Icône** : Un bâtiment ou un symbole représentant un cabinet d'immigration
- **Format** : PNG avec transparence ou fond coloré
- **Style** : Simple et reconnaissable même en petite taille

## Note importante

Les icônes doivent être au format PNG et optimisées pour le web. Assurez-vous que les fichiers sont bien nommés et placés dans ce dossier avant de déployer l'application.









