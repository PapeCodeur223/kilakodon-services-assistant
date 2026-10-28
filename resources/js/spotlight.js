// Halo lumineux qui suit la souris sur les cartes (.kh-card) : deux variables CSS, une écriture par image.
export function initSpotlight() {
    if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;

    let frame = 0;
    document.addEventListener('pointermove', (event) => {
        const card = event.target instanceof Element ? event.target.closest('.kh-card') : null;
        if (!card || frame) return;
        frame = requestAnimationFrame(() => {
            const rect = card.getBoundingClientRect();
            card.style.setProperty('--mx', `${event.clientX - rect.left}px`);
            card.style.setProperty('--my', `${event.clientY - rect.top}px`);
            frame = 0;
        });
    }, { passive: true });
}
