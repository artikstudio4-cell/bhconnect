# 🔊 Dossier des sons

Contient les fichiers audio pour les notifications de l'application.

## Fichiers

### notification.mp3
Son joué lors de la réception d'une nouvelle notification.

**Caractéristiques:**
- Format: MP3
- Durée: < 2 secondes
- Volume: Modéré (50%)
- Type: Son de notification discret et agréable

## Utilisation

Les sons sont joués automatiquement quand:
- Une nouvelle notification arrive
- L'utilisateur a activé les sons dans les préférences

## Fallback

Si le fichier MP3 n'est pas disponible, la Web Audio API génère automatiquement un son (deux notes: Do, Mi).

## Personnalisation

Pour remplacer le son par défaut:
1. Ajouter un nouveau fichier MP3 nommé `notification.mp3`
2. Placer le fichier dans ce dossier
3. S'assurer que le fichier est bien nommé `notification.mp3`

## Contrôle du son

L'utilisateur peut activer/désactiver les sons via:
- `notificationSystem.setSoundEnabled(false)` - Désactiver
- `notificationSystem.setSoundEnabled(true)` - Activer
- `notificationSystem.toggleSound()` - Basculer

La préférence est sauvegardée dans le localStorage.
