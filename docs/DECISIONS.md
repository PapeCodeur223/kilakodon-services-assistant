# DÉCISIONS — Registre

> Décisions prises (D-xxx) et questions ouvertes (Q-xxx). Claude Code **ajoute** ici toute contradiction relevée, toute nouvelle dépendance et toute interprétation faite par défaut. Il ne supprime jamais une entrée.

## Décisions prises

| ID | Date | Décision | Raison |
|---|---|---|---|
| D-001 | 2026-10-25 | Nouvelle plateforme développée de zéro ; l'ancien site est remplacé à la mise en production | Validation direction |
| D-002 | 2026-10-25 | Nom public : **Kilakodon Services** ; nom produit : **Kilakodon Hub** | Validation direction |
| D-003 | 2026-10-25 | Stack : Laravel 12, PHP 8.2 (verrou Composer), Filament, Livewire/Alpine, Tailwind v4, MySQL/MariaDB, préfixe `kh_` | Compatibilité LWS Perso (PHP 8.2, cron, pas de SSH) |
| D-004 | 2026-10-25 | Développement **local uniquement** jusqu'à la fin du lot 6 ; la production sera décidée ensuite (formule avec SSH ou GitHub Actions) | Validation direction |
| D-005 | 2026-10-25 | Périmètre MVP = PRD §6 (exigences M). Espace client, devis, configurateur et API WhatsApp sont reportés | Arbitrage MVP |
| D-006 | 2026-10-25 | Démo Lab interactif « Boutique » inclus dans le MVP | Différenciation et preuve d'expertise |
| D-007 | 2026-10-25 | Recommandations du diagnostic par règles déterministes ; l'IA reformule uniquement | Fiabilité, explicabilité |
| D-008 | 2026-10-25 | IA : API Anthropic, modèle par défaut `claude-haiku-5-5` (configurable), sortie structurée forcée, recherche FULLTEXT MySQL (pas de base vectorielle) | Coût, simplicité, hébergement mutualisé |
| D-009 | 2026-10-25 | Identité visuelle : logo inchangé, bleu `#282A89` et rouge `#F91C0C` mesurés sur le logo ; style éditorial inspiré de la référence « Steep » | Demande direction |
| D-010 | 2026-10-25 | Monnaie affichée FCFA (XOF en base), fuseau Africa/Bamako, langue FR | Contexte |
| D-011 | 2026-10-25 | Boutique en ligne supprimée ; les logiciels Lycée et Boulangerie deviennent des pages secteur et des démos | Positionnement « expertise » |
| D-012 | 2026-10-25 | Aucun upload de fichiers par les visiteurs au MVP | Réduction de la surface de risque |
| D-013 | 2026-10-28 | Identité visuelle « agence » plus expressive : police d'affichage grasse **Plus Jakarta Sans** (700–800) à la place de Fraunces 400 ; accent rouge surligné (non italique) ; hero et en-têtes de pages sombres (bleu nuit, trame, halos) ; effets de survol sur toutes les cartes et boutons ; animations au défilement (apparitions variées, parallaxe légère desktop, barre de progression) ; bandeau défilant des savoir-faire. Remplace UX §3.2, §7 (dosage) et §10 sur ces points | Demande de la direction : « design ultra pro digne d'une agence », police grasse inspirant confiance. Contraintes conservées : transform/opacity uniquement, `prefers-reduced-motion`, contenu lisible sans JS, aucune bibliothèque d'animation |

## Questions ouvertes (à trancher par Kilakodon avant la mise en production)

