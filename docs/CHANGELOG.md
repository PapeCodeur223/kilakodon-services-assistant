# CHANGELOG

## [Non publié]

### Lot 5 — Démo Lab (2026-11-17, en cours de validation)
#### Ajouté
- Tables `demo_*` isolées par `workspace_id` (ULID, cascade) et global scope obligatoire `BelongsToWorkspace` (sans espace courant : aucune donnée, aucune écriture) ; tests d’isolation et d’architecture.
- Espace par visiteur : cookie chiffré `kh_lab` (token haché en base), 24 h, 3 espaces par IP sur 24 h, 500 actions par espace ; `kh:lab-purge` toutes les heures.
- Jeu fictif : 25 produits, 5 clients, 30 ventes sur 14 jours, prix FCFA étiquetés « Démo ».
- Actions `RecordDemoSale` (transaction, prix lus en base, alerte au franchissement du seuil), `RestockDemoProduct`, `AdjustDemoStock`, missions du guide.
- Démo « Boutique » (Livewire) : Caisse (grille tactile, panier, Espèces / Mobile Money / Crédit), Stock (réassort, inventaire, alertes), Ventes (factures), Tableau de bord (ventes 7 jours en SVG, meilleurs produits, alertes) ; toast d’alerte ; guide des 3 missions et carte de conversion après 2 missions.
- Facture fictive HTML imprimable avec filigrane « DÉMO — DOCUMENT FICTIF ».
- Pages `/demo-lab`, démos guidées « Gestion scolaire » et « Boulangerie » (« bientôt disponible »), page de limite atteinte.
- LAB-03 : diagnostic pré-rempli `secteur=retail&entry=lab:retail` ; le lead prend le type « Démo Lab » et la source `lab:retail`.

### Clôture du lot 4 (2026-11-16)
- 10 réalisations publiées (autorisation client accordée) et exportées dans les seeds ; le seeder publie une réalisation seulement si l'autorisation est accordée (D-024).
- prepaconcours.ml : mention « la première plateforme » retirée.
- Accueil : toutes les FAQ cochées « accueil » sont affichées (12), sans limite.
- Évaluation réelle Claude Haiku 5.5 : 62/69 cas réussis, écart sur 3 cas critiques de formulation accepté par la direction.

### Réalisations, budget IA et sécurité (2026-11-16)
- Pages **Réalisations** refondues : filtres par secteur sans JavaScript, fiche à la une, cartes avec visuel du secteur et livrables ; fiche détaillée avec repères, récit en frise (contexte, défi, intervention), livrables, solutions utilisées et autres réalisations. Composant `x-kh.case-card`.
- IA : Claude Haiku 5.5, tarifs officiels saisis, budget quotidien 0,50 $ (D-023, Q-009 tranchée).
- Sécurité : Laravel 12.72 et Symfony 7.4.23 (commit séparé), `composer audit` sans vulnérabilité.

