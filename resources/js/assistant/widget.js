// Panneau de l'assistant (UX §4.3), chargé à la demande. JavaScript natif : les pages publiques
// interdisent `unsafe-eval` (CSP, D-014), donc pas d'Alpine ici (D-019). Tout texte venant du serveur
// est inséré via textContent ; le Markdown est restreint (gras, listes, liens internes au site).

const ICONS = {
    sparkles: '<path d="M9.94 14.06 4 20M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/>',
    x: '<path d="M18 6 6 18M6 6l12 12"/>',
    arrowUp: '<path d="m5 12 7-7 7 7M12 19V5"/>',
    alert: '<circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>',
};

const SUGGESTIONS = [
    'Quel logiciel pour ma boutique ?',
    'Comment se passe un projet avec vous ?',
    'Combien de temps pour un ERP ?',
];

function icon(name, size = 20) {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('width', size);
    svg.setAttribute('height', size);
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', '1.75');
    svg.setAttribute('stroke-linecap', 'round');
    svg.setAttribute('stroke-linejoin', 'round');
    svg.setAttribute('aria-hidden', 'true');
    svg.innerHTML = ICONS[name]; // constantes ci-dessus uniquement
    return svg;
}

function h(tag, attrs = {}, ...children) {
    const el = document.createElement(tag);
    for (const [key, value] of Object.entries(attrs)) {
        if (value === null || value === undefined || value === false) continue;
        if (key === 'class') el.className = value;
        else if (key.startsWith('on')) el.addEventListener(key.slice(2), value);
        else el.setAttribute(key, value === true ? '' : value);
    }
    for (const child of children.flat()) {
        if (child === null || child === undefined || child === false) continue;
        el.append(child instanceof Node ? child : document.createTextNode(String(child)));
    }
    return el;
}

function isInternal(url) {
    try {
        const parsed = new URL(url, window.location.origin);
        return parsed.origin === window.location.origin;
    } catch {
        return false;
    }
}

// **gras** et [texte](lien interne) ; tout le reste reste du texte brut.
function inline(text) {
    const nodes = [];
    const pattern = /\*\*(.+?)\*\*|\[([^\]]+)\]\(([^)\s]+)\)/g;
    let last = 0;
    let match;
    while ((match = pattern.exec(text)) !== null) {
        if (match.index > last) nodes.push(text.slice(last, match.index));
        if (match[1] !== undefined) nodes.push(h('strong', {}, match[1]));
        else if (isInternal(match[3])) nodes.push(h('a', { href: match[3], class: 'font-semibold text-kh-blue-700 underline' }, match[2]));
        else nodes.push(match[2]);
        last = pattern.lastIndex;
    }
    if (last < text.length) nodes.push(text.slice(last));
    return nodes;
}

function markdown(text) {
    const box = h('div', { class: 'space-y-2' });
    let list = null;
    for (const raw of String(text).split('\n')) {
        const line = raw.trim();
        if (/^[-*•]\s+/.test(line)) {
            if (!list) {
                list = h('ul', { class: 'list-disc space-y-1 ps-5' });
                box.append(list);
            }
            list.append(h('li', {}, inline(line.replace(/^[-*•]\s+/, ''))));
            continue;
        }
        list = null;
        if (line !== '') box.append(h('p', {}, inline(line)));
    }
    return box;
}

