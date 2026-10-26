// Apparition au scroll — docs/UX_UI_DESIGN.md §7. Une seule fois par élément.
const mq = window.matchMedia('(prefers-reduced-motion: reduce)');

export function initReveal() {
    const els = document.querySelectorAll('[data-reveal], .kh-halftone[data-animate]');
    if (mq.matches || !('IntersectionObserver' in window)) {
        els.forEach((e) => e.classList.add('is-in'));
        return;
    }
    const io = new IntersectionObserver((entries) => {
        entries.forEach(({ isIntersecting, target }) => {
            if (isIntersecting) {
                target.classList.add('is-in');
                io.unobserve(target);
            }
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -10% 0px' });
    els.forEach((e) => io.observe(e));
}
