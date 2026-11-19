// Mesure first-party (TRD §9) : quelques événements envoyés par sendBeacon à /api/events, seulement
// si le visiteur a accepté la mesure d'audience. Le serveur filtre tout (liste blanche) et revérifie l'accord.
// Aucun texte saisi, aucune coordonnée, aucun outil tiers.

const CTA_TARGETS = [
    ['/diagnostic', 'diagnostic'],
    ['/rendez-vous', 'appointment'],
    ['/contact', 'contact'],
    ['/demo-lab', 'lab'],
];

function send(name, props = {}) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!token) return;
    const body = JSON.stringify({ _token: token, name, props });
    try {
        const blob = new Blob([body], { type: 'application/json' });
        if (navigator.sendBeacon?.('/api/events', blob)) return;
    } catch {
        // Repli ci-dessous.
    }
    fetch('/api/events', { method: 'POST', body, keepalive: true, credentials: 'same-origin', headers: { 'Content-Type': 'application/json', Accept: 'application/json' } }).catch(() => {});
}

function pageView() {
    const props = { path: location.pathname };
    try {
        if (document.referrer) {
            const host = new URL(document.referrer).hostname;
            if (host && host !== location.hostname) props.referrer_host = host;
        }
    } catch {
        // Référent illisible : ignoré.
    }
    const params = new URLSearchParams(location.search);
    for (const key of ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content']) {
        if (params.get(key)) props[key] = params.get(key).slice(0, 100);
    }
    send('page_view', props);
}

// Contexte du clic WhatsApp (TRD §9) : zone de la page, sinon type de page.
function whatsappContext(link) {
    if (link.closest('[data-assistant]')) return 'assistant';
    if (link.closest('header')) return 'header';
    if (link.closest('footer')) return 'footer';
    const [, first, second] = location.pathname.split('/');
    if (first === 'brief') return 'brief';
    if (first === 'demo-lab') return 'lab';
    if (first === 'solutions' && second) return `solution:${second}`;
    if (first === 'secteurs' && second) return `sector:${second}`;
    return first || 'home';
}

function onClick(event) {
    const link = event.target.closest?.('a[href]');
    if (!link) return;

    let url;
    try {
        url = new URL(link.href, location.href);
    } catch {
        return;
    }

    if (url.hostname === 'wa.me' || url.hostname.endsWith('whatsapp.com')) {
        send('whatsapp_click', { context: link.dataset.khWa || whatsappContext(link), path: location.pathname });
        return;
    }

    if (url.origin !== location.origin) return;
    const explicit = link.dataset.khCta;
    const match = CTA_TARGETS.find(([prefix]) => url.pathname === prefix || url.pathname.startsWith(prefix + '/'));
    if (explicit || match) {
        send('cta_click', { cta: explicit || match[1], path: location.pathname });
    }
}

export function track(name, props = {}) {
    send(name, { path: location.pathname, ...props });
}

export function initAnalytics() {
    window.khTrack = () => {};
    // Rien n'est envoyé sans accord pour la mesure d'audience (balise posée par le serveur, Q-011).
    if (document.querySelector('meta[name="kh-analytics"]')?.content !== 'on') return;
    if (navigator.webdriver) return; // robots et tests automatisés : pas de statistiques.
    pageView();
    document.addEventListener('click', onClick, { capture: true });
    window.khTrack = track;
}
