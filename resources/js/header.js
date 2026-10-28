// Header voilé après 8 px de défilement ; menus <details> fermés au clic extérieur et à Échap.
export function initHeader() {
    const header = document.querySelector('[data-header]');
    if (header) {
        let ticking = false;
        const update = () => {
            header.classList.toggle('is-scrolled', window.scrollY > 8);
            // Barre de progression de lecture (transform: scaleX).
            const max = document.documentElement.scrollHeight - window.innerHeight;
            header.style.setProperty('--progress', max > 0 ? (window.scrollY / max).toFixed(3) : 0);
            ticking = false;
        };
        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(update);
                ticking = true;
            }
        }, { passive: true });
        update();
    }

    const menus = [...document.querySelectorAll('[data-disclosure]')];
    const closeAll = (except) => menus.forEach((m) => { if (m !== except) m.open = false; });

    menus.forEach((menu) => {
        menu.addEventListener('toggle', () => {
            if (menu.open) closeAll(menu);
            if (menu.hasAttribute('data-modal')) {
                document.documentElement.classList.toggle('overflow-hidden', menu.open);
            }
        });
    });

    document.querySelectorAll('[data-disclosure-close]').forEach((button) => {
        button.addEventListener('click', () => {
            const menu = button.closest('details');
            menu.open = false;
            menu.querySelector('summary')?.focus();
        });
    });

    document.addEventListener('click', (event) => {
        menus.forEach((m) => {
            if (m.open && !m.contains(event.target)) m.open = false;
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const open = menus.find((m) => m.open);
        if (open) {
            open.open = false;
            open.querySelector('summary')?.focus();
        }
    });
}
