# PLAN D'IMPLÉMENTATION — Kilakodon Hub (MVP, développement local)

> Ordre d'exécution pour Claude Code. **Un lot à la fois.** Chaque lot se termine par la checklist de fin de lot (skill `kh-lot-review`) et un **arrêt pour validation humaine** avant d'attaquer le suivant.
> Durées indicatives pour Claude Code + une personne qui relit et teste. Total ≈ 9–10 semaines.

## Règles de travail

1. Avant chaque lot : relire `CLAUDE.md`, la section du lot ci-dessous et les sections de doc citées. Lister les tâches avec l'outil de suivi.
2. Une branche Git par lot (`lot-1-site-public`), des commits petits et explicites (`feat(diagnostic): évaluateur de conditions`), une PR en fin de lot.
3. **Tests d'abord** pour les moteurs (diagnostic, références, gardes IA, permissions).
4. Pas de dépendance hors TRD §2 sans ajout dans `docs/DECISIONS.md`.
5. En cas de contradiction entre les docs : appliquer l'ordre d'autorité de `CLAUDE.md`, **noter** la contradiction dans `DECISIONS.md` et continuer avec l'interprétation la plus prudente.
6. **S'arrêter et demander** si une tâche exige : une décision commerciale (prix, délais, promesses), un texte juridique, un accès à la production, une clé API réelle, ou la suppression de données.
7. Fin de lot : `composer kh:check` vert, démonstration manuelle décrite (« comment tester ») dans la PR, mise à jour de `docs/CHANGELOG.md`.

---

## Lot 0 — Socle (≈ 1 semaine)
**But :** un projet propre, outillé, à l'identité Kilakodon, avec un back-office sécurisé.

Tâches :
- [ ] Créer le projet Laravel 12. Ajouter `config.platform.php = 8.2.0` dans `composer.json`. Configurer `DB_PREFIX=kh_`, `APP_LOCALE=fr`, les traductions FR de Laravel (`lang/fr`), et les drivers `database` pour la file, le cache et la session.
- [ ] Installer Pest, Pint, Larastan ; script `composer kh:check`.
- [ ] Installer Filament (panneau `/admin`), le thème bleu Kilakodon et le logo ; activer la MFA pour `admin`.
- [ ] Mettre en place l'enum `UserRole` (`admin`, `sales`, `editor`), les politiques de base, la commande `kh:make-admin` et le `DevSeeder` (3 utilisateurs).
- [ ] Créer les tables `settings`, `audit_logs`, `sequences`, `outbound_messages`, `analytics_events` (BACKEND_SCHEMA §2), le service `Settings` avec cache, et la page Filament « Paramètres ».
- [ ] Mettre en place le trait/observateur `Auditable` et les écritures d'audit (connexion, rôle, paramètres).
- [ ] Construire le **design system** : `resources/css/app.css` (tokens UX §3.4), polices auto-hébergées, composants Blade `x-kh.*` (button, link-arrow, eyebrow, card, card-accent, artifact, section, badge, input, icon, halftone), layout public (header et footer complets UX §4.1–4.2), `reveal.js` et header au scroll.
- [ ] Créer une page `/_styleguide` (**local uniquement**, middleware `env:local`) qui affiche tous les composants et états.
- [ ] Mettre en place les en-têtes de sécurité (middleware) et les pages d'erreur 404/500 stylées.

**Critères d'acceptation :** `/_styleguide` rend tous les composants en mobile et desktop ; un `editor` ne voit pas « Paramètres » ni « Utilisateurs » ; la MFA est demandée à un `admin` ; `kh:check` est vert.

---

## Lot 1 — Site public, contenus et SEO (≈ 1,5 semaine)
**Docs :** PRD §6.1, UX §5.1–5.3 et §5.7, CONTENT.md, BACKEND_SCHEMA §3, TRD §5.

