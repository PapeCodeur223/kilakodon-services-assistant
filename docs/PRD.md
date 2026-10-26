# PRD — Kilakodon Hub (MVP)

> **Plateforme :** Kilakodon Services — **Produit :** Kilakodon Hub
> **Version :** 1.0 — 25 octobre 2026 — **Statut :** validé par la direction pour démarrage du développement local
> **Sources :** Cahier des charges « Plateforme IA » v1.0 (24/10/2026), analyse du site kilakodon-services.com, arbitrages MVP validés.
> Les éléments marqués **[À VALIDER]** sont des propositions à confirmer par Kilakodon avant mise en production (voir `DECISIONS.md`).

---

## 1. Contexte et problème

Kilakodon Services (Bamako, Mali) conçoit des logiciels de gestion, ERP/CRM, applications web et mobiles sur mesure. Le site actuel est une vitrine PHP classique :

- l'offre est décrite en une phrase et trois puces par service, sans délai, ni méthode détaillée, ni preuve ;
- des éléments nuisent à la crédibilité (liens légaux vers `#`, chiffres d'affaires et notes « 5.0 » non sourcés, CTA « démo gratuite » qui mène à un devis) ;
- toute la conversion repose sur un clic WhatsApp sans contexte : le commercial repart de zéro à chaque échange ;
- rien n'est mesuré : impossible de savoir combien de visiteurs deviennent des clients.

**Problème à résoudre :** un dirigeant qui arrive sur le site ne sait pas *quelle solution il lui faut*, ne voit *aucune preuve* que Kilakodon sait la livrer, et le contact obtenu arrive *sans information exploitable*.

## 2. Vision et positionnement

**Kilakodon Hub est une plateforme d'avant-vente qui démontre l'expertise de Kilakodon en la faisant vivre au visiteur.** Le visiteur comprend, essaie, se diagnostique et repart avec un brief professionnel ; l'équipe reçoit un dossier qualifié.

> **Principe :** la plateforme est elle-même la preuve. Elle est rapide en 3G, fonctionne sans compte, s'appuie sur une IA encadrée et honnête, et sur des démos réelles.

**Promesse publique (proposition) :** « Pilotez votre entreprise avec des outils *faits pour elle*. Diagnostic gratuit en 3 minutes, sans inscription. »

**Différenciateurs à rendre visibles :**
1. **Diagnostic Numérique Kilakodon** : une restitution immédiate et expliquée, et un brief remis au prospect avec une référence.
2. **Démo Lab** : une vraie mini-application (boutique : stock, caisse, factures) à manipuler, avec données fictives.
3. **Assistant Kilakodon** : une IA qui répond depuis les contenus approuvés, cite ses sources, ne promet jamais de prix et passe la main à un humain.
4. **Conçu pour les réalités locales** : mode hors-ligne, Mobile Money, connexion instable, FCFA, interlocuteur à Bamako.

## 3. Objectifs, non-objectifs et indicateurs

### 3.1 Objectifs MVP
| ID | Objectif |
|---|---|
| OBJ-1 | Aider le visiteur à comprendre l'offre et à choisir une prochaine action pertinente. |
| OBJ-2 | Transformer un besoin libre en **brief structuré, corrigé et validé** par le prospect. |
| OBJ-3 | Prouver l'expertise : démo manipulable, méthode, réalisations autorisées. |
| OBJ-4 | Centraliser chaque demande dans un mini-CRM avec un responsable et une prochaine action. |
| OBJ-5 | Encadrer l'IA : sources, limites, coûts plafonnés, transfert humain. |
| OBJ-6 | Mesurer l'entonnoir réel : diagnostic → brief → RDV → devis → contrat. |

### 3.2 Non-objectifs MVP
Espace client (projets, jalons, livrables), devis en ligne, configurateur chiffré, paiement, API WhatsApp Business, agenda synchronisé, multi-entreprises, upload de fichiers par les visiteurs, application mobile native, boutique en ligne, multilingue (l'architecture reste prête pour la traduction).

### 3.3 Indicateurs (cibles à 3 mois après lancement) [À VALIDER]
| Indicateur | Définition | Cible proposée |
|---|---|---|
| Taux de complétion du diagnostic | diagnostics terminés / commencés | ≥ 55 % |
| Briefs validés / mois | briefs au statut `validated` | ≥ 30 |
| Briefs qualifiés / mois | leads passés à `qualified` par un commercial | ≥ 15 |
| Délai de prise en charge | création du lead → première activité | médiane < 4 h ouvrées |
| RDV tenus / mois | appointments `done` | ≥ 8 |
| Coût IA par brief qualifié | coût IA du mois / briefs qualifiés | suivi, plafond mensuel [À VALIDER] |
| Performance | Lighthouse mobile (accueil) | Perf ≥ 90, A11y ≥ 95, SEO ≥ 95 |

> Le nombre de messages envoyés à l'assistant **n'est pas** un indicateur de succès.

## 4. Utilisateurs

### 4.1 Personas externes
| Persona | Situation | Ce qu'il cherche | Point d'entrée typique |
|---|---|---|---|
| **Gérant de commerce / multi-boutiques** | Stock suivi sur cahier ou Excel, écarts de caisse, plusieurs points de vente | Contrôler stock et caisse, voir ses chiffres sans être sur place | Recherche Google, recommandation, page « Stocks & caisse » |
| **Promoteur d'école privée** | Inscriptions, frais de scolarité, notes et bulletins à la main | Suivre les paiements, produire les bulletins, informer les parents | Page secteur « Écoles », démo gestion scolaire |
| **Gérant de boulangerie / restauration** | Production, pertes, ventes en points multiples | Maîtriser production, pertes et recettes | Page secteur, démo boulangerie |
| **Directeur PME services / logistique / BTP** | Outils dispersés, rapports manuels | Un ERP/CRM adapté, des tableaux de bord | Page « ERP & CRM », diagnostic |
| **Décideur « en exploration »** | Pas encore de projet précis | Comprendre ce qui est possible et à quel coût d'ordre de grandeur | Assistant, ressources |

Contraintes communes : mobile majoritaire, connexion parfois instable, WhatsApp comme canal naturel, français professionnel.

### 4.2 Rôles internes (MVP)
| Rôle | Code | Droits |
|---|---|---|
| Administrateur | `admin` | Tout : utilisateurs, paramètres, IA (activation, budget), contenus, CRM, journaux |
| Commercial | `sales` | Leads, briefs, contacts, RDV, activités ; lecture des conversations IA liées à ses leads ; pas de paramètres ni d'utilisateurs |
| Éditeur | `editor` | Contenus (solutions, secteurs, réalisations, articles, FAQ, pages), base de connaissances IA en brouillon ; publication **uniquement si** `admin` |

Visiteur anonyme et prospect identifié (par lien privé) n'ont **pas de compte**.

## 5. Parcours de bout en bout

### P1 — Découvrir → Diagnostic → Brief → Contact (parcours principal)
1. Le visiteur arrive sur l'accueil ou une page solution/secteur.
2. Il clique « Faire mon diagnostic gratuit ».
3. Il répond à 8–14 questions adaptatives en 4 étapes, avec progression visible et retour arrière.
4. Il reçoit une **restitution immédiate sans donner de coordonnées** : niveau de maturité, 3 priorités, modules recommandés **avec leurs justifications**, et points à clarifier.
5. Il clique « Recevoir mon brief » et saisit nom, téléphone/WhatsApp, e-mail facultatif, entreprise, ainsi que le consentement au traitement de sa demande. La case marketing est séparée et non cochée.
6. Il relit le brief, le **corrige** et le **valide**.
7. Il obtient la référence `KLK-2026-0042`, un lien privé (30 jours), un PDF, et deux boutons : **« Continuer sur WhatsApp »** (message pré-rempli avec la référence) et **« Demander un rendez-vous »**.
8. Côté interne, un lead est créé et attribué, l'équipe est notifiée par e-mail, et le prospect reçoit un accusé de réception par e-mail s'il en a fourni un.

### P2 — Assistant → Brief ou humain
Le visiteur ouvre l'assistant et pose une question. L'assistant répond depuis les contenus approuvés, avec ses sources. Si l'information manque, il le dit. Il peut ensuite proposer le diagnostic, préparer un brief (que le visiteur voit et confirme avant tout envoi) ou transférer vers WhatsApp/RDV.

### P3 — Démo Lab → Contact contextualisé
Le visiteur lance la démo « Boutique ». Il crée une vente, voit le stock baisser, déclenche une alerte et imprime une facture fictive. Un bandeau propose : « Vous voulez ça pour votre entreprise ? » → diagnostic pré-rempli (secteur `retail`) ou WhatsApp avec le contexte « Démo Boutique ».

### P4 — Traitement commercial (interne)
Nouveau lead → dédoublonnage par suggestion (même téléphone/e-mail) → attribution → qualification (adéquation, maturité, budget déclaré, échéance) → RDV → devis (hors plateforme au MVP, montant saisi) → gagné/perdu avec motif.

### P5 — Indisponibilité IA
En cas d'API en échec, de quota ou de budget atteint, un message clair s'affiche (« L'assistant est momentanément indisponible ») avec les boutons diagnostic, WhatsApp et contact. Le reste du site n'est pas affecté.

## 6. Exigences fonctionnelles MVP

Les identifiants reprennent ceux du cahier des charges quand ils existent. Priorité : **M** = MVP obligatoire, **L** = après MVP (le modèle de données le prépare).

### 6.1 Site public — WEB
| ID | Exigence | Prio | Critères d'acceptation |
|---|---|---|---|
| WEB-01 | Contenus administrables (pages, solutions, secteurs, réalisations, articles, FAQ) avec statuts `draft → review → published → archived`, historique des versions et restauration | M | Un `editor` ne peut pas publier ; une version précédente est restaurable ; prévisualisation d'un brouillon possible par l'équipe |
| WEB-02 | Catalogue orienté besoins : 6 solutions avec problème, cible, bénéfices, contenu, limites, délai indicatif et prochaine action | M | Chaque solution publiée a une URL stable et des liens vers le diagnostic, le Démo Lab ou le contact ; elle est lisible sans JS et sans assistant |
| WEB-03 | Réalisations avec statut d'autorisation client | M | Publication **bloquée** si `authorization_status ≠ granted`. Aucun résultat chiffré sans `results_verified = true`. La section se masque s'il n'existe aucune réalisation publiée |
| WEB-04 | Recherche publique | L | — |
| WEB-05 | SEO : titles, meta descriptions, Open Graph (aperçu WhatsApp/Facebook), sitemap.xml, robots.txt, données structurées (Organization, LocalBusiness, Service, FAQPage, Article), redirections 301 des anciennes URL | M | Les pages privées (brief, admin, démo) sont en `noindex` ; toutes les anciennes URL listées dans le TRD redirigent |
| WEB-06 | Formulaire de contact : champs minimaux, honeypot, limitation de débit, accusé de réception | M | Une double soumission (même clé) ne crée pas deux leads ; un échec d'e-mail ne perd pas la demande |
| WEB-07 | Pages légales réelles : mentions légales, confidentialité, cookies | M | Plus aucun lien `#` dans le pied de page |
| WEB-08 | Section Ressources : articles (3 à 5 au lancement) | M | Articles indexables, avec auteur, date et catégorie |
| WEB-09 | Boutons WhatsApp contextuels : lien `wa.me` avec message pré-rempli selon la page, le brief ou la démo | M | Le message contient la référence du brief quand elle existe ; l'URL est encodée correctement |

### 6.2 Diagnostic — DIA
| ID | Exigence | Prio | Critères d'acceptation |
|---|---|---|---|
| DIA-01 | Questionnaire adaptatif versionné (questions conditionnelles, retour arrière, progression, bouton « Je ne sais pas » sur les questions qui l'autorisent) | M | Changer une réponse recalcule les questions visibles et supprime les réponses devenues non pertinentes |
| DIA-02 | Restitution explicable calculée par un **moteur de règles déterministe** ; l'IA peut uniquement reformuler un résumé | M | Chaque recommandation affiche « Pourquoi ? » avec les réponses qui la motivent ; mêmes réponses = mêmes résultats |
| DIA-03 | Sauvegarde anonyme et reprise par lien non devinable | M | Session anonyme expirant à 30 jours ; un token invalide renvoie 404 ; la reprise conserve la version du questionnaire |
| DIA-04 | Pré-remplissage depuis le contexte (secteur depuis une page secteur ou le Démo Lab) | M | Le paramètre est proposé comme réponse modifiable, jamais imposé |

### 6.3 Brief — BRF
| ID | Exigence | Prio | Critères d'acceptation |
|---|---|---|---|
| BRF-01 | Le brief distingue **Déclaré par vous** (réponses), **Recommandé par Kilakodon** (règles) et **Résumé rédigé** (IA, éditable) | M | Le commercial voit la même distinction plus ses notes internes, jamais visibles côté prospect |
| BRF-02 | Le prospect corrige puis valide ; chaque validation crée une version figée | M | La version validée est consultable telle quelle dans l'admin |
| BRF-03 | Référence unique `KLK-AAAA-NNNN` séquentielle par année | M | Aucune collision sous concurrence (test) |
| BRF-04 | Lien privé (token aléatoire de 32 octets, haché en base, expiration 30 jours, révocable) et export PDF | M | Lien expiré ou révoqué → page « lien expiré » avec contact ; PDF à l'identité visuelle Kilakodon, mention « document non contractuel » |

### 6.4 Assistant IA — IA
| ID | Exigence | Prio | Critères d'acceptation |
|---|---|---|---|
| IA-01 | Assistant documentaire public : répond uniquement depuis les fragments approuvés, cite les pages sources, indique ses limites et affiche une mention IA | M | Corpus d'évaluation (≥ 60 cas, voir `AI_ASSISTANT.md`) : 0 prix inventé, 0 réalisation inventée, 0 fuite d'instructions, ≥ 90 % de réponses utiles et fondées sur les cas répondables |
| IA-02 | Qualification conversationnelle : propose un résumé de besoin sans redemander ce qui est connu | M | Le visiteur peut corriger ou refuser le résumé |
| IA-03 | Actions contrôlées : `propose_brief`, `request_appointment`, `handoff_whatsapp`, `start_diagnostic`. Toute écriture passe par une **carte de confirmation** qui montre exactement les données envoyées | M | Sans confirmation, aucune écriture. Clé d'idempotence par proposition. Un échec n'est jamais affiché comme un succès |
| IA-04 | Transfert humain (WhatsApp ou RDV) avec horaires configurables | M | Hors horaires, le délai annoncé vient des paramètres, jamais inventé |
| IA-06 | Retour utile / pas utile par réponse, revue dans l'admin | M | Un retour négatif crée une entrée « à revoir » |
| IA-07 | Plafonds : messages par conversation, conversations par visiteur par jour, budget quotidien global, interrupteur d'urgence | M | Plafond atteint → message de repli et boutons humains ; l'interrupteur coupe l'IA sans affecter le site |
| IA-05 | Assistant privé client | L | — |

### 6.5 Démo Lab — LAB
| ID | Exigence | Prio | Critères d'acceptation |
|---|---|---|---|
| LAB-01 | Démo interactive « Boutique » : catalogue, vente en caisse, mouvement de stock, alerte de seuil, facture fictive imprimable, mini tableau de bord | M | Chaque visiteur obtient son propre espace isolé, pré-rempli de données fictives, purgé après 24 h ; aucun e-mail, SMS ou paiement réel |
| LAB-02 | Démos guidées « Gestion scolaire » et « Boulangerie » (vidéo ou parcours de captures commentées) | M | La nature exacte (« démonstration enregistrée ») est indiquée |
| LAB-03 | Conversion contextualisée depuis la démo (diagnostic pré-rempli, WhatsApp avec contexte) | M | Le lead garde la source `lab:retail` |
| LAB-04 | Démo « Boutique » en mode hors-ligne simulé (bascule « connexion coupée », ventes mises en file puis synchronisées) | L | — |

### 6.6 CRM — CRM
| ID | Exigence | Prio | Critères d'acceptation |
|---|---|---|---|
| CRM-01 | Contacts et entreprises, téléphones normalisés en E.164, suggestion de doublons (même téléphone ou e-mail), fusion manuelle journalisée | M | Deux homonymes ne fusionnent jamais automatiquement |
| CRM-02 | Leads avec étapes `new → qualified → meeting → proposal → won / lost / on_hold`, responsable, prochaine action et date | M | Un lead actif sans prochaine action apparaît dans « À traiter » ; chaque changement d'étape est historisé (auteur, date, motif) |
| CRM-03 | Qualification lisible : adéquation (1–3), maturité (issue du diagnostic), budget déclaré, échéance, décideur | M | Aucune décision automatique de rejet |
| CRM-04 | Activités : notes, appels, WhatsApp, e-mails, RDV, tâches avec échéance | M | Les notes restent internes |
| CRM-08 | Vue détail du lead : brief validé, réponses du diagnostic, conversation IA liée, historique, bouton WhatsApp | M | — |
| CRM-06 | Devis versionnés | L | — |

### 6.7 Rendez-vous et communications — RDV / COM
| ID | Exigence | Prio | Critères d'acceptation |
|---|---|---|---|
| RDV-01 | Demande de RDV : motif, format (WhatsApp, appel, visio, présentiel), 1 à 3 créneaux souhaités, fuseau `Africa/Bamako` | M | Statut `requested` ; l'interface ne dit **jamais** « confirmé » tant qu'un humain n'a pas confirmé |
| COM-01 | Notifications e-mail : nouveau lead (équipe), accusé de réception (prospect), RDV confirmé (prospect), lead non traité depuis 24 h ouvrées (admin) | M | File d'envoi rejouable ; statut visible dans l'admin ; aucune donnée sensible dans l'objet |
| COM-02 | WhatsApp Business API | L | — |

### 6.8 Administration, mesure, données personnelles — ADM / MES / PRV
| ID | Exigence | Prio | Critères d'acceptation |
|---|---|---|---|
| ADM-01 | Paramètres : coordonnées, numéros WhatsApp, horaires, destinataires des notifications, activation et budget IA, textes de consentement versionnés | M | Les secrets (clé API) restent dans `.env`, jamais en base ni dans l'admin |
| ADM-02 | Utilisateurs internes, rôles, désactivation, journal d'audit (connexion, changement de rôle, publication, export, fusion, paramètres IA) | M | Toute élévation de rôle et tout export sont journalisés |
| ADM-04 | Éditeur du diagnostic (questions, règles) avec versionnage | M | Une version active est immuable ; toute modification crée une nouvelle version brouillon ; les sessions passées gardent leur version |
| MES-01 | Événements first-party (voir TRD §9), sans outil tiers au MVP | M | Aucun contenu de conversation ni coordonnée dans les événements |
| MES-02 | Tableau de bord direction : entonnoir, sources, délais de prise en charge, coût IA | M | Les chiffres se recalculent depuis les tables sources |
| PRV-01 | Consentements versionnés et séparés (traitement de la demande / marketing / mesure d'audience) | M | Le retrait est tracé |
| PRV-02 | Demandes de droits (accès, rectification, suppression, opposition) : formulaire de contact dédié et traitement manuel dans l'admin, avec export JSON d'un contact et anonymisation | M | L'anonymisation couvre contact, leads, briefs, conversations et fragments liés |
| PRV-03 | Purge automatique : sessions de diagnostic anonymes non converties (30 j), espaces Démo Lab (24 h), conversations non liées à un lead (90 j) [À VALIDER] | M | Commande planifiée testée |

## 7. Règles métier non négociables

1. **Aucune information inventée** : ni réalisation, ni client, ni témoignage, ni statistique, ni prix, ni délai ferme. Une donnée inconnue s'affiche comme inconnue.
2. **L'IA ne fixe jamais de prix**, ne prend aucun engagement contractuel et n'écrit rien sans confirmation explicite.
3. **Les règles déterministes priment** pour les recommandations, les droits et les statuts ; l'IA reformule.
4. **Pas de compte obligatoire** avant une restitution utile.
5. **Le site reste entièrement utilisable sans l'assistant** et sans JavaScript pour la lecture.
6. **Les données de démo sont fictives et isolées**, et étiquetées « Démo — données fictives ».
7. **Consentement marketing séparé** du traitement de la demande, jamais pré-coché.
8. **Monnaie affichée : FCFA** (code ISO `XOF` en base). Fuseau : `Africa/Bamako`.

## 8. Critères de lancement (Go / No-Go)

- Tous les critères d'acceptation **M** passent et la suite de tests est verte.
- Évaluation IA : seuils du §6.4 atteints, et 0 échec sur les cas critiques.
- Lighthouse mobile accueil, solution, diagnostic : Perf ≥ 90, A11y ≥ 95.
- `php artisan kh:content-audit` ne trouve plus aucun `[À VALIDER]` dans les contenus publiés.
- Pages légales validées par la direction ; coordonnées réelles ; au moins 1 réalisation autorisée, ou section masquée.
- Budget IA mensuel et horaires de prise en charge renseignés.
- Les redirections des anciennes URL sont testées.

## 9. Traçabilité vers le cahier des charges v1.0

| Cahier v1.0 | Traitement MVP |
|---|---|
| WEB-01..06 | WEB-01, 02, 03, 05, 06 en M ; WEB-04 en L ; ajouts WEB-07, 08, 09 |
| DIA-01..04 | Repris (DIA-04 du cahier → BRF-01/02) ; ajout DIA-04 pré-remplissage |
| IA-01..06 | IA-01..04 et IA-06 en M ; IA-05 en L ; ajout IA-07 plafonds |
| DEM-01..03 | Remplacés par LAB-01..03 ; LAB-01 interactif **avancé en MVP** |
| CFG-01..03 | L (configurateur et estimation) |
| CRM-01..07 | CRM-01..04 en M ; CRM-05, 06, 07 en L |
| RDV-01/02, COM-01/02 | RDV-01 et COM-01 en M ; RDV-02 et COM-02 en L |
| ESP-*, SUP-01 | L |
| ADM-01..03 | M (simplifiés : 3 rôles) |
| TEN-01/02 | L (P2) |
