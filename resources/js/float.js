// Les artefacts flottants se mettent en pause hors écran (économie de batterie).
export function initFloat() {
    const els = document.querySelectorAll('.kh-float');
    if (!els.length || !('IntersectionObserver' in window)) return;
    const io = new IntersectionObserver((entries) => {
        entries.forEach(({ isIntersecting, target }) => target.classList.toggle('is-paused', !isIntersecting));
    });
    els.forEach((e) => io.observe(e));
}