Tâches :
- [ ] Migrations et modèles : `services`, `sectors`, `sector_service`, `case_studies`, `case_study_service`, `articles`, `faqs`, `pages`, `content_revisions` ; traits `Publishable` et `HasRevisions`.
- [ ] Action `PublishContent` (seul un `admin` publie) ; règle de publication des réalisations (autorisation `granted`) ; restauration d'une révision.
- [ ] Ressources Filament pour tous les contenus (éditeur Markdown, aperçu, statut, révisions).
- [ ] `ContentSeeder` depuis CONTENT.md : les contenus contenant `[À VALIDER]` sont créés en `draft` ; les autres en `published`.
- [ ] Pages publiques : accueil (toutes les sections UX §5.1, section Réalisations masquée si vide), solutions (index + détail), secteurs, réalisations, méthode, à propos, ressources (index + article), légales, contact (formulaire + Action `CreateLeadFromContact`). Ce lot livre donc dès maintenant les tables `contacts`, `consents` et `leads` (BACKEND_SCHEMA §5, colonnes complètes) avec une liste Filament simple ; le lot 3 complète le CRM.
- [ ] `WhatsAppLink` (helper) et boutons contextuels (CONTENT §11).
- [ ] SEO : composant `x-kh.seo` (title, description, canonical, OG, Twitter), JSON-LD (Organization/LocalBusiness, Service, FAQPage, Article, BreadcrumbList), sitemap, robots, `noindex` pour les routes privées.
- [ ] Middleware `LegacyRedirects` (TRD §5.3) et son test.
- [ ] Commande `kh:content-audit` (liste les `[À VALIDER]` dans les contenus publiés et les paramètres ; code de sortie ≠ 0 s'il en reste).
- [ ] Animations de l'accueil (UX §7) ; artefacts du hero ; sphère de points.

**Critères d'acceptation :** WEB-01, 02, 03, 05, 06, 07, 08, 09 ; Lighthouse mobile accueil ≥ 90/95/95 (perf/a11y/SEO) ; le site est lisible JS désactivé ; aucun lien `#`.

---

## Lot 2 — Diagnostic et brief (≈ 2 semaines)
**Docs :** PRD §6.2–6.3, DIAGNOSTIC_SPEC.md, TRD §6, BACKEND_SCHEMA §4–5, UX §5.4–5.5.

Tâches :
- [x] Moteur pur PHP : `ConditionEvaluator`, `VisibilityResolver`, `RuleEngine`, `MaturityScorer`, `PainCompleter`, DTO `DiagnosticOutcome` — **tous les tests de DIAGNOSTIC_SPEC §6** + tests positif et négatif par règle.
- [x] Migrations : templates, questions, règles, sessions, réponses, résultats ; `DiagnosticTemplateV1Seeder` ; Action `ActivateDiagnosticTemplate` (immutabilité).
- [x] Composant Livewire `Diagnostic\Wizard` : étapes, questions visibles, retour arrière, « Je ne sais pas », pré-remplissage `?secteur=`, enregistrement à chaque étape, lien de reprise, transitions et accessibilité (UX §5.4, §9).
- [x] Écran de restitution (« Pourquoi ? » dépliable), résumé gabarit + job `GenerateDiagnosticSummary` (IA via `LlmClient`, repli gabarit).
- [x] Formulaire de coordonnées et consentements → Actions `UpsertContact` (normalisation E.164, suggestion de doublon), `CreateBriefFromDiagnostic` (référence via `ReferenceGenerator`, version 1, token d'accès) et `CreateLeadFromBrief` (idempotent).
- [x] Page brief Livewire : correction, validation (nouvelle version figée), états expiré et révoqué ; PDF dompdf ; boutons WhatsApp et RDV.
- [x] Filament : éditeur de templates, questions et règles (lecture seule si `active`, bouton « Dupliquer en nouvelle version »), liste des sessions et des briefs.
- [x] Purge `kh:diagnostic-purge`.

**Critères d'acceptation :** DIA-01..04, BRF-01..04 ; parcours P1 complet testé (Pest feature) ; test de concurrence `ReferenceGenerator` (20 générations parallèles simulées → 20 références distinctes).

---

## Lot 3 — CRM, rendez-vous et notifications (≈ 1 semaine)
**Docs :** PRD §6.6–6.7, BACKEND_SCHEMA §5, CONTENT §11.

Tâches :
- [ ] Compléter `contacts`, `companies`, `leads`, `lead_stage_history`, `activities`, `appointments`, `consents`, `privacy_requests`.
- [ ] Actions : `ChangeLeadStage` (transitions autorisées, motif obligatoire pour `lost` et `on_hold`), `AssignLead`, `LogActivity`, `RequestAppointment`, `ConfirmAppointment`, `MergeContacts` (journalisé), `RecordConsent`.
- [ ] Filament : Leads en liste et en **Kanban** (par étape, glisser-déposer → `ChangeLeadStage` avec modale de motif si besoin), fiche lead complète (brief, diagnostic, conversation, activités, bouton WhatsApp), Contacts avec suggestion de doublons, RDV avec actions confirmer, annuler et réaliser.
- [ ] Page publique `/rendez-vous` et formulaire.
- [ ] Notifications : `outbound_messages` + job `SendOutboundMessage` + modèles e-mail (CONTENT §11) ; écran Filament « Envois » avec bouton « Renvoyer ».
- [ ] Scheduler : `kh:leads-unattended`.
- [ ] Widgets du tableau de bord : leads à traiter, RDV demandés.

**Critères d'acceptation :** CRM-01..04, CRM-08, RDV-01, COM-01 ; test : un échec SMTP laisse le lead intact et l'envoi en `failed` relançable ; un `sales` ne modifie pas les paramètres.

---

## Lot 4 — Assistant IA (≈ 1,5 semaine)
**Docs :** PRD §6.4, AI_ASSISTANT.md, TRD §7, BACKEND_SCHEMA §6, UX §4.3.

Tâches :
- [ ] Migrations : knowledge_documents, knowledge_chunks (FULLTEXT), conversations, messages, ai_action_proposals, ai_feedback, ai_usage_daily.
- [ ] `RebuildKnowledgeForSource` (déclenché à la publication), `KnowledgeSeeder`, ressource Filament « Base de connaissances » (approbation par `admin`, documents manuels).
- [ ] `Retriever` (natural, puis boolean, puis synonymes) avec tests sur un jeu de fragments fixe.
- [ ] Interface `LlmClient` ; `AnthropicClient` (outil forcé `respond`, timeout, une nouvelle tentative, usage) ; `FakeLlmClient` ; `PromptBuilder` (prompt `sys-v1` versionné) ; `ResponseValidator` (citations, filtre montants, schéma).
- [ ] `AssistantGuard` (tous les plafonds TRD §7.4) et `kh:ai-budget-check`.
- [ ] Endpoints JSON (TRD §5.2) ; actions proposées avec confirmation, hash et idempotence ; réutilisation des Actions du lot 2/3.
- [ ] Widget Alpine (chargé à la demande) : lanceur, panneau, sources, 👍/👎, carte de confirmation, états indisponible et limite.
- [ ] Filament : Conversations (lecture, limitée aux leads du commercial pour `sales`), Retours à revoir, Usage (jour et mois), interrupteur IA.
- [ ] `tests/Ai/cases.yaml` (≥ 60 cas) + `kh:ai-eval` (mode `fake` pour la CI, mode `anthropic` en manuel) + export CSV.

**Critères d'acceptation :** IA-01..04, 06, 07 ; tests : interrupteur coupé → aucun appel HTTP ; plafond atteint → repli ; confirmation avec payload altéré → refus ; double confirmation → un seul lead. **Arrêt : demander une clé API à l'équipe pour l'évaluation réelle.**

---

## Lot 5 — Démo Lab (≈ 1,5 semaine)
**Docs :** PRD §6.5, TRD §8, BACKEND_SCHEMA §7, UX §5.6, CONTENT §5.

Tâches :
- [ ] Migrations `demo_*` ; trait `BelongsToWorkspace` + test d'architecture ; `DemoLabFixtures` (catalogue fictif réaliste : riz, huile, sucre, savon, crédit téléphonique, etc., prix en FCFA plausibles et **étiquetés fictifs**).
- [ ] Création et reprise de l'espace (cookie signé), limites, `kh:lab-purge`.
- [ ] Livewire : Caisse (panier, moyens de paiement, encaissement → `RecordDemoSale`), Stock (réassort, ajustement, alertes), Ventes (liste, facture imprimable avec filigrane), Tableau de bord (ventes 7 jours en SVG léger, top produits, alertes).
- [ ] Guide des missions et carte de conversion (→ diagnostic `?secteur=retail&entry=lab` ou WhatsApp).
- [ ] Pages de démos guidées (gestion scolaire, boulangerie) : gabarit captures commentées et vidéo (fichiers fournis par Kilakodon ; afficher « bientôt disponible » sinon).

**Critères d'acceptation :** LAB-01..03 ; test : deux espaces simultanés ne voient pas leurs données respectives ; aucune notification ni e-mail émis depuis le Démo Lab (Mail::fake + assertNothingSent).

---

## Lot 6 — Mesure, données personnelles, finition, recette (≈ 1 semaine)
**Docs :** PRD §6.8 et §8, TRD §9 et §11, UX §9.

Tâches :
- [ ] Bannière cookies minimale (nécessaires / mesure d'audience) ; `visitor_key` conditionnel ; endpoint `/api/events` avec liste blanche ; événements serveur.
- [ ] Tableau de bord direction (entonnoir 30 j, sources, délai médian de prise en charge, coût IA par brief qualifié).
- [ ] Données personnelles : `privacy_requests` dans Filament, `ExportContactData` (JSON), `AnonymizeContact`, `kh:conversations-purge` ; lien « Gérer mes données » dans le pied de page vers un formulaire dédié.
- [ ] Passe accessibilité (clavier, lecteur d'écran sur diagnostic et assistant), passe performance (images, polices, JS), Lighthouse sur 5 pages.
- [ ] Revue de sécurité (skill `kh-security-review`), sur les endpoints publics, les tokens et les politiques.
- [ ] Recette complète des parcours P1 à P5 ; liste des `[À VALIDER]` restants (sortie de `kh:content-audit`) remise à la direction.
- [ ] `docs/RUNBOOK.md` : activer ou désactiver l'IA, relancer un envoi, traiter une demande de droits, restaurer un contenu, ajouter une règle de diagnostic.

**Critères d'acceptation :** tous les critères Go/No-Go du PRD §8 vérifiables en local (sauf ceux qui dépendent de décisions de la direction, listés).

---

## Lot 7 — Production (NE PAS DÉMARRER sans décision)
Choix de la formule LWS (avec ou sans SSH), workflow GitHub Actions, préproduction, stratégie de migration des URL, sauvegardes, supervision. À planifier avec l'équipe après le lot 6.

---

## Suivi
| Lot | Statut | PR | Validé par | Date |
|---|---|---|---|---|
| 0 | validé | branche `lot-0-socle` | Direction Kilakodon | 2026-10-26 |
| 1 | validé | branche `lot-1-site-public` | Direction Kilakodon | 2026-10-28 |
| 2 | validé | branche `lot-2-diagnostic` | Direction Kilakodon | 2026-10-28 |
| 3 | à faire | | | |
| 4 | à faire | | | |
| 5 | à faire | | | |
| 6 | à faire | | | |
