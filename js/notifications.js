/**
 * Système de notifications en temps réel
 */

class NotificationSystem {
    constructor() {
        this.pollInterval = 30000; // 30 secondes
        this.pollTimer = null;
        this.lastCheck = null;
        this.apiUrl = window.BASE_PATH + 'api/notifications.php';
        this.unreadCount = 0;
        this.soundEnabled = this.getSoundPreference();
        this.audioContext = null;
        
        this.init();
    }
    
    init() {
        // Vérifier les notifications au chargement
        this.checkNotifications();
        
        // Démarrer le polling
        this.startPolling();
        
        // Écouter les clics sur le bouton de notifications
        const notificationBtn = document.getElementById('notificationBtn');
        if (notificationBtn) {
            notificationBtn.addEventListener('click', () => {
                this.loadNotificationsDropdown();
            });
        }
        
        // Marquer comme lu au clic
        document.addEventListener('click', (e) => {
            if (e.target.closest('.notification-item')) {
                const notificationId = e.target.closest('.notification-item').dataset.id;
                if (notificationId) {
                    this.markAsRead(notificationId);
                }
            }
        });
    }
    
    /**
     * Vérifier les nouvelles notifications
     */
    async checkNotifications() {
        try {
            const response = await fetch(this.apiUrl + '?action=count');
            
            // Vérifier le status HTTP d'abord
            if (!response.ok) {
                console.warn('Erreur API notifications:', response.status);
                return;
            }
            
            // Vérifier le content-type
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                console.warn('Réponse non-JSON reçue:', contentType);
                return;
            }
            
            const data = await response.json();
            
            if (data.success) {
                const newCount = data.unread_count;
                
                // Si le nombre a changé, mettre à jour l'affichage
                if (newCount !== this.unreadCount) {
                    this.updateNotificationBadge(newCount);
                    this.unreadCount = newCount;
                    
                    // Si nouvelle notification, afficher une notification toast
                    if (newCount > this.unreadCount) {
                        this.showNewNotificationToast();
                    }
                }
            }
        } catch (error) {
            console.error('Erreur vérification notifications:', error);
        }
    }
    
    /**
     * Charger les notifications dans le dropdown
     */
    async loadNotificationsDropdown() {
        try {
            const response = await fetch(this.apiUrl + '?action=get&limit=5');
            
            // Vérifier le status HTTP d'abord
            if (!response.ok) {
                console.warn('Erreur API notifications:', response.status);
                return;
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                console.warn('Réponse non-JSON pour loadNotificationsDropdown');
                return;
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.renderNotificationsDropdown(data.notifications, data.unread_count);
            }
        } catch (error) {
            console.error('Erreur chargement notifications:', error);
        }
    }
    
    /**
     * Rendre le dropdown de notifications
     */
    renderNotificationsDropdown(notifications, unreadCount) {
        const dropdown = document.getElementById('notificationDropdown');
        if (!dropdown) return;
        
        let html = '';
        
        if (notifications.length === 0) {
            html = `
                <li>
                    <div class="dropdown-item-text text-center text-muted py-3">
                        <i class="bi bi-bell-slash fs-4 d-block mb-2"></i>
                        Aucune notification
                    </div>
                </li>
            `;
        } else {
            notifications.forEach(notif => {
                const icon = this.getNotificationIcon(notif.type);
                const color = this.getNotificationColor(notif.type);
                const unreadClass = !notif.lu ? 'notification-item-unread' : '';
                
                html += `
                    <li>
                        <a class="dropdown-item notification-item ${unreadClass}" 
                           href="${notif.lien || '#'}" 
                           data-id="${notif.id}">
                            <div class="d-flex align-items-start">
                                <div class="me-3">
                                    <i class="bi ${icon} ${color} fs-5"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold">${this.escapeHtml(notif.titre)}</div>
                                    <div class="small text-muted">${this.escapeHtml(notif.message)}</div>
                                    <div class="small text-muted mt-1">${notif.time_ago}</div>
                                </div>
                                ${!notif.lu ? '<span class="badge bg-danger ms-2">Nouveau</span>' : ''}
                            </div>
                        </a>
                    </li>
                `;
            });
            
            html += `
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-center" href="${window.BASE_PATH}notifications.php">
                        <i class="bi bi-arrow-right"></i> Voir toutes les notifications
                    </a>
                </li>
            `;
        }
        
        dropdown.innerHTML = html;
    }
    
    /**
     * Mettre à jour le badge de notifications
     */
    updateNotificationBadge(count) {
        const badge = document.getElementById('notificationBadge');
        const badgeText = document.getElementById('notificationBadgeText');
        
        if (badge) {
            if (count > 0) {
                badge.style.display = 'inline-block';
                if (badgeText) {
                    badgeText.textContent = count > 99 ? '99+' : count;
                }
            } else {
                badge.style.display = 'none';
            }
        }
    }
    
    /**
     * Marquer une notification comme lue
     */
    async markAsRead(notificationId) {
        try {
            const formData = new FormData();
            formData.append('id', notificationId);
            
            const response = await fetch(this.apiUrl + '?action=mark_read', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            if (data.success) {
                // Mettre à jour l'affichage
                const item = document.querySelector(`[data-id="${notificationId}"]`);
                if (item) {
                    item.classList.remove('notification-item-unread');
                    const badge = item.querySelector('.badge');
                    if (badge) badge.remove();
                }
                
                // Recharger le compteur
                this.checkNotifications();
            }
        } catch (error) {
            console.error('Erreur marquer comme lu:', error);
        }
    }
    
    /**
     * Afficher un toast pour nouvelle notification
     */
    showNewNotificationToast() {
        // Jouer le son
        this.playNotificationSound();
        
        // Créer un toast Bootstrap si disponible
        if (typeof bootstrap !== 'undefined') {
            const toastContainer = this.getOrCreateToastContainer();
            const toast = this.createToast('Nouvelle notification', 'Vous avez reçu une nouvelle notification');
            
            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            // Supprimer le toast après fermeture
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }
    }
    
    /**
     * Créer un élément toast
     */
    createToast(title, message) {
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.innerHTML = `
            <div class="toast-header bg-primary text-white">
                <i class="bi bi-bell-fill me-2"></i>
                <strong class="me-auto">${this.escapeHtml(title)}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${this.escapeHtml(message)}
            </div>
        `;
        return toast;
    }
    
    /**
     * Obtenir ou créer le conteneur de toasts
     */
    getOrCreateToastContainer() {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        return container;
    }
    
    /**
     * Démarrer le polling
     */
    startPolling() {
        this.pollTimer = setInterval(() => {
            this.checkNotifications();
        }, this.pollInterval);
    }
    
    /**
     * Arrêter le polling
     */
    stopPolling() {
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
            this.pollTimer = null;
        }
    }
    
    /**
     * Obtenir l'icône selon le type de notification
     */
    getNotificationIcon(type) {
        const icons = {
            'rdv_confirme': 'bi-calendar-check',
            'rdv_refuse': 'bi-calendar-x',
            'rdv_demande': 'bi-calendar-plus',
            'document_valide': 'bi-file-check',
            'document_rejete': 'bi-file-x',
            'message': 'bi-envelope',
            'dossier_update': 'bi-folder',
            'dossier_finalise': 'bi-folder-check'
        };
        return icons[type] || 'bi-bell';
    }
    
    /**
     * Obtenir la couleur selon le type de notification
     */
    getNotificationColor(type) {
        const colors = {
            'rdv_confirme': 'text-success',
            'rdv_refuse': 'text-danger',
            'rdv_demande': 'text-info',
            'document_valide': 'text-success',
            'document_rejete': 'text-danger',
            'message': 'text-primary',
            'dossier_update': 'text-info',
            'dossier_finalise': 'text-success'
        };
        return colors[type] || 'text-primary';
    }
    
    /**
     * Échapper le HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Jouer le son de notification
     */
    playNotificationSound() {
        if (!this.soundEnabled) return;
        
        try {
            // Essayer avec un fichier audio
            const audio = new Audio(window.BASE_PATH + 'sounds/notification.mp3');
            audio.volume = 0.5;
            audio.play().catch(err => {
                console.log('Impossible de jouer le son:', err);
                // Fallback: générer un son avec l'API Web Audio
                this.playToneSound();
            });
        } catch (err) {
            console.log('Erreur lors de la lecture du son:', err);
            this.playToneSound();
        }
    }
    
    /**
     * Générer un son avec Web Audio API (fallback)
     */
    playToneSound() {
        try {
            if (!this.audioContext) {
                this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            }
            
            const ctx = this.audioContext;
            const now = ctx.currentTime;
            
            // Créer une séquence de notes pour une notification agréable
            // Notes: Do, Mi (arpège)
            const notes = [262, 330]; // Hz
            const duration = 0.2;
            
            notes.forEach((freq, index) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                
                osc.connect(gain);
                gain.connect(ctx.destination);
                
                osc.frequency.value = freq;
                osc.type = 'sine';
                
                // Fade in et out
                gain.gain.setValueAtTime(0, now + index * duration);
                gain.gain.linearRampToValueAtTime(0.3, now + index * duration + 0.05);
                gain.gain.linearRampToValueAtTime(0, now + (index + 1) * duration);
                
                osc.start(now + index * duration);
                osc.stop(now + (index + 1) * duration);
            });
        } catch (err) {
            console.log('Web Audio API non disponible:', err);
        }
    }
    
    /**
     * Basculer le son des notifications
     */
    toggleSound() {
        this.soundEnabled = !this.soundEnabled;
        localStorage.setItem('notificationSoundEnabled', this.soundEnabled);
        return this.soundEnabled;
    }
    
    /**
     * Récupérer la préférence de son depuis le localStorage
     */
    getSoundPreference() {
        const saved = localStorage.getItem('notificationSoundEnabled');
        if (saved === null) {
            return true; // Par défaut, activé
        }
        return saved === 'true';
    }
    
    /**
     * Définir la préférence de son
     */
    setSoundEnabled(enabled) {
        this.soundEnabled = enabled;
        localStorage.setItem('notificationSoundEnabled', enabled);
    }
}

// Initialiser le système de notifications au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.BASE_PATH === 'undefined') {
        window.BASE_PATH = '';
    }
    window.notificationSystem = new NotificationSystem();
});


