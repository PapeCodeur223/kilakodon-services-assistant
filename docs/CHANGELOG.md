# CHANGELOG

## [Non publié]

### Lot 0 — Socle (2026-10-26)
#### Ajouté
- Projet Laravel 12 (verrou `config.platform.php = 8.2.0`), préfixe `kh_`, locale `fr` et traductions `lang/fr`, drivers `database` (file, cache, sessions), sessions de 8 h.
- Outillage : Pest 3, Larastan (niveau 6), Pint ; script `composer kh:check`.
- Filament 4 sur `/admin` : thème bleu Kilakodon (Inter auto-hébergée, mode sombre désactivé), logo, groupes de navigation (UX §5.8), MFA par application obligatoire pour `admin`.
- Enum `UserRole` (`admin`, `sales`, `editor`), politiques `UserPolicy` et `AuditLogPolicy`, porte `manage-settings`, commande `kh:make-admin`, `DevSeeder` (3 comptes `.test`) et `SettingsSeeder`.
- Tables `settings`, `audit_logs`, `sequences`, `outbound_messages`, `analytics_events` ; service `Settings` (cache 10 min, invalidation à la sauvegarde) ; action `UpdateSettings` (versionnage des textes de consentement) ; page « Paramètres ».
- Journal d'audit : trait `Auditable` (création, modification, changement de rôle, paramètres), connexion journalisée avec `last_login_at`, IP hachée (HMAC) ; écrans « Utilisateurs » et « Journal » (lecture seule).
- Design system : tokens Tailwind v4 (UX §3), Fraunces et Inter auto-hébergées, composants `x-kh.*` (button, link-arrow, eyebrow, card, card-accent, artifact, section, badge, input, icon, halftone, logo, header, footer), layout public, `reveal.js`, header au défilement, menus `<details>` utilisables sans JS, icônes Lucide copiées.
- Logo recadré (PNG/WebP @1x/@2x), favicons et `apple-touch-icon`.
- Page `/_styleguide` (environnement local uniquement, `noindex`).
- En-têtes de sécurité (CSP avec nonce, X-Frame-Options, Referrer-Policy, Permissions-Policy, HSTS en production) ; pages d'erreur 403, 404, 419, 500, 503 stylées.
- Accueil provisoire (hero de CONTENT §2).

#### Écarts notés dans DECISIONS.md
- PHP 8.2 installé hors XAMPP ; MariaDB 10.4 en local ; tests sur MariaDB ; Fraunces `opsz` au lieu de `full` ; CSP assouplie limitée à l'admin ; MFA obligatoire pour `admin` seulement ; favicons depuis le logo complet ; navigation statique jusqu'au lot 1.

### Documentation
- 2026-10-25 — Pack de démarrage : PRD, TRD, schéma, design, plan, diagnostic, assistant, contenus, décisions, skills.

<!-- Une entrée par lot : Ajouté / Modifié / Corrigé / Écarts notés dans DECISIONS.md -->
