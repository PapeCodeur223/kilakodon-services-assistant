# CLAUDE.md — Kilakodon Hub

## Mission
Tu développes **Kilakodon Hub**, la nouvelle plateforme web de **Kilakodon Services** (Bamako, Mali), une entreprise qui conçoit des logiciels de gestion sur mesure (ERP/CRM, stock et caisse, applications web et mobiles, audit, design).
La plateforme est un **outil d'avant-vente qui prouve l'expertise** :
- un site public commercial et indexable ;
- un **diagnostic** gratuit qui produit un **brief** validé par le prospect ;
- un **Démo Lab** interactif ;
- un **assistant IA encadré** ;
- un mini-CRM pour l'équipe.

**Phase actuelle : développement LOCAL uniquement.** Ne mets en place aucun déploiement, aucun accès à la production, aucune configuration LWS.

## Documents (ordre d'autorité en cas de conflit)
1. `docs/DECISIONS.md` — décisions prises et questions ouvertes
2. `docs/PRD.md` — quoi et pourquoi (exigences, critères d'acceptation)
3. `docs/TRD.md` — comment (stack, architecture, sécurité, IA, files)
4. `docs/BACKEND_SCHEMA.md` — base de données
5. `docs/UX_UI_DESIGN.md` — design system, pages, animations
6. `docs/DIAGNOSTIC_SPEC.md` — questions, règles, tests du diagnostic
7. `docs/AI_ASSISTANT.md` — prompt, sortie structurée, corpus d'évaluation
8. `docs/CONTENT.md` — tous les textes marketing (source des seeders)
9. `docs/IMPLEMENTATION_PLAN.md` — lots, ordre et critères de fin

Si deux documents se contredisent : applique le plus haut dans la liste, **note l'écart** dans `docs/DECISIONS.md` (journal) et continue avec l'option la plus prudente.

## Règles non négociables
1. **Ne jamais inventer** de client, réalisation, témoignage, statistique, prix, délai ferme, certification ou partenaire — ni dans le code, ni dans les seeders, ni dans les textes. Une information manquante reste `[À VALIDER]` et son contenu reste en `draft`.
2. **L'IA ne fixe jamais de prix**, ne prend aucun engagement et n'écrit rien sans **confirmation explicite** du visiteur (carte de confirmation, payload figé, hash, idempotence).
3. **Les règles déterministes décident** (recommandations, droits, statuts). L'IA reformule seulement.
4. **Le site fonctionne sans l'IA et sans JS** pour la lecture. L'IA peut être coupée par un interrupteur sans rien casser.
5. **Démo Lab = données fictives isolées** (`workspace_id` + global scope), étiquetées « Démo ».
6. **Sécurité côté serveur** : politiques sur chaque ressource, tokens publics aléatoires et stockés hachés, aucun secret en base ni dans le dépôt, aucune entrée utilisateur rendue sans échappement.
7. **Consentement marketing séparé**, jamais pré-coché.
8. **Compatibilité production** : PHP **8.2** (verrou `config.platform.php`), MySQL/MariaDB, pas de Redis, pas de worker permanent, pas de Node en production.

## Stack
Laravel 12 · PHP 8.2 · Filament (panneau `/admin`) · Livewire + Alpine.js · Tailwind CSS v4 (Vite) · MySQL/MariaDB (`DB_PREFIX=kh_`) · Pest · Pint · Larastan · dompdf · laravel-phone · spatie/laravel-sitemap · spatie/laravel-honeypot · API Anthropic (Http client, `AI_DRIVER=fake|anthropic`).
**Aucune autre dépendance** sans justification dans `docs/DECISIONS.md`. Pas de bibliothèque d'animation.

## Conventions de code
- Monolithe modulaire : `app/Domain/<Module>/{Models,Actions,Enums,Data,Services}`. Contrôleurs, Livewire et Filament restent **minces** et appellent des **Actions** (`handle()`).
- Code, tables, colonnes et valeurs d'enum **en anglais**. UI, contenus, messages et e-mails **en français** (méthode `label()` des enums). URL publiques en français.
- Enums PHP natifs stockés en `string`, pas d'ENUM SQL.
- Montants : entiers en FCFA (`*_xof`). Dates en UTC, affichage `Africa/Bamako`.
- Pas d'ID auto-incrémenté dans une URL publique (ULID ou token).
- Vues : composants `x-kh.*`, tokens Tailwind uniquement, **aucune couleur en dur**.
- Animations : `transform`/`opacity` uniquement, `prefers-reduced-motion` respecté, contenu visible sans JS.
- Commits : conventional commits en français (`feat(crm): kanban des leads`).

## Commandes
```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate:fresh --seed     # données de dev (DevSeeder en local)
npm run dev                          # Vite
php artisan schedule:work            # file d'attente + tâches planifiées
php artisan kh:make-admin            # créer un administrateur
composer kh:check                    # pint --test + phpstan + pest (obligatoire en fin de tâche)
php artisan kh:content-audit         # liste les [À VALIDER] restants
php artisan kh:ai-eval --driver=fake # corpus IA (anthropic en manuel uniquement)
```

## Façon de travailler
- Travaille **lot par lot** selon `docs/IMPLEMENTATION_PLAN.md`. Utilise le skill `kh-lot-workflow` pour démarrer un lot et `kh-lot-review` pour le clôturer. **Arrête-toi** à la fin de chaque lot pour validation humaine.
- **Tests d'abord** pour : moteur de diagnostic, générateur de références, gardes IA, validation des réponses IA, politiques, isolation du Démo Lab.
- Avant toute interface : skill `kh-design-system`. Avant d'écrire ou de modifier un texte public : skill `kh-content-copy`. Pour le diagnostic : `kh-diagnostic-rules`. Pour l'assistant : `kh-ai-guardrails`. Avant de clore les lots 2, 4 et 6 : `kh-security-review`.
- Pour une revue indépendante d'un lot, délègue au sous-agent `kh-reviewer`.
- **Demande avant de** : choisir un prix, un délai ou une promesse commerciale ; rédiger un texte juridique définitif ; utiliser une vraie clé API ; toucher à la production ; supprimer des données ; ajouter une dépendance majeure.
- Garde `docs/CHANGELOG.md` à jour (une entrée par lot).

## Identité
- Logo : `resources/brand/logo-kilakodon.png` — **ne pas le modifier ni le recolorer**.
- Bleu `#282A89` (structure, actions) ; rouge `#F91C0C` (logo, décor, texte ≥ 24 px) / `#D4170A` (rouge fonctionnel AA) — rare.
- Affichage : Plus Jakarta Sans 800 (un fragment rouge surligné par titre clé) — décision D-013, qui remplace Fraunces. Texte : Inter.
- Contact : WhatsApp +223 74 66 64 89 (principal), +223 97 46 28 39 ; kilakodon@gmail.com ; Bamako, Mali.