| ID | Question | Impact | Défaut appliqué en attendant |
|---|---|---|---|
| Q-001 | Raison sociale, RCCM, NIF, adresse précise, directeur de publication | Mentions légales | Pages légales en `draft` |
| Q-002 | Horaires de réponse réels ; maintient-on « support 7j/7 » ? | Contact, assistant, e-mails | « Lun–sam, 8 h–18 h » marqué [À VALIDER] |
| Q-003 | Délais indicatifs par solution | Pages solutions | Fourchettes de CONTENT §3 marquées [À VALIDER] |
| Q-004 | Affiche-t-on des prix « à partir de » ? | Pages solutions, assistant | Non : « Sur devis après diagnostic » |
| Q-005 | Tranches de budget du diagnostic | Diagnostic | Tranches de DIAGNOSTIC_SPEC §2 |
| Q-006 | Réalisations publiables (accord écrit des clients) | Section Réalisations | Section masquée tant qu'il n'y en a aucune |
| Q-007 | Opérateurs Mobile Money réellement intégrables | Contenus | Formulation prudente [À VALIDER] |
| Q-008 | Conformité « facture normalisée » | Contenu Stocks & caisse | Formulation prudente [À VALIDER] |
| Q-009 | Budget IA mensuel et quotidien | AssistantGuard | 0 en production tant que non renseigné (IA désactivée) |
| Q-010 | Durées de conservation (diagnostics, conversations, leads perdus) | Purges | 30 j / 90 j / non purgé |
| Q-011 | Régime de consentement pour la mesure d'audience ; validation juridique de la politique de confidentialité (loi n° 2013-015, APDP) | Bannière cookies | Mesure désactivée par défaut jusqu'à acceptation |
| Q-012 | Sort de l'ancien espace client `/client/*` (des clients l'utilisent-ils ?) | Redirections | Redirection vers `/contact` |
| Q-013 | Correspondance `produit_detail.php?id=1/2` | Redirections | id 1 → écoles, id 2 → boulangeries |
| Q-014 | Supports des démos guidées (vidéos, captures) | Lot 5 | Gabarit « bientôt disponible » |
| Q-015 | Adresse e-mail de domaine et SMTP de production | E-mails | `kilakodon@gmail.com`, driver `log` en local |
| Q-016 | Photos réelles de l'équipe et des locaux | À propos, accueil | Pas de photos ; visuels produits uniquement |
| Q-017 | Propriété du code et des données livrés aux clients | Carte « Vos données vous appartiennent » | Marquée [À VALIDER] |

## Journal des écarts et interprétations (rempli par Claude Code)