export function createAssistant(root, launcher, config) {
    const state = { conversation: null, busy: false, unavailable: false };
    const url = (name, id, msg) => config.endpoints[name].replace('__ID__', encodeURIComponent(id ?? '')).replace('__MSG__', encodeURIComponent(msg ?? ''));

    // ---------- Structure ----------
    const log = h('div', { class: 'flex-1 space-y-4 overflow-y-auto px-4 py-4', 'aria-live': 'polite', 'aria-relevant': 'additions' });
    const counter = h('p', { class: 'mt-1 hidden text-end text-tag text-slate', 'aria-live': 'polite' });
    const input = h('textarea', {
        id: 'kh-assistant-input',
        rows: '1',
        maxlength: String(config.maxChars),
        placeholder: 'Posez votre question…',
        class: 'max-h-32 min-h-11 w-full resize-none bg-transparent px-1 py-2.5 text-body text-ink placeholder:text-slate focus:outline-none',
    });
    const send = h('button', {
        type: 'submit',
        class: 'inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-kh-blue-700 text-white transition-transform duration-200 hover:-translate-y-0.5 disabled:opacity-50 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-kh-blue-500',
        'aria-label': 'Envoyer',
    }, icon('arrowUp', 20));
    const form = h('form', { class: 'border-t border-line px-4 py-3', novalidate: true },
        h('label', { for: 'kh-assistant-input', class: 'sr-only' }, 'Votre question'),
        h('div', { class: 'flex items-end gap-2 rounded-[16px] border border-line bg-white p-1.5 ps-3 focus-within:outline-3 focus-within:outline-offset-2 focus-within:outline-kh-blue-500' }, input, send),
        counter,
    );
    const close = h('button', {
        type: 'button',
        class: 'inline-flex size-11 items-center justify-center rounded-full text-slate hover:bg-mist hover:text-ink focus-visible:outline-3 focus-visible:outline-kh-blue-500',
        'aria-label': 'Fermer l\'assistant',
    }, icon('x', 20));

    const panel = h('div', {
        role: 'dialog',
        'aria-labelledby': 'kh-assistant-title',
        hidden: true,
        class: 'kh-assistant-panel fixed inset-0 z-50 flex flex-col bg-white sm:inset-auto sm:bottom-6 sm:right-6 sm:h-[640px] sm:max-h-[calc(100dvh-3rem)] sm:w-[400px] sm:rounded-card sm:shadow-pop',
    },
        h('div', { class: 'flex items-center gap-3 border-b border-line px-4 py-3' },
            h('span', { class: 'inline-flex size-9 items-center justify-center rounded-full bg-kh-blue-50 text-kh-blue-700' }, icon('sparkles', 18)),
            h('div', { class: 'flex-1' },
                h('p', { id: 'kh-assistant-title', class: 'flex items-center gap-2 font-semibold text-ink' }, 'Assistant Kilakodon',
                    h('span', { class: 'rounded-full bg-kh-blue-50 px-2 py-0.5 text-tag font-semibold text-kh-blue-700' }, 'IA')),
            ),
            close,
        ),
        h('p', { class: 'bg-fog px-4 py-2 text-tag text-slate' }, 'Réponses générées par IA à partir de nos pages. Un conseiller peut prendre le relais.'),
        log,
        form,
    );
    root.append(panel);

    // ---------- Échanges avec le serveur ----------
    async function api(endpoint, body = {}) {
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': config.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(body),
            });
            const data = await response.json().catch(() => ({}));
            return { status: response.status, data };
        } catch {
            return { status: 0, data: {} };
        }
    }

    function scroll() {
        log.scrollTop = log.scrollHeight;
    }

    function linkButton(href, label, primary = false, external = false) {
        return h('a', {
            href,
            class: primary
                ? 'inline-flex min-h-11 items-center rounded-full bg-kh-blue-700 px-4 text-small font-semibold text-white'
                : 'inline-flex min-h-11 items-center rounded-full border border-line bg-white px-4 text-small font-semibold text-kh-blue-700 hover:border-kh-blue-200',
            target: external ? '_blank' : null,
            rel: external ? 'noopener' : null,
        }, label);
    }

    function userBubble(text) {
        log.append(h('div', { class: 'flex justify-end' },
            h('p', { class: 'max-w-[85%] whitespace-pre-line rounded-[20px] rounded-br-[6px] bg-kh-blue-700 px-4 py-2.5 text-small text-white' }, text)));
        scroll();
    }

    function notice(text) {
        log.append(h('p', { class: 'rounded-input bg-fog px-4 py-3 text-small text-ink', role: 'status' }, text));
        scroll();
    }

    function typing() {
        const dots = h('div', { class: 'flex w-fit gap-1.5 rounded-[20px] rounded-bl-[6px] bg-mist px-4 py-3.5', 'aria-label': 'L\'assistant rédige sa réponse', role: 'status' },
            [0, 1, 2].map((i) => h('span', { class: 'size-2 rounded-full bg-kh-blue-700 motion-safe:animate-pulse', style: `animation-delay:${i * 150}ms;opacity:.35` })));
        log.append(dots);
        scroll();
        return dots;
    }

    function unavailable(data) {
        state.unavailable = true;
        const links = data.fallback || {};
        const limit = ['message_limit', 'visitor_limit'].includes(data.reason);
        log.append(h('div', { class: 'rounded-card bg-fog p-5 text-center', role: 'status' },
            h('span', { class: 'mx-auto inline-flex size-10 items-center justify-center rounded-full bg-mist text-slate' }, icon('alert', 22)),
            h('p', { class: 'mt-3 text-small text-ink' }, data.message || 'L\'assistant est momentanément indisponible. Vous pouvez faire le diagnostic ou nous écrire directement.'),
            h('div', { class: 'mt-4 flex flex-wrap justify-center gap-2' },
                // Limite atteinte : un conseiller prend le relais (WhatsApp ou RDV) ; sinon diagnostic d'abord.
                limit
                    ? [links.whatsapp ? linkButton(links.whatsapp, 'WhatsApp', true, true) : null,
                        links.appointment ? linkButton(links.appointment, 'Demander un rendez-vous') : null]
                    : [links.diagnostic ? linkButton(links.diagnostic, 'Faire le diagnostic', true) : null,
                        links.whatsapp ? linkButton(links.whatsapp, 'WhatsApp', false, true) : null,
                        links.contact ? linkButton(links.contact, 'Nous écrire') : null],
            ),
            teamNote(data.team),
        ));
        input.disabled = true;
        send.disabled = true;
        scroll();
    }

    // Hors horaires, on rappelle quand l'équipe répond (texte des paramètres, jamais du modèle).
    function teamNote(team) {
        if (!team || team.open || !team.hours) return null;
        return h('p', { class: 'px-1 text-tag text-slate' }, `Notre équipe vous répond ${team.hours}.`);
    }

    function feedback(messageId) {
        const status = h('span', { class: 'text-tag text-slate', 'aria-live': 'polite' });
        const button = (rating, emoji, label) => h('button', {
            type: 'button',
            class: 'inline-flex size-9 items-center justify-center rounded-full text-base hover:bg-mist focus-visible:outline-3 focus-visible:outline-kh-blue-500',
            'aria-label': label,
            onclick: async (event) => {
                const { status: code } = await api(url('feedback', state.conversation, messageId), { rating });
                status.textContent = code === 200 ? 'Merci pour votre retour.' : '';
                event.currentTarget.parentElement.querySelectorAll('button').forEach((b) => b.setAttribute('aria-pressed', String(b === event.currentTarget)));
            },
        }, emoji);
        return h('div', { class: 'flex items-center gap-1' }, button('up', '👍', 'Réponse utile'), button('down', '👎', 'Réponse pas utile'), status);
    }

    function assistantBubble(data) {
        const message = data.message;
        const links = data.links || {};
        const block = h('div', { class: 'max-w-[92%] space-y-2' });
        block.append(h('div', { class: 'rounded-[20px] rounded-bl-[6px] bg-mist px-4 py-3 text-small text-ink' }, markdown(message.content)));

        if (message.clarifying_question) {
            block.append(h('p', { class: 'px-1 text-small font-medium text-ink' }, message.clarifying_question));
        }

        const sources = (message.citations || []).filter((c) => isInternal(c.url));
        if (sources.length) {
            block.append(h('div', { class: 'flex flex-wrap gap-2 px-1' },
                h('span', { class: 'sr-only' }, 'Sources :'),
                sources.map((c) => h('a', { href: c.url, class: 'inline-flex min-h-9 items-center rounded-full bg-kh-blue-50 px-3 text-tag font-semibold text-kh-blue-700 hover:bg-kh-blue-100' }, `${c.title} →`))));
        }

        const actions = h('div', { class: 'flex flex-wrap gap-2 px-1' });
        if (message.suggested_action === 'start_diagnostic' && links.diagnostic) {
            actions.append(linkButton(links.diagnostic, 'Faire le diagnostic gratuit', true));
            // Repli prix : un humain reste joignable (AI_ASSISTANT §4).
            if (message.error_code === 'price_guard' && links.whatsapp) {
                actions.append(linkButton(links.whatsapp, 'WhatsApp', false, true));
            }
        }
        if (message.suggested_action === 'handoff_whatsapp' && links.whatsapp) {
            const text = data.whatsapp_text || '';
            if (text) block.append(h('p', { class: 'px-1 text-tag text-slate' }, `Message prérempli : « ${text} »`));
            actions.append(linkButton(`${links.whatsapp}${text ? `?text=${encodeURIComponent(text)}` : ''}`, 'Continuer sur WhatsApp', true, true));
            if (links.appointment) actions.append(linkButton(links.appointment, 'Demander un rendez-vous'));
            if (['llm_unavailable', 'llm_error'].includes(message.error_code) && links.diagnostic) {
                actions.append(linkButton(links.diagnostic, 'Faire le diagnostic'));
            }
        }
        if (actions.childElementCount) block.append(actions);
        if (['handoff_whatsapp', 'request_appointment'].includes(message.suggested_action)) {
            const note = teamNote(data.team);
            if (note) block.append(note);
        }
        if (data.proposal) block.append(proposalCard(data.proposal));
        if (!message.error_code) block.append(feedback(message.id));

        log.append(block);
        scroll();
    }

    // ---------- Carte de confirmation (TRD §7.5) ----------
    function field(label, name, value, { type = 'text', required = false, textarea = false, options = null, hint = null } = {}) {
        const id = `kh-a-${name.replace(/\W+/g, '-')}-${Math.random().toString(36).slice(2, 7)}`;
        let control;
        if (options) {
            control = h('select', { id, name, class: 'mt-1 min-h-11 w-full rounded-input border border-line bg-white px-3 text-small' },
                Object.entries(options).map(([v, l]) => h('option', { value: v, selected: v === value }, l)));
        } else if (textarea) {
            control = h('textarea', { id, name, rows: '3', class: 'mt-1 w-full rounded-input border border-line bg-white px-3 py-2 text-small', required });
            control.value = value ?? '';
        } else {
            control = h('input', { id, name, type, class: 'mt-1 min-h-11 w-full rounded-input border border-line bg-white px-3 text-small', required, min: type === 'date' ? config.minDate : null, max: type === 'date' ? config.maxDate : null });
            control.value = value ?? '';
        }
        const hintId = hint ? `${id}-hint` : null;
        control.setAttribute('aria-describedby', [hintId, `${id}-error`].filter(Boolean).join(' '));
        return h('div', {}, h('label', { for: id, class: 'text-tag font-semibold text-ink' }, label, required ? ' *' : ''), control,
            hint ? h('p', { id: hintId, class: 'mt-1 text-tag text-slate' }, hint) : null,
            h('p', { id: `${id}-error`, class: 'mt-1 hidden text-tag text-kh-red-600', 'data-error': name }));
    }

    function checkbox(label, name, checked = false) {
        const id = `kh-a-${name}-${Math.random().toString(36).slice(2, 7)}`;
        return h('div', {},
            h('div', { class: 'flex items-start gap-2' },
                h('input', { id, name, type: 'checkbox', checked, 'aria-describedby': `${id}-error`, class: 'mt-1 size-5 shrink-0 accent-kh-blue-700' }),
                h('label', { for: id, class: 'text-tag text-ink' }, label)),
            h('p', { id: `${id}-error`, class: 'mt-1 hidden text-tag text-kh-red-600', 'data-error': name }));
    }

    function proposalCard(proposal) {
        const p = proposal.payload;
        const isBrief = proposal.type === 'propose_brief';
        const card = h('form', { class: 'space-y-3 rounded-card border-s-4 border-kh-blue-700 bg-white p-4 shadow-pop', novalidate: true });
        const fields = [];

        if (isBrief) {
            for (const [key, label] of Object.entries(proposal.labels || {})) fields.push(field(label, `brief.${key}`, p.brief?.[key]));
            fields.push(field('Résumé de votre besoin', 'brief.summary', p.brief?.summary, { textarea: true, required: true }));
        } else {
            fields.push(field('Motif du rendez-vous', 'topic', p.topic, { required: true }));
            fields.push(field('Canal souhaité', 'channel', p.channel, { options: config.channels }));
            fields.push(field('Date souhaitée', 'slots.0.date', p.slots?.[0]?.date, { type: 'date', required: true }));
            fields.push(field('Moment de la journée', 'slots.0.period', p.slots?.[0]?.period || 'morning', { options: config.periods }));
        }
        fields.push(field('Nom et prénom', 'contact.full_name', p.contact?.full_name, { required: true }));
        fields.push(field('Téléphone (WhatsApp de préférence)', 'contact.phone', p.contact?.phone, { type: 'tel', required: true, hint: 'Exemple : 74 66 64 89' }));
        fields.push(field('E-mail (facultatif)', 'contact.email', p.contact?.email, { type: 'email' }));
        fields.push(field('Entreprise (facultatif)', 'contact.company', p.contact?.company));

        const result = h('div', { role: 'status', 'aria-live': 'polite' });
        const confirm = h('button', { type: 'submit', class: 'inline-flex min-h-11 items-center rounded-full bg-kh-blue-700 px-5 text-small font-semibold text-white disabled:opacity-50' }, 'Confirmer l\'envoi');
        const cancel = h('button', { type: 'button', class: 'inline-flex min-h-11 items-center rounded-full border border-line px-5 text-small font-semibold text-kh-blue-700' }, 'Annuler');

        card.append(
            h('p', { class: 'font-semibold text-ink' }, 'Voici ce que nous allons transmettre'),
            h('p', { class: 'text-tag text-slate' }, isBrief
                ? 'Relisez et corrigez chaque champ : rien n\'est envoyé sans votre confirmation.'
                : 'Votre demande sera transmise à l\'équipe, qui vous recontactera pour fixer le créneau. Rien n\'est envoyé sans votre confirmation.'),
            ...fields,
            checkbox(config.consentProcessing || 'J\'accepte que Kilakodon Services utilise ces informations pour répondre à ma demande.', 'consent_processing', false),
            checkbox(config.consentMarketing || 'Je souhaite recevoir occasionnellement des conseils et actualités de Kilakodon Services.', 'marketing_opt_in', false),
            h('p', { class: 'text-tag text-slate' }, h('a', { href: config.privacyUrl, class: 'underline' }, 'Confidentialité')),
            h('div', { class: 'flex flex-wrap gap-2' }, confirm, cancel),
            result,
        );

        let current = proposal;

        const collect = () => {
            const data = { contact: {}, consent_processing: card.elements.consent_processing.checked, marketing_opt_in: card.elements.marketing_opt_in.checked };
            if (isBrief) data.brief = {};
            else data.slots = [{}];
            for (const el of card.querySelectorAll('input[name],select[name],textarea[name]')) {
                if (el.type === 'checkbox') continue;
                const path = el.name.split('.');
                let target = data;
                for (let i = 0; i < path.length - 1; i++) target = target[path[i]];
                target[path[path.length - 1]] = el.value.trim() === '' ? null : el.value.trim();
            }
            return data;
        };

        const showErrors = (errors = {}) => {
            card.querySelectorAll('[data-error]').forEach((el) => {
                const message = errors[el.dataset.error]?.[0];
                el.textContent = message || '';
                el.classList.toggle('hidden', !message);
                const control = card.querySelector(`[name="${CSS.escape(el.dataset.error)}"]`);
                if (control) {
                    if (message) control.setAttribute('aria-invalid', 'true');
                    else control.removeAttribute('aria-invalid');
                }
            });
            const first = Object.keys(errors)[0];
            if (first) card.querySelector(`[name="${CSS.escape(first)}"]`)?.focus();
        };

        const lock = () => {
            card.querySelectorAll('input,select,textarea,button').forEach((el) => { el.disabled = true; });
        };

        card.addEventListener('submit', async (event) => {
            event.preventDefault();
            confirm.disabled = true;
            result.textContent = '';
            showErrors();

            // Toute modification crée une nouvelle proposition figée : on confirme exactement ce qui est affiché.
            const revised = await api(url('revise', current.id), { payload: collect() });
            if (revised.status === 422) {
                showErrors(revised.data.errors || { proposal: [revised.data.message] });
                if (revised.data.errors?.proposal) result.textContent = revised.data.errors.proposal[0];
                confirm.disabled = false;
                return;
            }
            if (revised.status !== 200) {
                result.textContent = 'L\'envoi n\'a pas abouti. Réessayez ou écrivez-nous sur WhatsApp.';
                confirm.disabled = false;
                return;
            }
            current = revised.data.proposal;

            const done = await api(url('confirm', current.id), { hash: current.hash });
            if (done.data.status === 'executed') {
                lock();
                result.replaceChildren(h('div', { class: 'rounded-input bg-kh-blue-50 p-3 text-small text-ink' },
                    h('p', { class: 'font-semibold' }, done.data.message),
                    done.data.result?.url ? h('a', { href: done.data.result.url, class: 'mt-2 inline-block font-semibold text-kh-blue-700 underline' }, 'Voir mon brief (lien privé, à conserver)') : null,
                    teamNote(done.data.team)));
                return;
            }
            result.replaceChildren(h('p', { class: 'rounded-input bg-fog p-3 text-small text-kh-red-600' }, done.data.message || 'L\'envoi n\'a pas abouti : votre demande n\'a pas été enregistrée. Réessayez ou écrivez-nous sur WhatsApp.'));
            confirm.disabled = false;
        });

        cancel.addEventListener('click', async () => {
            await api(url('cancel', current.id));
            lock();
            result.replaceChildren(h('p', { class: 'text-small text-slate' }, 'Proposition annulée : rien n\'a été envoyé.'));
        });

        return card;
    }

    // ---------- Conversation ----------
    async function ensureConversation() {
        if (state.conversation) return true;
        const { status, data } = await api(config.endpoints.start, { entry_path: window.location.pathname });
        if (status === 201 && data.conversation) {
            state.conversation = data.conversation;
            return true;
        }
        if (status === 429 || data.status === 'notice') {
            notice(data.message || 'Patientez quelques secondes avant votre prochain message.');
            return false;
        }
        unavailable(data);
        return false;
    }

    async function ask(text) {
        if (state.busy || state.unavailable) return;
        text = text.trim();
        if (!text) return;

        state.busy = true;
        send.disabled = true;
        userBubble(text);
        input.value = '';
        updateCounter();
        suggestions.remove();

        const dots = typing();
        try {
            if (!(await ensureConversation())) return;
            const { status, data } = await api(url('message', state.conversation), { message: text });

            if (status === 404) {
                // Session expirée : on repart d'une nouvelle conversation au prochain message.
                state.conversation = null;
                notice('La conversation a expiré. Reposez votre question pour en démarrer une nouvelle.');
            } else if (data.status === 'ok') {
                assistantBubble(data);
            } else if (data.status === 'notice') {
                notice(data.message);
            } else {
                unavailable(data.status === 'unavailable' ? data : {});
            }
        } finally {
            dots.remove();
            state.busy = false;
            send.disabled = state.unavailable;
            if (!state.unavailable) input.focus();
        }
    }

    function updateCounter() {
        const length = input.value.length;
        counter.textContent = `${length} / ${config.maxChars}`;
        counter.classList.toggle('hidden', length <= 800);
        input.style.height = 'auto';
        input.style.height = `${Math.min(input.scrollHeight, 128)}px`;
    }

    const suggestions = h('div', { class: 'space-y-2' },
        h('p', { class: 'text-small text-slate' }, 'Bonjour ! Je réponds à partir des pages du site. Par exemple :'),
        SUGGESTIONS.map((s) => h('button', {
            type: 'button',
            class: 'block w-full rounded-input border border-line bg-white px-4 py-2.5 text-start text-small text-kh-blue-700 hover:border-kh-blue-200',
            onclick: () => ask(s),
        }, s)));
    log.append(suggestions);

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        ask(input.value);
    });
    input.addEventListener('input', updateCounter);
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            ask(input.value);
        }
    });

    // Plein écran (sous 640 px) : boîte de dialogue modale, le focus reste dans le panneau.
    const fullscreen = window.matchMedia('(max-width: 639.98px)');
    const syncModal = () => panel.setAttribute('aria-modal', fullscreen.matches ? 'true' : 'false');
    fullscreen.addEventListener?.('change', syncModal);
    syncModal();

    panel.addEventListener('keydown', (event) => {
        if (event.key !== 'Tab' || !fullscreen.matches) return;
        const focusable = [...panel.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled])')]
            .filter((el) => el.offsetParent !== null);
        if (!focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    function openPanel() {
        panel.hidden = false;
        panel.classList.remove('is-open');
        requestAnimationFrame(() => panel.classList.add('is-open'));
        launcher.hidden = true;
        launcher.setAttribute('aria-expanded', 'true');
        input.focus();
    }

    function closePanel() {
        panel.hidden = true;
        launcher.hidden = false;
        launcher.setAttribute('aria-expanded', 'false');
        launcher.focus();
    }

    close.addEventListener('click', closePanel);
    panel.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closePanel();
    });

    return { open: openPanel, close: closePanel };
}
