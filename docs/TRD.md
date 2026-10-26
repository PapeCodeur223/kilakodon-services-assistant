# TRD — Kilakodon Hub (exigences techniques)

> Complète `PRD.md`. Le schéma de base de données est dans `BACKEND_SCHEMA.md`, le design dans `UX_UI_DESIGN.md`.
> **Phase actuelle : développement local uniquement.** Le déploiement en production (LWS) est décrit au §16 pour information ; il ne doit **pas** être mis en place avant décision explicite.

---

## 1. Contraintes d'environnement

| Élément | Local (maintenant) | Production cible (plus tard) |
|---|---|---|
| PHP | **8.2.x** (aligné sur la production) | 8.2 (LWS Perso) |
| Base | MySQL 8 **ou** MariaDB 10.6+ (à aligner sur la version LWS dès qu'elle est connue) | MySQL/MariaDB LWS, une seule base, **tables préfixées `kh_`** |
| Cron | `php artisan schedule:work` | Tâche planifiée LWS chaque minute → `schedule:run` |
| SSH | — | **Indisponible sur la formule actuelle** → déploiement par GitHub Actions (build complet puis envoi SFTP/FTP) ou changement de formule |
| Processus persistants | autorisés en local | **interdits** (hébergement mutualisé) → aucune dépendance à Redis, Horizon, WebSockets ou workers permanents |
| Node | Node 20+ pour Vite (build seulement) | **Aucun Node en production** : les assets sont compilés avant le déploiement |

**Conséquences de conception :**
- Files d'attente avec le driver `database`, consommées par le scheduler (`queue:work --stop-when-empty --max-time=50`).
- Cache et sessions avec le driver `database` (ou `file`).
- Aucun streaming SSE obligatoire pour l'assistant : on renvoie la réponse complète, avec un indicateur de saisie côté client.
- Pas de fonctionnalités MySQL incompatibles avec MariaDB : pas d'index fonctionnels sur JSON, pas de colonnes générées requises, pas de `JSON_TABLE`.

## 2. Stack et versions

| Couche | Choix | Remarques |
|---|---|---|
| Framework | **Laravel 12.x** | Laravel 13 exige PHP ≥ 8.3 : **ne pas** l'utiliser tant que la production est en 8.2 |
| Verrou PHP | `composer.json` → `"config": { "platform": { "php": "8.2.0" } }` | **Obligatoire** : empêche toute dépendance incompatible avec la production |
| Back-office | **Filament** (dernière version majeure compatible Laravel 12 + PHP 8.2, v4 attendue) | Panneau unique `/admin` |
| Interactivité | **Livewire** (version requise par Filament) + **Alpine.js** | Livewire **uniquement** sur diagnostic, brief, Démo Lab et admin |
| CSS | **Tailwind CSS v4** via Vite, tokens dans `@theme` (voir `UX_UI_DESIGN.md`) | Aucun CDN |
| Polices | `@fontsource-variable/fraunces` et `@fontsource-variable/inter`, auto-hébergées et sous-ensemble latin | `font-display: swap` + préchargement de la police d'affichage |
| Tests | **Pest** (+ plugin Laravel) | Couverture obligatoire des moteurs (diagnostic, références, plafonds IA, permissions) |
| PDF | `barryvdh/laravel-dompdf` | Gabarit Blade dédié, sans JS |
| Téléphones | `propaganistas/laravel-phone` | Normalisation E.164, pays par défaut `ML` |
| SEO | `spatie/laravel-sitemap` + composants Blade maison pour meta/OG/JSON-LD | — |
| Anti-spam | `spatie/laravel-honeypot` + `RateLimiter` Laravel | Pas de captcha tiers au MVP |
| Icônes | SVG Lucide (licence ISC) copiés dans `resources/svg/lucide/` et rendus par `x-kh.icon` | Pas de paquet ni de police d'icônes |
| Markdown | `league/commonmark` (via `Str::markdown`), HTML **désactivé** dans le contenu | — |
| IA | API Anthropic Messages via `Illuminate\Support\Facades\Http` | Pas de SDK obligatoire ; client encapsulé (voir §7) |
| Qualité | Laravel Pint (PSR-12 / preset laravel), Larastan niveau 6 | Exécutés avant chaque fin de lot |

Toute dépendance ajoutée en dehors de cette liste doit être justifiée dans `docs/DECISIONS.md`.

## 3. Architecture

**Monolithe modulaire Laravel.** Le code métier vit dans `app/Domain/<Module>` ; les contrôleurs, composants Livewire et ressources Filament restent minces et appellent des **Actions** (une classe = une opération métier, méthode `handle()`).

```
app/
  Domain/
    Content/        # Service, Sector, CaseStudy, Article, Faq, Page, revisions, publication
    Diagnostic/     # Template, Question, Rule, Session, Engine (ConditionEvaluator, RuleEngine, MaturityScorer)
    Brief/          # Brief, BriefVersion, ReferenceGenerator, BriefPdf, AccessToken
    Crm/            # Contact, Company, Lead, StageHistory, Activity, Appointment, DuplicateFinder
    Assistant/      # KnowledgeDocument/Chunk, Retriever, PromptBuilder, LlmClient (Anthropic|Fake), Guard (caps), ActionProposal
    DemoLab/        # Workspace, Product, Sale, StockMovement, Seeder, Purger
    Analytics/      # Event recorder, funnel queries
    Privacy/        # Consent, PrivacyRequest, Anonymizer, Exporter
    Platform/       # Settings, AuditLog, Sequences, OutboundMessage, enums partagés
  Http/Controllers/  (Public/*, Api/*)
  Livewire/          (Diagnostic/*, Brief/*, DemoLab/*)
  Filament/          (Resources/*, Pages/*, Widgets/*)
  Enums/             (ré-exports si besoin)
resources/views/
  layouts/ components/ (x-kh.*) pages/ diagnostic/ brief/ demo-lab/ pdf/ emails/
resources/css/app.css   (tokens @theme + couches)
resources/js/app.js     (reveal, header, assistant widget Alpine)
database/seeders/content/  (seeders générés depuis CONTENT.md et DIAGNOSTIC_SPEC.md)
tests/Unit/  tests/Feature/  tests/Ai/
```

**Conventions :**
- Identifiants de code, tables, colonnes et valeurs d'enum **en anglais** ; libellés UI **en français** via les méthodes `label()` des enums PHP natifs (`enum LeadStage: string { case New = 'new'; … public function label(): string }`).
- Les URL publiques sont en français (`/solutions`, `/realisations`).
- Les montants sont des **entiers en FCFA** (`*_xof`, `unsignedBigInteger`). Pas de float pour l'argent.
- Les dates sont stockées en UTC et affichées en `Africa/Bamako` (UTC+0, sans heure d'été, mais on reste explicite).
- Les clés publiques exposées sont des **ULID** ou des tokens aléatoires ; jamais d'ID auto-incrémenté dans une URL publique.

## 4. Stratégie de rendu et JavaScript

| Type de page | Rendu | JS |
|---|---|---|
| Accueil, solutions, secteurs, réalisations, méthode, ressources, légales | Blade SSR, cache HTTP court | `app.js` (≤ 15 Ko gz) : reveal au scroll, header, menu mobile, widget assistant chargé **à l'ouverture** |
| Diagnostic, brief | Livewire | Livewire + Alpine |
| Démo Lab | Livewire | Livewire + Alpine |
| Admin | Filament | Filament |

**Budgets (mobile, 4G simulée) :** LCP < 2,5 s ; CLS < 0,05 ; INP < 200 ms ; poids de l'accueil < 600 Ko hors polices mises en cache ; JS de l'accueil ≤ 40 Ko gz. Images en AVIF/WebP avec `width`/`height` explicites et `loading="lazy"` hors hero.

## 5. Routes

### 5.1 Public
| Méthode | URL | Nom | Rendu |
|---|---|---|---|
| GET | `/` | `home` | Blade |
| GET | `/solutions` | `solutions.index` | Blade |
| GET | `/solutions/{service:slug}` | `solutions.show` | Blade |
| GET | `/secteurs/{sector:slug}` | `sectors.show` | Blade |
| GET | `/realisations`, `/realisations/{caseStudy:slug}` | `cases.*` | Blade |
| GET | `/demo-lab` | `lab.index` | Blade |
| GET | `/demo-lab/boutique` | `lab.retail` | Livewire (crée ou reprend l'espace via cookie signé) |
| GET | `/demo-lab/{slug}` | `lab.guided` | Blade (démos guidées) |
| GET | `/diagnostic` | `diagnostic.start` | Livewire (`?secteur=retail` pré-remplit) |
| GET | `/diagnostic/reprendre/{token}` | `diagnostic.resume` | Livewire |
| GET | `/brief/{token}` | `brief.show` | Livewire (correction + validation) |
| GET | `/brief/{token}/pdf` | `brief.pdf` | PDF |
| GET | `/methode`, `/a-propos`, `/contact`, `/rendez-vous` | — | Blade + formulaires |
| POST | `/contact`, `/rendez-vous` | — | Contrôleurs (honeypot + throttle) |
| GET | `/ressources`, `/ressources/{article:slug}` | `articles.*` | Blade |
| GET | `/mentions-legales`, `/confidentialite`, `/cookies` | `legal.*` | Blade (contenu `pages`) |
| GET | `/sitemap.xml`, `/robots.txt` | — | Générés |

### 5.2 API interne (JSON, même origine, CSRF + throttle)
| Méthode | URL | Usage |
|---|---|---|
| POST | `/api/assistant/conversations` | Démarre une conversation (renvoie un ULID) |
| POST | `/api/assistant/conversations/{ulid}/messages` | Envoie un message et reçoit la réponse complète |
| POST | `/api/assistant/actions/{ulid}/confirm` | Confirme une action proposée (arguments figés, hash vérifié) |
| POST | `/api/assistant/actions/{ulid}/cancel` | Annule l'action |
| POST | `/api/assistant/messages/{id}/feedback` | Retour utile / pas utile |
| POST | `/api/events` | Événements de mesure (liste blanche, voir §9) |

### 5.3 Redirections 301 de l'ancien site
| Ancienne URL | Nouvelle URL |
|---|---|
| `/index.php` | `/` |
| `/services.php` | `/solutions` |
| `/shop.php` | `/demo-lab` |
| `/portofolio.php` | `/realisations` |
| `/contact.php` | `/contact` |
| `/pages/produit_detail.php?id=1` | `/secteurs/ecoles-formation` [À VALIDER : vérifier quel produit porte l'id 1] |
| `/pages/produit_detail.php?id=2` | `/secteurs/boulangeries-restauration` [À VALIDER] |
| `/client/connexion.php`, `/client/mon_compte.php` | `/contact` [À VALIDER : sort de l'ancien espace client] |

Les redirections sont implémentées dans un middleware dédié `LegacyRedirects` (table en config) et couvertes par un test.

## 6. Moteur de diagnostic

Il est entièrement déterministe, en PHP pur et testé unitairement. Il ne dépend ni de Laravel ni de l'IA.

- **`ConditionEvaluator`** évalue une condition JSON :
  ```json
  { "all": [ {"q": "sector", "op": "eq", "v": "retail"} ],
    "any": [ {"q": "pains", "op": "contains", "v": "stock_gaps"}, {"q": "sites", "op": "in", "v": ["2_3","4_10","10_plus"]} ] }
  ```
  Opérateurs : `eq`, `neq`, `in`, `not_in`, `contains`, `contains_any`, `contains_none`, `answered`, `not_answered`, `is_unknown`. Les blocs `all`/`any`/`none` peuvent être imbriqués. Une condition vide vaut `true`.
- **Visibilité** : une question est visible si `show_if` est vrai. Après chaque réponse, on recalcule les questions visibles, puis on **supprime** les réponses des questions devenues invisibles.
- **`RuleEngine`** évalue toutes les règles actives par `priority` et accumule leurs effets :
  - `recommend` : `{module, weight}`. Les poids s'additionnent par module ; on garde les modules ≥ 3 points, au maximum 5, triés par poids.
  - `pain` : `{key}`, pour les priorités affichées (maximum 3, ordonnées par fréquence puis priorité de règle).
  - `clarify` : `{key}`, pour les points à clarifier.
  - Chaque effet conserve `rule_key` et les `question_key` qui l'ont déclenché : c'est le contenu de « Pourquoi ? ».
- **`MaturityScorer`** calcule un score depuis les outils actuels (barème dans `DIAGNOSTIC_SPEC.md`) et le traduit en niveau 1–4 avec libellé et explication. Il n'y a **pas** de score présenté comme scientifique.
- **Résultat** : une instance de `DiagnosticOutcome` (DTO) est sérialisée dans `diagnostic_results`, avec la version du modèle de questions.
- **Résumé IA facultatif** : après le calcul, un job peut demander à l'IA une reformulation de 80 à 120 mots du résultat **à partir du DTO uniquement**. En cas d'échec, on utilise un résumé gabarit (Blade). Le résumé IA est marqué « rédigé automatiquement ».
- **Tests** : chaque règle de `DIAGNOSTIC_SPEC.md` a au moins un test positif et un test négatif ; un test vérifie que des réponses identiques produisent un résultat identique.

## 7. Assistant IA

### 7.1 Base de connaissances
- Sources : services, secteurs, FAQ, pages (méthode, à propos), articles, réalisations publiées, et documents manuels (`source_type = manual`).
- À la publication ou à l'approbation, un job **`RebuildKnowledgeForSource`** découpe le contenu en fragments de 120 à 400 mots, en coupant sur les titres. Chaque fragment conserve `heading`, `content`, `url` et `document_version`. Les anciens fragments de la source sont supprimés **dans la même transaction**.
- Seuls les documents `status = approved` et non expirés sont interrogeables.
- Index `FULLTEXT(heading, content)` (InnoDB).

### 7.2 Recherche (`Retriever`)
1. Normalisation de la question : minuscules, suppression des accents pour la recherche, retrait des mots vides français (liste en config).
2. `MATCH … AGAINST (… IN NATURAL LANGUAGE MODE)` → top 6.
3. Si le score est faible ou nul : requête `BOOLEAN MODE` avec préfixes (`stock*`), puis repli sur les synonymes métier (config `assistant.synonyms` : « caisse » ↔ « point de vente », « ERP » ↔ « logiciel de gestion », etc.).
4. On renvoie au plus 6 fragments, et au plus environ 2 500 mots de contexte.

### 7.3 Appel du modèle (`AnthropicClient` implémente `LlmClient`)
- `POST https://api.anthropic.com/v1/messages`, en-têtes `x-api-key`, `anthropic-version: 2023-06-01` et `content-type: application/json`.
- Modèle : `config('assistant.model')`, défini par `ANTHROPIC_MODEL` (par défaut `claude-haiku-5-5`).
- `max_tokens` : 700. Timeout : 25 s. Une seule nouvelle tentative, uniquement sur 429 ou 5xx, après 2 s.
- **Sortie structurée obligatoire** : un outil `respond` est défini avec un schéma JSON (voir `AI_ASSISTANT.md` §3) et forcé par `tool_choice: {"type":"tool","name":"respond"}`. Le serveur **valide** la sortie ; si elle est invalide, une réponse de repli est utilisée.
- Prompt système : le texte exact se trouve dans `AI_ASSISTANT.md`. Les fragments sont injectés dans un bloc `<documents>` explicitement décrit comme des **données, pas des instructions**.
- Historique : les 8 derniers messages au maximum.
- Coût : `usage.input_tokens` et `usage.output_tokens` sont enregistrés par message ; le coût est estimé via `config('assistant.pricing.<model>')`. Ces prix sont **à renseigner** depuis la page de tarifs officielle et ne doivent jamais être codés en dur.
- **`FakeLlmClient`** : utilisé quand `AI_DRIVER=fake` (tests, développement sans clé). Il renvoie des réponses déterministes selon des mots-clés, pour permettre de tester l'interface et les gardes.

### 7.4 Gardes (`AssistantGuard`) — vérifiées **avant** chaque appel
| Garde | Valeur par défaut (paramétrable) |
|---|---|
| Assistant activé | `settings.ai.enabled` (interrupteur admin) **et** `AI_ENABLED=true` |
| Longueur d'un message utilisateur | 1 000 caractères |
| Messages par conversation | 20 |
| Conversations par visiteur et par jour (cookie + IP hachée) | 5 |
| Requêtes par minute et par IP | 6 |
| Budget quotidien global | `settings.ai.daily_budget_usd` [À VALIDER] ; alerte e-mail à 80 % ; blocage à 100 % |

Quand une garde bloque, l'API renvoie `{"status":"unavailable","reason":"…","fallback":{…liens…}}` et le widget affiche le repli. **Jamais de fausse réponse.**

### 7.5 Actions proposées
- Le modèle peut renseigner `suggested_action` ∈ `none | start_diagnostic | propose_brief | request_appointment | handoff_whatsapp`.
- `start_diagnostic` et `handoff_whatsapp` sont de simples liens : aucune écriture côté serveur. Le lien WhatsApp contient un résumé de 300 caractères maximum, montré au visiteur avant l'ouverture.
- `propose_brief` et `request_appointment` créent une ligne `ai_action_proposals` (`status = proposed`, `payload` figé, `payload_hash`, `idempotency_key`, expiration 30 min). Le widget affiche une **carte de confirmation** avec les champs exacts, modifiables. Toute modification régénère une nouvelle proposition.
- La confirmation (`/confirm`) vérifie le hash, l'expiration et le statut, puis exécute **la même Action métier** que le formulaire classique (`CreateLeadFromBrief`, `RequestAppointment`) dans une transaction. Elle enregistre `result_ref`. Le résultat réel est renvoyé ; en cas d'échec, `failed` est renvoyé avec un message.
- Un double clic (même `idempotency_key`) renvoie le résultat existant.

### 7.6 Sécurité de l'assistant
- Les instructions système, les notes internes et les données d'autres visiteurs ne sont jamais transmises au modèle.
- Le modèle n'a accès à aucune base, aucun outil d'écriture ni aucune recherche web.
- Les contenus récupérés sont échappés et encadrés. Le rendu de la réponse côté client se fait en Markdown restreint (gras, listes, liens internes au domaine uniquement) ; tout HTML est échappé.
- Les liens cités sont filtrés : seuls les chemins internes connus sont rendus.

## 8. Démo Lab

- Espace par visiteur : `demo_workspaces` (ULID et token haché dans un cookie signé `kh_lab`, durée 24 h). À la création, un seeder clone un jeu fictif d'environ 25 produits, 5 clients et 30 ventes réparties sur 14 jours.
- Toutes les tables `demo_*` portent `workspace_id`. Un **global scope** obligatoire (`BelongsToWorkspace`) filtre chaque requête ; un test vérifie qu'un espace ne voit jamais les données d'un autre.
- Aucune notification, aucun e-mail et aucune intégration externe : le Démo Lab utilise des modèles et des actions **séparés** du CRM.
- Limites : 3 espaces par IP et par jour, 500 actions par espace. Purge planifiée toutes les heures (`expires_at < now()`).
- La facture fictive est rendue en HTML imprimable (et non en PDF) avec un filigrane « DÉMO — DOCUMENT FICTIF ».

## 9. Mesure (first-party)

Table `analytics_events`. Le client envoie via `navigator.sendBeacon('/api/events')` ; le serveur n'accepte que la **liste blanche** :

| Événement | Propriétés autorisées |
|---|---|
| `page_view` | `path`, `referrer_host`, `utm_*` |
| `cta_click` | `cta` (id), `path` |
| `diagnostic_started` | `entry` (`home`, `solution:<slug>`, `sector:<slug>`, `lab`, `assistant`) |
| `diagnostic_step_completed` | `step` |
| `diagnostic_completed` | `template_version`, `maturity_level` |
| `brief_validated` | `modules_count` |
| `whatsapp_click` | `context` (`header`, `brief`, `assistant`, `lab`, `solution:<slug>`) |
| `appointment_requested` | `channel` |
| `contact_submitted` | — |
| `assistant_opened`, `assistant_message`, `assistant_handoff`, `assistant_unavailable` | `reason` pour ce dernier |
| `lab_started`, `lab_action` | `action` (`sale`, `restock`, `invoice`, `alert_seen`) |

Les événements serveur (`lead_created`, `lead_stage_changed`, `lead_won`) sont écrits par les Actions. Le visiteur est identifié par `visitor_key` (UUID dans un cookie first-party de 13 mois, uniquement si la mesure d'audience est acceptée ; sinon les événements sont **agrégés sans visitor_key**). Aucune IP brute ni aucune coordonnée n'est stockée dans les événements. [À VALIDER : le régime exact du consentement pour la mesure d'audience]

## 10. Files d'attente et tâches planifiées

`routes/console.php` :

| Tâche | Fréquence |
|---|---|
| `queue:work --stop-when-empty --max-time=50 --tries=3` | chaque minute (`withoutOverlapping`) |
| `kh:lab-purge` | toutes les heures |
| `kh:diagnostic-purge` (sessions anonymes > 30 j) | quotidienne 02:00 |
| `kh:conversations-purge` (non liées > 90 j) | quotidienne 02:15 |
| `kh:leads-unattended` (alerte leads sans activité > 24 h ouvrées) | toutes les heures, de 08:00 à 18:00 |
| `kh:ai-budget-check` | toutes les 15 min |
| `sitemap:generate` (commande maison) | quotidienne |

Jobs : `SendOutboundMessage`, `RebuildKnowledgeForSource`, `GenerateDiagnosticSummary`, `GenerateBriefPdf`. Chaque job est **idempotent**.

## 11. Sécurité

- Authentification Filament, mots de passe ≥ 12 caractères, **MFA par application** obligatoire pour `admin` (fonctionnalité MFA de Filament), sessions de 8 h, limitation des tentatives de connexion.
- Politiques (`Policy`) par ressource et par rôle, testées. Vérification côté serveur systématique.
- Tokens publics (brief, reprise, Démo Lab) : 32 octets aléatoires, encodés en base64url dans l'URL, stockés en **SHA-256**, comparés en temps constant, expirables et révocables.
- En-têtes : `Content-Security-Policy` (scripts `self` et nonce Livewire), `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` minimal, HSTS en production.
- Validation via Form Requests ; échappement Blade par défaut ; aucune entrée utilisateur rendue en `{!! !!}`.
- Limitation de débit : contact et RDV (5/h/IP), diagnostic (20 sessions/j/IP), assistant (§7.4), Démo Lab (§8).
- IP stockées **hachées** (`hash_hmac('sha256', ip, APP_KEY)`) pour les limites et l'audit.
- Secrets uniquement dans `.env` : `.env` n'est jamais versionné et `.env.example` est tenu à jour.
- Journal d'audit : connexions admin, changements de rôle, publications, fusions, exports, anonymisations, changements de paramètres IA.

## 12. E-mails

- Local : `MAIL_MAILER=log` (ou Mailpit si disponible). Production : SMTP LWS [À VALIDER].
- Modèles Markdown Laravel à l'identité Kilakodon. Aucune donnée sensible dans l'objet.
- Chaque envoi passe par `outbound_messages` (statut, tentatives, dernière erreur) pour rester visible et relançable depuis l'admin, sans rejouer l'action métier.

## 13. Tests et qualité

| Niveau | Contenu minimal |
|---|---|
| Unitaires | `ConditionEvaluator`, `RuleEngine`, `MaturityScorer`, `ReferenceGenerator` (concurrence), `AssistantGuard`, `Retriever` (sur une base de test), `DuplicateFinder`, normalisation des téléphones |
| Fonctionnels | Parcours P1 complet ; reprise par token ; token expiré ; double soumission ; politiques des 3 rôles ; publication bloquée d'une réalisation non autorisée ; redirections legacy ; isolation du Démo Lab ; confirmation et annulation d'une action IA ; repli IA désactivée ; purge |
| IA | `tests/Ai/cases.yaml` (≥ 60 cas, voir `AI_ASSISTANT.md`), commande `php artisan kh:ai-eval` : contrôles automatiques (structure, citations valides, absence de montants non sourcés, refus attendus) et export CSV pour la revue humaine |
| Visuel et performance | Lighthouse CLI (`npx lighthouse`) sur accueil, solution et diagnostic en fin de lot 1 et 6 |
| Statique | Pint, Larastan niveau 6 |

Commande de fin de lot : `composer kh:check` = `pint --test && phpstan && pest`.

## 14. Configuration (`.env.example`)

```dotenv
APP_NAME="Kilakodon Services"
APP_ENV=local
APP_URL=http://kilakodon-hub.test
APP_TIMEZONE=UTC
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr
KH_DISPLAY_TIMEZONE=Africa/Bamako

DB_CONNECTION=mysql
DB_PREFIX=kh_

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
MAIL_MAILER=log

AI_ENABLED=true
AI_DRIVER=fake            # fake | anthropic
ANTHROPIC_API_KEY=
ANTHROPIC_MODEL=claude-haiku-5-5

KH_WHATSAPP_PRIMARY=+22374666489
KH_WHATSAPP_SECONDARY=+22397462839
KH_CONTACT_EMAIL=kilakodon@gmail.com
```

`config/database.php` : `'prefix' => env('DB_PREFIX', '')` sur la connexion MySQL.

## 15. Environnement de développement local

- Windows : **Laravel Herd** ou **Laragon**, avec PHP 8.2 sélectionné. macOS : Herd. Linux : PHP 8.2 natif ou Sail (image PHP 8.2).
- Composer 2, Node 20+, MySQL/MariaDB local.
- Démarrage : `composer install && npm install && cp .env.example .env && php artisan key:generate && php artisan migrate --seed && npm run dev`, puis `php artisan schedule:work` dans un second terminal.
- `php artisan kh:make-admin` crée le premier administrateur de façon interactive.

## 16. Production (pour information — NE PAS mettre en place sans décision)

- Dépôt GitHub privé. Workflow `deploy.yml` déclenché manuellement : `composer install --no-dev --optimize-autoloader` (PHP 8.2), `npm ci && npm run build`, puis envoi par SFTP/FTP vers LWS (dossier hors `public_html`, et `public/` mappé sur la racine web ou via un `index.php` relais) [À VALIDER selon la structure LWS].
- Sans SSH, les migrations sont lancées par une route d'administration protégée et à usage unique, **ou** par une tâche cron temporaire. Option préférée : passer à une formule LWS avec SSH.
- Préproduction : `staging.kilakodon-services.com`, en `noindex` et protégée par mot de passe.
