# Dossier Images

## Logo de l'entreprise

Placez le logo de votre entreprise dans ce dossier avec l'un des noms suivants :
- `logo.png` (recommandé)
- `logo.jpg`
- `logo.svg`

### Tailles recommandées :
- **Logo navbar** : 150px de large maximum, hauteur 40px
- **Format** : PNG avec transparence (recommandé) ou JPG
- **Poids** : < 100KB pour un chargement rapide

### Personnalisation

Vous pouvez modifier le chemin et le nom du logo dans `config/config.php` :
```php
define('LOGO_PATH', url('images/logo.png'));
define('SHOW_LOGO', true); // Mettre à false pour désactiver
```

Le logo s'affichera automatiquement dans la barre de navigation (navbar).