### Pages, assistant et réalisations (2026-11-16)
- Pages **À propos** (repères, histoire et conviction, équipe, domaines d'expertise, mission), **Contact** (canaux directs, formulaire en carte, « ce qui se passe ensuite ») et **Rendez-vous** (parcours en 3 étapes, formulaire numéroté, conseils de préparation) refondues dans le style de la page Méthode ; composant `x-kh.next-steps` ; icônes Lucide ajoutées (même version 0.460.0).
- Assistant : échanges courants à réponse fixe sans appel au modèle (`SmallTalk`, D-021), prompt `sys-v3`, 5 cas ajoutés au corpus, tests unitaires et API.
- 10 réalisations en brouillon à relire et autoriser (D-022), seedées depuis `database/seeders/content/case-studies.php`.

### Contenus validés et identité de l'assistant (2026-11-16)
- Contenus validés par la direction exportés dans les fichiers de seed (solutions, FAQ, Méthode, À propos, pages légales) : ils seront identiques en production (D-020).
- Assistant renommé **Kilakodon Assistant** ; prompt `sys-v2` avec section IDENTITÉ et deux cas critiques au corpus (D-019).
- Nouveau logo Moov Money (marges rognées, affiché plus grand dans le pied de page).

### Lot 4 — Assistant IA (2026-11-16, validé)
#### Ajouté
- Tables de l'assistant (`knowledge_documents`, `knowledge_chunks` avec index FULLTEXT, `conversations`, `messages`, `ai_action_proposals`, `ai_feedback`, `ai_usage_daily`), modèles et enums.
- Base de connaissances : découpage en fragments (120–400 mots), reconstruction automatique à chaque modification d'un contenu publié (`RebuildKnowledgeForSource`), `KnowledgeSeeder`, documents manuels, approbation et retrait par un admin.
- `Retriever` (FULLTEXT naturel, booléen, synonymes métier, repli par termes, contexte plafonné).
- Clients `FakeLlmClient` (déterministe, sans réseau) et `AnthropicClient` (outil `respond` imposé, 25 s, une nouvelle tentative sur 429/5xx) ; `PromptBuilder` (`sys-v1`) ; `ResponseValidator` (schéma, citations limitées aux fragments fournis, filtre des montants, brouillon de brief sans champ inventé).
- `AssistantGuard` : interrupteur (paramètre + `AI_ENABLED`), longueur, messages par conversation, conversations par visiteur et par jour, débit par IP, budget quotidien ; `kh:ai-budget-check` (alerte e-mail à 80 %, toutes les 15 min).
- API JSON `/api/assistant/*` (ouverture, messages, propositions : modification, confirmation, annulation ; retours) ; propositions figées (payload, hash, idempotence, 30 min) exécutées par les mêmes Actions que les formulaires (`CreateBriefFromAssistant` + `CreateLeadFromBrief`, `RequestAppointment`).
- Widget public chargé à la demande : lanceur « Une question ? », sources, 👍/👎, carte de confirmation, états indisponible et limite ; absent du site quand l'assistant est coupé.
- Filament (groupe Assistant) : Base de connaissances, Conversations (le commercial ne voit que ses leads), Retours à revoir, Usage (jour, mois, 31 jours).
- Corpus `tests/Ai/cases.yaml` (62 cas) et `kh:ai-eval` (pilote factice en continu, API réelle en manuel avec confirmation) avec rapport CSV.

#### Modifié
- Page privée et PDF du brief : brief sans recommandation (origine assistant) pris en charge.
- `AppointmentRequest` : source du lead et origine du consentement paramétrables ; `BusinessHours::isOpen()`.

#### Écarts notés dans DECISIONS.md
- Assistant coupé avec l'API réelle tant que budget et tarif ne sont pas renseignés ; widget sans Alpine (CSP) ; route `revise` ; URL des retours sans identifiant seul ; brief assistant sans recommandation ; `symfony/yaml` (dev) pour le corpus.
### Lot 3 — CRM, rendez-vous et notifications (2026-10-29)
#### Ajouté
- Tables `lead_stage_history`, `activities`, `appointments`, `privacy_requests` ; enums `ActivityType`, `AppointmentStatus`, `AppointmentChannel`, `SlotPeriod`, `LostReason`, `PrivacyRequestType|Status` ; carte des transitions de `LeadStage`.
- Actions : `ChangeLeadStage` (transitions, motif obligatoire pour perdu et en pause, historique, activité, audit), `AssignLead`, `LogActivity` (+ tâche faite), `RequestAppointment` (idempotent, rattachement au lead du brief), `ConfirmAppointment` (+ annuler, réalisé, absent), `MergeContacts` (journalisé), `RecordConsent`, `AlertUnattendedLeads` (`kh:leads-unattended`, toutes les heures de 8 h à 18 h).
- File d'envoi : `QueueOutboundMessage`, job `SendOutboundMessage` (idempotent, `failed` puis `abandoned`), `ResendOutboundMessage` ; modèles e-mail Markdown à l'identité Kilakodon (nouveau lead, accusés contact, brief et RDV, RDV confirmé, leads en attente).
- Filament : leads (liste « À traiter », **Kanban** glisser-déposer avec modale de motif, fiche complète : suivi, contact et doublons, qualification, brief et réponses du diagnostic, activités, rendez-vous, historique), contacts (doublons, fusion), entreprises, rendez-vous (confirmer, reprogrammer, annuler, réalisé, absent), « Envois » (renvoyer) ; widgets du tableau de bord (indicateurs, leads à traiter, RDV à confirmer).
- Page publique `/rendez-vous` (sans JavaScript requis, honeypot, 5 envois/h/IP) ; lien depuis le brief et le pied de page ; sitemap.
- Habillage « pro » de l'administration (thème Filament aux couleurs et typographies Kilakodon, écran de connexion de marque).

#### Modifié
- Les e-mails mis en file depuis les lots 1 et 2 partent désormais réellement (driver `log` en local).
- `RecordConsents` s'appuie sur `RecordConsent` ; `LeadPolicy` ouvre la mise à jour au commercial.

#### Ajouts du 2026-11-15
- Page d'administration « À valider » (liste des informations à confirmer, lien de correction, badge dans le menu) ; `kh:content-audit` s'appuie sur le même service.
- Bandeau « Mobile Money — Opérateurs acceptés » dans le pied de page (Orange Money, Moov Money, Wave, Sama Money) ; opérateurs validés (D-017).
- Commande `kh:mfa-reset` pour réinitialiser la double authentification d'un utilisateur (journalisée).
- Logos Moov Money et Sama Money (fournis par Kilakodon) ; « Vos données vous appartiennent » validé (D-018).
- Refonte de la page Méthode : frise d'aperçu, étapes en quinconce avec livrables, engagements, appel au diagnostic.

#### Décisions de la direction (2026-11-14)
- D-015 : horaires de réponse validés (lundi–samedi, 8 h–18 h) ; ils sont désormais affichés sur le site et cités dans les e-mails.
- D-016 : poursuite du développement en local, mise en production après le lot 6.

#### Écarts notés dans DECISIONS.md
- Transitions d'étapes, « à traiter », heures ouvrées (validées depuis, D-015), confirmation humaine des RDV, modèles d'e-mail ajoutés, thème de l'admin, fusion manuelle, `privacy_requests` traité au lot 6.

### Lot 2 — Diagnostic et brief (2026-10-28)
#### Ajouté
- Moteur pur PHP (`app/Domain/Diagnostic/Engine`) : `ConditionEvaluator` (opérateurs TRD §6, blocs imbriqués, trace des questions déterminantes), `VisibilityResolver` (questions et options visibles, nettoyage des réponses devenues non pertinentes), `RuleEngine`, `MaturityScorer`, `PainCompleter`, DTO `DiagnosticOutcome`. Tests : cas T1–T10 de DIAGNOSTIC_SPEC §6, un test positif et un négatif par règle (32 règles), opérateurs, barème de maturité.
- Tables `diagnostic_templates|questions|rules|sessions|answers|results`, `briefs`, `brief_versions` (+ clé étrangère `leads.brief_id`) ; `DiagnosticTemplateV1Seeder` (questionnaire v1 activé) ; immuabilité des templates actifs ou retirés (questions et règles comprises).
- Actions : `ActivateDiagnosticTemplate` (validation de cohérence, un seul actif), `DuplicateDiagnosticTemplate`, `StartDiagnosticSession` (token haché, 30 jours, pré-remplissage `?secteur=`), `SaveDiagnosticAnswers`, `CompleteDiagnostic`, `PurgeDiagnosticSessions` (`kh:diagnostic-purge`, 02:00), `UpsertContact` (E.164, doublons suggérés), `RecordConsents`, `QueueLeadNotifications`, `CreateBriefFromDiagnostic`, `CreateLeadFromBrief` (idempotent), `RequestBrief`, `ValidateBrief` (version figée), `RevokeBriefAccess`.
- `ReferenceGenerator` (`KLK-AAAA-NNNN`) : test de 20 processus réellement parallèles → 20 références distinctes ; compteurs créés chaque jour (`kh:sequences-prepare`).
- Résumé : gabarit Blade et reformulation IA du seul DTO (`GenerateDiagnosticSummary`, `LlmClient` + `FakeLlmClient`, sortie validée, repli gabarit).
- Livewire `Diagnostic\Wizard` (étapes, « Je ne sais pas », choix exclusifs, 4 priorités max, enregistrement à chaque étape, lien de reprise, 20 sessions/jour/IP, focus et annonces `aria-live`) ; restitution avec « Pourquoi ? » ; formulaire de coordonnées (consentements séparés non cochés, honeypot, 5 demandes/h/IP).
- Livewire `Brief\Show` (document, correction, validation, états expiré et révoqué) ; PDF dompdf « document non contractuel » ; pages à token limitées à 30 requêtes/min/IP.
- Filament : questionnaires (lecture seule si actifs, « Activer », « Dupliquer en nouvelle version »), sessions, briefs (versions figées, révocation du lien) ; lien lead → brief.
- Test de schéma : aucune colonne `ON UPDATE CURRENT_TIMESTAMP`.

#### Modifié
- `CreateLeadFromContact` réutilise `RecordConsents` et `QueueLeadNotifications`.
- `x-kh.checkbox` transmet les attributs `wire:*` ; nouvelles icônes Lucide (arrow-left, circle-help, download, link, copy, save, lightbulb, pencil) ; composant `x-kh.focus-header`.

#### Corrigé
- Colonnes `TIMESTAMP NOT NULL` réécrites par MariaDB à chaque mise à jour (`analytics_events.occurred_at`, migration corrective).

#### Écarts notés dans DECISIONS.md
- D-014 (CSP : `'unsafe-eval'` limité au diagnostic et au brief, résout Q-018), Q-019 (tranches de budget) ; secret MFA de l'admin de développement restauré après `migrate:fresh` ; départage des priorités ; lead créé à l'envoi des coordonnées ; doublons suggérés sans fusion ; PDF à la demande ; rendez-vous via WhatsApp jusqu'au lot 3 ; animations CSS dans les vues Livewire.

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
- Refonte visuelle « agence » (D-013, retours de la direction du 2026-10-28) : police Plus Jakarta Sans grasse, hero sombre avec maquette de tableau de bord animée (données « Démo »), en-têtes de pages sombres, bandeau défilant des savoir-faire, grille « bento », section « Logiciels éprouvés » (lycée privé, boulangerie-pâtisserie), halo lumineux et élévation au survol des cartes, reflet sur les boutons, apparitions variées au défilement, parallaxe légère et barre de progression ; contenus enrichis depuis kilakodon-services.com (sans prix ni promesses invérifiables).
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