| Date | Lot | Constat | Interprétation retenue |
|---|---|---|---|
| 2026-10-26 | 0 | Poste local : PHP 8.4 (Herd Lite, sans `intl`, requis par Filament) et PHP 8.1 (XAMPP, trop ancien pour Laravel 12) | PHP 8.2.35 officiel installé dans `C:\php\8.2` (validé par l'équipe), utilisé en chemin explicite ; XAMPP inchangé. Le site se lance avec `php artisan serve` (Apache XAMPP reste en 8.1) |
| 2026-10-26 | 0 | MariaDB locale 10.4 (XAMPP) alors que TRD §1 vise 10.6+ | Accepté en local (Laravel 12 supporte 10.3+) ; à aligner sur la version LWS au lot 7. Aucune fonctionnalité 10.6+ utilisée |
| 2026-10-26 | 0 | Tests : base SQLite par défaut de Laravel | Tests sur MariaDB (`kilakodon_hub_testing`) pour rester fidèle à la production (FULLTEXT au lot 4) ; suite plus lente (~7 min) |
| 2026-10-26 | 0 | UX §3.4 importe Fraunces `wght`, mais demande `"SOFT" 50, "opsz" 72` (axes absents de ce fichier) ; le fichier `full` pèse 121 Ko | Import de `opsz.css` + `opsz-italic.css` (67 Ko) : taille optique respectée, axe SOFT abandonné. Repasser à `full.css` si la direction tient à SOFT |
| 2026-10-26 | 0 | `--color-*: initial` dans `@theme` | La palette Tailwind par défaut est retirée : seules les couleurs-tokens existent (impossible d'écrire `bg-blue-500`). Ajout de `success-50` et `warning-50` (fonds des badges) dérivés des tokens |
| 2026-10-26 | 0 | TRD §11 : CSP `self` + nonce. Filament/Alpine exigent `unsafe-eval` et des scripts en ligne | CSP stricte (nonce, sans `unsafe-eval`) sur les pages publiques ; CSP assouplie limitée à `/admin`, `/livewire/*`, `/filament/*`. À réévaluer pour le diagnostic (Livewire) au lot 2 |
| 2026-10-26 | 0 | Filament 4 : « MFA obligatoire » s'applique à tous les utilisateurs | Middleware `RequireAdminMultiFactor` : obligatoire pour `admin`, facultative (profil) pour `sales`/`editor` |
| 2026-10-26 | 0 | UX §2 : favicon « à partir de la sphère seule » ; or le mot « Kilakodon » chevauche la sphère dans le fichier source | Favicons générés depuis le logo complet recadré (aucun pixel modifié). Une version « sphère seule » doit être fournie par la direction si souhaitée |
| 2026-10-26 | 0 | Icône WhatsApp absente de Lucide | Glyphe de marque copié depuis Simple Icons (CC0) dans `resources/svg/brands/whatsapp.svg`, rendu par `x-kh.icon name="brand:whatsapp"` ; aucune dépendance |
| 2026-10-26 | 0 | Utilisateurs : PRD parle de « désactivation » | Pas de suppression d'utilisateur depuis l'admin (politique `delete` refusée pour soi-même, action absente) ; un admin ne peut ni changer son rôle ni se désactiver (contrôle serveur) |
| 2026-10-26 | 0 | Navigation (header/footer) requise au lot 0, contenus créés au lot 1 | Liste statique `config/kh_navigation.php` (libellés de CONTENT §3–4) ; les liens mènent à la 404 stylée jusqu'au lot 1, puis seront lus en base. « Gérer mes données » pointe provisoirement vers `/confidentialite` (formulaire au lot 6) |
| 2026-10-26 | 0 | Accueil : page complète prévue au lot 1 | Accueil provisoire limité au hero de CONTENT §2 |
| 2026-10-26 | 0 | `git fsmonitor` (activé globalement dans Git for Windows) bloque Composer indéfiniment | `core.fsmonitor=false` dans la config locale du dépôt uniquement |
| 2026-10-26 | 0 | `kh:content-audit` cité dans la checklist de fin de lot | Commande prévue au lot 1 : non exécutée au lot 0 |
| 2026-10-26 | 0 | IA : Q-009 | Paramètres seedés avec `ai.enabled=false` et budget 0 tant que la direction n'a pas fixé le budget |
| 2026-10-26 | 1 | BACKEND_SCHEMA §9 prévoit en local `ai.enabled = true` et un budget de 1 USD, contrairement au lot 0 | Appliqué en local uniquement (pilote `fake`, aucun appel réel) ; hors local, IA coupée (Q-009) |
| 2026-10-26 | 1 | Règle « contenu avec [À VALIDER] = brouillon » : les 6 solutions, Méthode, À propos et les 3 pages légales sont seedés en brouillon | Appliquée telle quelle. Le public ne voit ni liens ni pages vers ces brouillons (navigation calculée depuis les contenus publiés) ; l'équipe connectée (admin, éditeur) les prévisualise avec un bandeau, en `noindex` |
| 2026-10-26 | 1 | Publication d'un contenu contenant encore [À VALIDER] | Bloquée par `PublishContent` (règle n° 1 de CLAUDE.md) ; `kh:content-audit` couvre aussi les paramètres et les textes de l'accueil |
| 2026-10-26 | 1 | Textes de l'accueil (CONTENT §2) sans table dédiée au schéma | Fichier `resources/content/home.php` ; les éléments [À VALIDER] (Mobile Money, propriété des données) sont masqués au public et listés par l'audit |
| 2026-10-26 | 1 | Un éditeur peut-il modifier un contenu publié ? (sinon publication sans admin) | Non : un contenu publié/archivé n'est modifiable que par un admin, qui peut le repasser en brouillon. Transitions éditeur : brouillon ↔ relecture |
| 2026-10-26 | 1 | Colonnes ajoutées hors schéma : `sectors.icon` (navigation) et `faqs.on_home` (sélection des 6 FAQ de l'accueil) ; `faqs` sans `slug` | Ajoutées ; à reporter dans BACKEND_SCHEMA à la prochaine révision |
| 2026-10-26 | 1 | Secteurs 4.4 à 4.6 : CONTENT.md ne donne ni introduction ni « ce que ça change » | Rédigés selon kh-content-copy (aucun chiffre ni promesse) et ajoutés à CONTENT.md pour relecture ; publiés. Intro « Écoles » : mention de prix retirée (Q-004 déjà tracée) |
| 2026-10-26 | 1 | Articles de lancement : seuls les plans existent (CONTENT §9) | 4 articles rédigés en brouillon, toujours seedés en `draft` (relecture humaine obligatoire) |
| 2026-10-26 | 1 | Formulaire de contact : e-mails (accusé de réception, notification équipe) prévus au lot 3 | Messages enregistrés en file (`outbound_messages`, statut `queued`) dès le lot 1 ; l'envoi effectif arrive avec le job du lot 3. Un échec d'envoi ne peut donc pas perdre la demande |
| 2026-10-26 | 1 | Contact : réutiliser un contact existant (même téléphone) ? | Non au lot 1 : un nouveau contact par demande ; suggestion de doublons et fusion au lot 3 (CRM-01) |
| 2026-10-26 | 1 | Honeypot : par défaut, un envoi sans champs piège n'est pas vérifié | `honeypot_fields_required_for_all_forms = true` |
| 2026-10-26 | 1 | `/demo-lab` et `/diagnostic` sont liés depuis le site mais livrés aux lots 2 et 5 | Liens conservés (404 stylée en attendant) ; ils ne sont pas dans le sitemap |
| 2026-10-26 | 1 | Accueil, section Démo Lab : « capture animée légère » non disponible | Aperçu statique dans un artefact étiqueté « Démo » jusqu'au lot 5 |
| 2026-10-26 | 1 | Flottement continu des artefacts : ~60 recalculs de style/s sur mobile (carrousel) | Réservé au desktop (≥ 1024 px), conforme à UX §5.1 (carrousel sur mobile) ; entrée unique partout |
| 2026-10-26 | 1 | Lighthouse CLI instable sur ce poste (NO_NAVSTART/NO_FCP) et serveur de dev lent (MariaDB ~125 ms par écriture, `artisan serve` monoprocessus, sans gzip) | Une mesure aboutie : accessibilité 100, bonnes pratiques 100, SEO 100 (en mode production). Mesure labo CDP (mobile, CPU ×4, Slow 4G) : CLS 0,032, TBT 18 ms, 354 Ko ; FCP/LCP non représentatifs. À re-mesurer en préproduction (critère perf ≥ 90 non prouvé) |
| 2026-10-26 | 1 | OPcache absent du PHP 8.2 installé au lot 0 | Activé dans `C:\php\8.2\php.ini` (validation des fichiers conservée) |
| 2026-10-28 | 1 | Contenus inspirés du site actuel kilakodon-services.com (demande de la direction) | Repris : positionnement « écosystème logiciel & ERP », logiciels lycée privé et boulangerie-pâtisserie (fonctionnalités), multi-plateforme PC/tablette/cloud, accès par rôle et sauvegardes automatisées, équipe d'ingénieurs à Bamako. **Non repris** : prix (1 000 000 / 300 000 XOF — Q-004, décision commerciale), « zéro fraude » (interdit CONTENT), « 7j/7 » (Q-002), « milliers d'utilisateurs simultanés » (invérifiable). Les montants du tableau de bord (2 360 000 FCFA, +34,8 %) ne sont utilisés que dans une maquette étiquetée « Démo » |
| 2026-10-28 | 1 | Dépendance : `@fontsource-variable/plus-jakarta-sans` remplace `@fontsource-variable/fraunces` | Justifiée par D-013 ; même famille de paquets que TRD §2, auto-hébergée, plus légère (27 Ko contre 148 Ko) |
| 2026-10-28 | 1 | Home : les fiches solutions sont en brouillon, la grille « Six expertises » serait vide pour le public | Grille alimentée par les titres et accroches de CONTENT §3 (sans [À VALIDER]) ; lien vers la fiche seulement si elle est publiée. Même principe pour la colonne « Solutions » du pied de page (lien vers /solutions) |
