# CHANGELOG

## [Non publié]

### Lot 1 — Site public, contenus et SEO (2026-10-26)
#### Ajouté
- Contenus : tables `services`, `sectors`, `case_studies`, `articles`, `faqs`, `pages`, `content_revisions` ; traits `Publishable` (verrou de publication) et `HasRevisions` ; actions `PublishContent` (admin, refus si [À VALIDER] ou réalisation non autorisée), `ChangeContentStatus`, `RestoreRevision`, `AttestCaseStudyAuthorization` ; politique `ContentPolicy`.
- `ContentSeeder` depuis CONTENT.md (brouillon si [À VALIDER], articles toujours en brouillon) et commande `kh:content-audit` (`--all`).
- Filament : Solutions, Secteurs, Réalisations (attestation d'autorisation), Articles, FAQ, Pages ; historique des versions avec restauration ; aperçu ; liste des leads (lecture).
- CRM de base : `companies`, `contacts`, `consents`, `leads` (colonnes complètes) ; `CreateLeadFromContact` idempotent, consentements séparés et versionnés, e-mails mis en file.
- Pages publiques : accueil complet (UX §5.1), solutions, secteurs, réalisations, ressources, méthode, à propos, légales (sommaire ancré), contact (honeypot, 5 envois/h/IP) ; prévisualisation des brouillons pour l'équipe ; navigation limitée aux contenus publiés.
- `WhatsAppLink` contextuel (solution, brief, démo, assistant ≤ 300 caractères).
- SEO : `x-kh.seo` (title, description, canonical, Open Graph, Twitter), JSON-LD (Organization/LocalBusiness, Service, FAQPage, Article, BreadcrumbList), image de partage, `sitemap.xml` (+ `sitemap:generate` quotidien), `robots.txt`, `X-Robots-Tag` sur les espaces privés, capture des UTM.
- Middleware `LegacyRedirects` (TRD §5.3).
- Composants : breadcrumb, faq, markdown, textarea, select, checkbox, cta-band, page-hero, status-marker ; préchargement de Fraunces.

#### Modifié
- Flottement continu des artefacts réservé au desktop (performance mobile).
- `SettingsSeeder` : IA activée avec budget de 1 USD en local uniquement (BACKEND_SCHEMA §9).

#### Écarts notés dans DECISIONS.md
- Solutions et pages éditoriales en brouillon (contiennent [À VALIDER]) ; textes de l'accueil en fichier ; colonnes `sectors.icon` et `faqs.on_home` ; secteurs 4.4–4.6 et articles rédigés pour relecture ; e-mails en file jusqu'au lot 3 ; mesure Lighthouse incomplète sur ce poste.

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
