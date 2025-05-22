/**
 * Enhanced Diagnostics JavaScript
 * Dodatkowe funkcje dla ultra nowoczesnego interfejsu
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicjalizacja enhanced features
    initParticleBackground();
    initCardAnimations();
    initCounterAnimations();
    initTooltipSystem();
    initProgressIndicators();
    
    // Smooth scrolling dla anchor linków
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});

/**
 * Particle background effect
 */
function initParticleBackground() {
    const canvas = document.createElement('canvas');
    canvas.style.position = 'fixed';
    canvas.style.top = '0';
    canvas.style.left = '0';
    canvas.style.width = '100%';
    canvas.style.height = '100%';
    canvas.style.zIndex = '-2';
    canvas.style.pointerEvents = 'none';
    canvas.style.opacity = '0.3';
    
    document.body.appendChild(canvas);
    
    const ctx = canvas.getContext('2d');
    let particles = [];
    
    function resizeCanvas() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    
    function createParticles() {
        particles = [];
        const particleCount = Math.floor((canvas.width * canvas.height) / 15000);
        
        for (let i = 0; i < particleCount; i++) {
            particles.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                vx: (Math.random() - 0.5) * 0.5,
                vy: (Math.random() - 0.5) * 0.5,
                size: Math.random() * 2 + 1,
                opacity: Math.random() * 0.5 + 0.2
            });
        }
    }
    
    function animateParticles() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        particles.forEach(particle => {
            particle.x += particle.vx;
            particle.y += particle.vy;
            
            if (particle.x < 0 || particle.x > canvas.width) particle.vx *= -1;
            if (particle.y < 0 || particle.y > canvas.height) particle.vy *= -1;
            
            ctx.beginPath();
            ctx.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(255, 255, 255, ${particle.opacity})`;
            ctx.fill();
        });
        
        requestAnimationFrame(animateParticles);
    }
    
    resizeCanvas();
    createParticles();
    animateParticles();
    
    window.addEventListener('resize', () => {
        resizeCanvas();
        createParticles();
    });
}

/**
 * Enhanced card animations
 */
function initCardAnimations() {
    const cards = document.querySelectorAll('.info-card, .status-card, .diagnostic-section');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('fade-in');
                }, index * 100);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    
    cards.forEach(card => {
        observer.observe(card);
        
        // Enhanced hover effects
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
}

/**
 * Counter animations
 */
function initCounterAnimations() {
    const counters = document.querySelectorAll('[data-counter]');
    
    const animateCounter = (element) => {
        const target = parseInt(element.dataset.counter);
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;
        
        const timer = setInterval(() => {
            current += step;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current).toLocaleString('pl-PL');
        }, 16);
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    });
    
    counters.forEach(counter => observer.observe(counter));
}

/**
 * Enhanced tooltip system
 */
function initTooltipSystem() {
    const tooltips = document.querySelectorAll('.tooltip');
    
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', function(e) {
            const tooltipText = this.getAttribute('data-tooltip');
            const tooltipElement = document.createElement('div');
            tooltipElement.className = 'enhanced-tooltip';
            tooltipElement.textContent = tooltipText;
            
            tooltipElement.style.cssText = `
                position: absolute;
                background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
                color: white;
                padding: 0.75rem 1rem;
                border-radius: 8px;
                font-size: 0.875rem;
                font-weight: 500;
                z-index: 10000;
                pointer-events: none;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.1);
                transform: translateY(-10px);
                opacity: 0;
                transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            `;
            
            document.body.appendChild(tooltipElement);
            
            const updatePosition = (e) => {
                tooltipElement.style.left = e.pageX - tooltipElement.offsetWidth / 2 + 'px';
                tooltipElement.style.top = e.pageY - tooltipElement.offsetHeight - 15 + 'px';
            };
            
            updatePosition(e);
            
            setTimeout(() => {
                tooltipElement.style.opacity = '1';
                tooltipElement.style.transform = 'translateY(0)';
            }, 10);
            
            this.addEventListener('mousemove', updatePosition);
            
            this.addEventListener('mouseleave', function() {
                tooltipElement.style.opacity = '0';
                tooltipElement.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    if (tooltipElement.parentNode) {
                        tooltipElement.parentNode.removeChild(tooltipElement);
                    }
                }, 300);
            }, { once: true });
        });
    });
}

/**
 * Progress indicators
 */
function initProgressIndicators() {
    window.showEnhancedProgress = function(containerId, progress, label = '') {
        let container = document.getElementById(containerId);
        
        if (!container) {
            container = document.createElement('div');
            container.id = containerId;
            container.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(20px);
                padding: 2rem;
                border-radius: 16px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
                z-index: 10001;
                min-width: 300px;
                text-align: center;
                border: 1px solid rgba(255, 255, 255, 0.3);
            `;
            
            document.body.appendChild(container);
        }
        
        container.innerHTML = `
            <div style="margin-bottom: 1rem;">
                <div style="width: 60px; height: 60px; margin: 0 auto 1rem; border: 4px solid #e2e8f0; border-top: 4px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <h3 style="margin: 0 0 0.5rem 0; color: #2d3748;">Przetwarzanie...</h3>
                ${label ? `<p style="margin: 0; color: #4a5568;">${label}</p>` : ''}
            </div>
            <div style="background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden;">
                <div style="background: linear-gradient(90deg, #667eea, #764ba2); height: 100%; width: ${progress}%; transition: width 0.3s ease; border-radius: 4px;"></div>
            </div>
            <div style="margin-top: 0.5rem; color: #4a5568; font-weight: 600;">${Math.round(progress)}%</div>
        `;
        
        if (progress >= 100) {
            setTimeout(() => {
                container.style.opacity = '0';
                container.style.transform = 'translate(-50%, -50%) scale(0.8)';
                setTimeout(() => {
                    if (container.parentNode) {
                        container.parentNode.removeChild(container);
                    }
                }, 300);
            }, 1000);
        }
    };
    
    window.hideEnhancedProgress = function(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            container.style.opacity = '0';
            container.style.transform = 'translate(-50%, -50%) scale(0.8)';
            setTimeout(() => {
                if (container.parentNode) {
                    container.parentNode.removeChild(container);
                }
            }, 300);
        }
    };
}

