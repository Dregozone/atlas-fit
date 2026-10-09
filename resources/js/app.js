/**
 * Lightweight confetti burst used to celebrate completed actions
 * (logging a workout, food, weight, saving goals). Respects reduced motion.
 */
const CONFETTI_COLOURS = ['#10b981', '#06b6d4', '#a78bfa', '#f59e0b', '#f43f5e', '#34d399'];

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function confetti({ particleCount = 90, originX = 0.5, originY = 0.35 } = {}) {
    if (prefersReducedMotion()) {
        return;
    }

    const canvas = document.createElement('canvas');
    canvas.setAttribute('aria-hidden', 'true');
    canvas.style.cssText = 'position:fixed;inset:0;width:100%;height:100%;pointer-events:none;z-index:9999';
    document.body.appendChild(canvas);

    const context = canvas.getContext('2d');
    const pixelRatio = window.devicePixelRatio || 1;
    canvas.width = window.innerWidth * pixelRatio;
    canvas.height = window.innerHeight * pixelRatio;
    context.scale(pixelRatio, pixelRatio);

    const particles = Array.from({ length: particleCount }, () => {
        const angle = Math.random() * Math.PI * 2;
        const speed = 4 + Math.random() * 7;

        return {
            x: window.innerWidth * originX,
            y: window.innerHeight * originY,
            velocityX: Math.cos(angle) * speed,
            velocityY: Math.sin(angle) * speed - 4,
            size: 5 + Math.random() * 5,
            rotation: Math.random() * Math.PI,
            spin: (Math.random() - 0.5) * 0.3,
            colour: CONFETTI_COLOURS[Math.floor(Math.random() * CONFETTI_COLOURS.length)],
            life: 0,
        };
    });

    const maxLife = 110;

    const frame = () => {
        context.clearRect(0, 0, window.innerWidth, window.innerHeight);

        particles.forEach((particle) => {
            particle.life++;
            particle.velocityY += 0.22;
            particle.velocityX *= 0.985;
            particle.x += particle.velocityX;
            particle.y += particle.velocityY;
            particle.rotation += particle.spin;

            context.save();
            context.globalAlpha = Math.max(0, 1 - particle.life / maxLife);
            context.translate(particle.x, particle.y);
            context.rotate(particle.rotation);
            context.fillStyle = particle.colour;
            context.fillRect(-particle.size / 2, -particle.size / 4, particle.size, particle.size / 2);
            context.restore();
        });

        if (particles[0].life < maxLife) {
            requestAnimationFrame(frame);
        } else {
            canvas.remove();
        }
    };

    requestAnimationFrame(frame);
}

window.atlasConfetti = confetti;

window.addEventListener('celebrate', (event) => {
    const detail = Array.isArray(event.detail) ? event.detail[0] ?? {} : event.detail ?? {};

    confetti({ particleCount: detail.big ? 160 : 90 });
});
