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
| | | | |
