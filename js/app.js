/**
 * ============================================
 * APPLICATION JAVASCRIPT - PROFESSIONNEL
 * ============================================
 */

(function() {
    'use strict';

    // ============================================
    // INITIALIZATION
    // ============================================

    document.addEventListener('DOMContentLoaded', function() {
        initSidebar();
        initNavigation();
        initTooltips();
        initModals();
        initTableInteractions();
        initScrollAnimations();
        initFormValidation();
        initSkeletonLoader();
        initPageTransitions();
        initHeaderScroll();
        initCardAnimations();
        initButtonRipple();
        initSmoothScroll();
        initParallaxEffects();
        initPWA();
    });

    // ============================================
    // SIDEBAR FUNCTIONALITY
    // ============================================

    function initSidebar() {
        const sidebar = document.getElementById('sidebar');
        const toggleButtons = document.querySelectorAll('.btn-toggle-sidebar');
        const mainContent = document.getElementById('mainContent');
        
        // Vérifier que la sidebar existe avant de continuer
        if (!sidebar) return;
        
        // Toggle sidebar on mobile
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                document.body.classList.toggle('sidebar-open');
            });
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && 
                    !e.target.closest('.btn-toggle-sidebar') && 
                    sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    document.body.classList.remove('sidebar-open');
                }
            }
        });

        // Sidebar hover effect on desktop
        if (window.innerWidth > 768) {
            sidebar.addEventListener('mouseenter', function() {
                sidebar.style.width = 'var(--sidebar-width)';
            });

            sidebar.addEventListener('mouseleave', function() {
                // Optionnel: réduire la sidebar au survol
                // sidebar.style.width = 'var(--sidebar-collapsed-width)';
            });
        }

        // Active menu item highlight
        const navLinks = document.querySelectorAll('.sidebar-menu .nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                navLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                // Animation du clic
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });
    }

    // ============================================
    // NAVIGATION BETWEEN SECTIONS
    // ============================================

    function initNavigation() {
        const navLinks = document.querySelectorAll('.sidebar-menu .nav-link[data-section]');
        const sections = document.querySelectorAll('.content-section');
        const breadcrumb = document.getElementById('currentBreadcrumb');

        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetSection = this.getAttribute('data-section');
                const sectionName = this.querySelector('span').textContent;

                // Hide all sections with fade out
                sections.forEach(section => {
                    section.classList.remove('active');
                    section.classList.add('fade-out');
                });

                // Show target section with fade in
                setTimeout(() => {
                    sections.forEach(section => {
                        section.classList.remove('fade-out');
                        if (section.id === targetSection) {
                            section.classList.add('active');
                            section.style.animation = 'fadeIn 0.4s ease-out';
                            
                            // Update breadcrumb
                            if (breadcrumb) {
                                breadcrumb.textContent = sectionName;
                            }

                            // Scroll to top
                            window.scrollTo({
                                top: 0,
                                behavior: 'smooth'
                            });
                        }
                    });
                }, 150);
            });
        });
    }

    // ============================================
    // TOOLTIPS INITIALIZATION
    // ============================================

    function initTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // ============================================
    // MODAL ENHANCEMENTS
    // ============================================

    function initModals() {
        const modals = document.querySelectorAll('.modal');
        
        modals.forEach(modal => {
            modal.addEventListener('show.bs.modal', function() {
                const modalDialog = this.querySelector('.modal-dialog');
                modalDialog.style.animation = 'scaleIn 0.25s ease-out';
            });

            modal.addEventListener('hide.bs.modal', function() {
                const modalDialog = this.querySelector('.modal-dialog');
                modalDialog.style.animation = 'scaleOut 0.25s ease-out';
            });
        });

        // Form submission in modals
        const addClientForm = document.getElementById('addClientForm');
        if (addClientForm) {
            addClientForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Show loading state
                const submitBtn = this.closest('.modal').querySelector('.btn-primary');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner"></span> Enregistrement...';
                submitBtn.disabled = true;

                // Simulate API call
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(this.closest('.modal'));
                    modal.hide();
                    
                    // Show success message
                    showNotification('Client ajouté avec succès!', 'success');
                }, 1500);
            });
        }
    }

    // ============================================
    // TABLE INTERACTIONS
    // ============================================

    function initTableInteractions() {
        const tables = document.querySelectorAll('.table');
        
        tables.forEach(table => {
            // Row click animation
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                // Stagger animation on load
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    row.style.transition = 'opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1), transform 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
                    row.style.opacity = '1';
                    row.style.transform = 'translateX(0)';
                    row.classList.add('animate-in');
                }, index * 80);

                // Hover effect with smooth transition
                row.addEventListener('mouseenter', function() {
                    this.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                });

                // Click effect with ripple
                row.addEventListener('click', function(e) {
                    const ripple = document.createElement('div');
                    ripple.classList.add('row-ripple');
                    const rect = this.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    this.appendChild(ripple);
                    
                    setTimeout(() => ripple.remove(), 600);
                });
            });

            // Checkbox selection
            const checkboxes = table.querySelectorAll('thead input[type="checkbox"], tbody input[type="checkbox"]');
            const headerCheckbox = table.querySelector('thead input[type="checkbox"]');
            
            if (headerCheckbox) {
                headerCheckbox.addEventListener('change', function() {
                    const bodyCheckboxes = table.querySelectorAll('tbody input[type="checkbox"]');
                    bodyCheckboxes.forEach(cb => {
                        cb.checked = this.checked;
                    });
                });
            }
        });
    }

    // ============================================
    // SCROLL ANIMATIONS
    // ============================================

    function initScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('scroll-animate');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe cards and other elements
        const animatedElements = document.querySelectorAll('.card, .page-header');
        animatedElements.forEach(el => {
            observer.observe(el);
        });
    }

    // ============================================
    // FORM VALIDATION
    // ============================================

    function initFormValidation() {
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            const inputs = form.querySelectorAll('input, textarea, select');
            
            inputs.forEach(input => {
                // Real-time validation
                input.addEventListener('blur', function() {
                    validateField(this);
                });

                input.addEventListener('input', function() {
                    if (this.classList.contains('is-invalid')) {
                        validateField(this);
                    }
                });
            });

            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                inputs.forEach(input => {
                    if (!validateField(input)) {
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    showNotification('Veuillez corriger les erreurs dans le formulaire', 'danger');
                }
            });
        });
    }

    function validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';

        // Required validation
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'Ce champ est obligatoire';
        }

        // Email validation
        if (field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Email invalide';
            }
        }

        // Update field state
        if (isValid) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            removeFieldError(field);
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
            showFieldError(field, errorMessage);
        }

        return isValid;
    }

    function showFieldError(field, message) {
        removeFieldError(field);
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }

    function removeFieldError(field) {
        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    // ============================================
    // SKELETON LOADER
    // ============================================

    function initSkeletonLoader() {
        const skeletonLoader = document.getElementById('skeletonLoader');
        
        // Show loader function
        window.showLoader = function() {
            if (skeletonLoader) {
                skeletonLoader.classList.add('active');
            }
        };

        // Hide loader function
        window.hideLoader = function() {
            if (skeletonLoader) {
                skeletonLoader.classList.remove('active');
            }
        };

        // Simulate page load
        window.addEventListener('load', function() {
            setTimeout(() => {
                hideLoader();
            }, 500);
        });
    }

    // ============================================
    // PAGE TRANSITIONS
    // ============================================

    function initPageTransitions() {
        // Smooth page transitions
        document.body.style.opacity = '0';
        document.body.classList.add('page-enter');
        window.addEventListener('load', function() {
            document.body.style.transition = 'opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            document.body.style.opacity = '1';
            setTimeout(() => {
                document.body.classList.remove('page-enter');
            }, 400);
        });
    }

    // ============================================
    // HEADER SCROLL EFFECT
    // ============================================

    function initHeaderScroll() {
        const header = document.querySelector('.navbar');
        if (!header) return;

        let lastScroll = 0;
        window.addEventListener('scroll', throttle(function() {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }

            lastScroll = currentScroll;
        }, 100));
    }

    // ============================================
    // CARD ANIMATIONS ON SCROLL
    // ============================================

    function initCardAnimations() {
        const cards = document.querySelectorAll('.card');
        if (cards.length === 0) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                        entry.target.classList.add('animate-fade-in');
                    }, index * 100);
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s cubic-bezier(0.4, 0, 0.2, 1), transform 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            observer.observe(card);
        });
    }

    // ============================================
    // BUTTON RIPPLE EFFECT
    // ============================================

    function initButtonRipple() {
        const buttons = document.querySelectorAll('.btn, button');
        
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;

                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');

                const existingRipple = this.querySelector('.ripple');
                if (existingRipple) {
                    existingRipple.remove();
                }

                this.appendChild(ripple);

                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    }

    // ============================================
    // SMOOTH SCROLL
    // ============================================

    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#' || href === '') return;
                
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    // ============================================
    // PARALLAX EFFECTS
    // ============================================

    function initParallaxEffects() {
        const parallaxElements = document.querySelectorAll('[data-parallax]');
        if (parallaxElements.length === 0) return;

        window.addEventListener('scroll', throttle(function() {
            const scrolled = window.pageYOffset;
            
            parallaxElements.forEach(element => {
                const speed = parseFloat(element.dataset.parallax) || 0.5;
                const yPos = -(scrolled * speed);
                element.style.transform = `translateY(${yPos}px)`;
            });
        }, 10));
    }

    // ============================================
    // NOTIFICATION SYSTEM
    // ============================================

    function showNotification(message, type = 'info') {
        // Remove existing notifications
        const existing = document.querySelector('.notification-toast');
        if (existing) {
            existing.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => existing.remove(), 300);
        }

        // Create notification
        const notification = document.createElement('div');
        const typeClass = type === 'success' ? 'success' : type === 'danger' ? 'danger' : 'info';
        notification.className = `notification-toast alert alert-${typeClass} alert-dismissible fade show`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            min-width: 320px;
            max-width: 400px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            border-radius: 12px;
            border: none;
            animation: slideInRightBounce 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            backdrop-filter: blur(10px);
        `;
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                <span>${message}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    }

    // Expose to global scope
    window.showNotification = showNotification;

    // ============================================
    // UTILITY FUNCTIONS
    // ============================================

    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Throttle function
    function throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    // Request Animation Frame throttle for smooth animations
    function rafThrottle(func) {
        let rafId = null;
        return function(...args) {
            if (rafId === null) {
                rafId = requestAnimationFrame(() => {
                    func.apply(this, args);
                    rafId = null;
                });
            }
        };
    }

    // ============================================
    // RESPONSIVE HANDLERS
    // ============================================

    window.addEventListener('resize', debounce(function() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        
        if (window.innerWidth > 768) {
            sidebar.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        }
    }, 250));

    // ============================================
    // KEYBOARD SHORTCUTS
    // ============================================

    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K for search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.getElementById('globalSearchInput');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }

        // Escape to close modals/sidebar
        if (e.key === 'Escape') {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        }
    });

    // ============================================
    // PERFORMANCE OPTIMIZATION
    // ============================================

    // Lazy load images
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        observer.unobserve(img);
                    }
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }

    // ============================================
    // ADDITIONAL CSS FOR RIPPLE EFFECT
    // ============================================

    // Inject ripple styles
    const style = document.createElement('style');
    style.textContent = `
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple-animation 0.6s ease-out;
            pointer-events: none;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .row-ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(113, 75, 103, 0.2);
            width: 20px;
            height: 20px;
            transform: scale(0);
            animation: ripple-animation 0.6s ease-out;
            pointer-events: none;
        }
        
        @keyframes slideInRightBounce {
            0% {
                transform: translateX(400px);
                opacity: 0;
            }
            60% {
                transform: translateX(-10px);
            }
            100% {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);

    // ============================================
    // PWA FUNCTIONALITY
    // ============================================

    function initPWA() {
        // Vérifier si le navigateur supporte les service workers
        if ('serviceWorker' in navigator) {
            // Enregistrer le service worker
            window.addEventListener('load', function() {
                const swPath = 'service-worker.js';
                navigator.serviceWorker.register(swPath)
                    .then(function(registration) {
                        console.log('[PWA] Service Worker enregistré avec succès:', registration.scope);
                        
                        // Vérifier les mises à jour du service worker
                        registration.addEventListener('updatefound', () => {
                            const newWorker = registration.installing;
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    // Nouveau service worker disponible
                                    showPWAUpdateNotification();
                                }
                            });
                        });
                    })
                    .catch(function(error) {
                        console.error('[PWA] Erreur lors de l\'enregistrement du Service Worker:', error);
                    });
            });

            // Gérer l'installation PWA
            let deferredPrompt;
            window.addEventListener('beforeinstallprompt', (e) => {
                // Empêcher le prompt par défaut
                e.preventDefault();
                // Stocker l'événement pour l'utiliser plus tard
                deferredPrompt = e;
                // Afficher un bouton d'installation personnalisé
                showPWAInstallButton();
            });

            // Gérer l'installation réussie
            window.addEventListener('appinstalled', () => {
                console.log('[PWA] Application installée avec succès');
                hidePWAInstallButton();
                showNotification('Application installée avec succès!', 'success');
                deferredPrompt = null;
            });

            // Fonction pour afficher le bouton d'installation
            function showPWAInstallButton() {
                // Vérifier si l'app est déjà installée
                if (window.matchMedia('(display-mode: standalone)').matches || 
                    window.navigator.standalone === true) {
                    return; // Déjà installée
                }

                // Créer le bouton d'installation s'il n'existe pas
                let installButton = document.getElementById('pwa-install-button');
                if (!installButton) {
                    installButton = document.createElement('button');
                    installButton.id = 'pwa-install-button';
                    installButton.className = 'btn btn-primary position-fixed';
                    installButton.style.cssText = 'bottom: 20px; right: 20px; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.3);';
                    installButton.innerHTML = '<i class="bi bi-download"></i> Installer l\'application';
                    
                    installButton.addEventListener('click', async () => {
                        if (deferredPrompt) {
                            // Afficher le prompt d'installation
                            deferredPrompt.prompt();
                            
                            // Attendre la réponse de l'utilisateur
                            const { outcome } = await deferredPrompt.userChoice;
                            console.log('[PWA] Choix de l\'utilisateur:', outcome);
                            
                            deferredPrompt = null;
                            hidePWAInstallButton();
                        }
                    });

                    document.body.appendChild(installButton);
                }
            }

            function hidePWAInstallButton() {
                const installButton = document.getElementById('pwa-install-button');
                if (installButton) {
                    installButton.remove();
                }
            }

            function showPWAUpdateNotification() {
                const notification = document.createElement('div');
                notification.className = 'alert alert-info alert-dismissible fade show position-fixed';
                notification.style.cssText = 'top: 20px; right: 20px; z-index: 10000; min-width: 300px;';
                notification.innerHTML = `
                    <i class="bi bi-info-circle"></i> 
                    Une nouvelle version de l'application est disponible.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    <div class="mt-2">
                        <button class="btn btn-sm btn-primary" onclick="window.location.reload()">
                            Mettre à jour
                        </button>
                    </div>
                `;
                document.body.appendChild(notification);
            }
        } else {
            console.warn('[PWA] Service Workers non supportés par ce navigateur');
        }
    }

    // ============================================
    // CONSOLE LOG
    // ============================================

    console.log('%c Application Professionnelle ', 'background: #2C3E50; color: #fff; padding: 5px 10px; border-radius: 4px; font-weight: 600;');
    console.log('%c Application initialisée avec succès! ', 'color: #2C3E50; font-size: 12px;');

})();


