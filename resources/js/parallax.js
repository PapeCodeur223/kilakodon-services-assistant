// Parallaxe légère au défilement ([data-parallax="0.15"]) : desktop uniquement, translateY seulement.
export function initParallax() {
    const els = [...document.querySelectorAll('[data-parallax]')];
    const desktop = window.matchMedia('(min-width: 1024px)');
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (!els.length || !desktop.matches || reduce.matches) return;

    let ticking = false;
    const update = () => {
        const y = window.scrollY;
        els.forEach((el) => {
            const speed = parseFloat(el.dataset.parallax) || 0.1;
            el.style.setProperty('--py', `${Math.round(y * speed * -1)}px`);
        });
        ticking = false;
    };

    window.addEventListener('scroll', () => {
        if (!ticking && window.scrollY < 1400) {
            requestAnimationFrame(update);
            ticking = true;
        }
    }, { passive: true });
}
