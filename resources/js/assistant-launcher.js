// Lanceur de l'assistant (UX §4.3) : visible seulement avec JavaScript ; le panneau est un module
// séparé, importé à la première ouverture (aucun coût pour les visiteurs qui ne l'utilisent pas).
export function initAssistantLauncher() {
    const root = document.querySelector('[data-assistant]');
    const launcher = root?.querySelector('[data-assistant-launcher]');
    if (!root || !launcher) return;

    launcher.hidden = false;
    let widget = null;

    launcher.addEventListener('click', async () => {
        launcher.disabled = true;
        try {
            if (!widget) {
                const { createAssistant } = await import('./assistant/widget.js');
                widget = createAssistant(root, launcher, JSON.parse(root.dataset.config || '{}'));
            }
            widget.open();
            window.khTrack?.('assistant_opened');
        } catch {
            // Module introuvable (réseau coupé) : on renvoie vers le diagnostic plutôt que de ne rien faire.
            window.location.href = '/diagnostic';
        } finally {
            launcher.disabled = false;
        }
    });
}