/**
 * Enhanced notifications
 */
function showEnhancedNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    const icons = {
        success: '✨',
        error: '⚠️',
        warning: '🔔',
        info: 'ℹ️'
    };
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="font-size: 1.5rem;">${icons[type] || icons.info}</div>
            <div>
                <div style="font-weight: 600; margin-bottom: 0.25rem;">
                    ${type.charAt(0).toUpperCase() + type.slice(1)}
                </div>
                <div style="opacity: 0.9;">${message}</div>
            </div>
        </div>
        <button onclick="this.parentElement.click()" style="background: none; border: none; color: inherit; font-size: 1.2rem; cursor: pointer; opacity: 0.7; padding: 0; margin-left: auto;">×</button>
    `;
    
    notification.className = `notification ${type}`;
    notification.style.cssText += `
        display: flex;
        align-items: center;
        gap: 1rem;
        cursor: pointer;
        animation: slideInRight 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    `;
    
    document.body.appendChild(notification);
    
    const removeNotification = () => {
        notification.style.animation = 'slideOutRight 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 400);
    };
    
    notification.addEventListener('click', removeNotification);
    
    if (duration > 0) {
        setTimeout(removeNotification, duration);
    }
}

// Dodaj CSS dla animacji
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOutRight {
        from { 
            opacity: 1; 
            transform: translateX(0) scale(1); 
        }
        to { 
            opacity: 0; 
            transform: translateX(100%) scale(0.8); 
        }
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .enhanced-tooltip {
        animation: tooltipFadeIn 0.3s ease !important;
    }
    
    @keyframes tooltipFadeIn {
        from {
            opacity: 0;
            transform: translateY(-5px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
`;
document.head.appendChild(style);

// Expose enhanced functions globally
window.showEnhancedNotification = showEnhancedNotification;