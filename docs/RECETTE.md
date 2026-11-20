# Recette du MVP — Kilakodon Hub (lot 6)

Ce document vérifie les parcours P1 à P5 (PRD §5) et les critères de lancement (PRD §8), **en local**. Chaque ligne indique sa preuve : un test automatique (`composer kh:check`) ou des étapes à refaire à la main. La mise en production (lot 7) n'est pas couverte ici.

Comptes de démonstration (local uniquement) : `admin@kilakodon.test`, `sales@kilakodon.test`, `editor@kilakodon.test`, mot de passe `kilakodon-dev-2026`.

---

## 1. Parcours

### P1 — Découvrir → Diagnostic → Brief → Contact
| Étape | Preuve automatique | Vérification manuelle |
|---|---|---|
| Accueil et solutions lisibles sans JavaScript | `PublicPagesTest`, `PublicContentTest` | Désactiver JavaScript dans le navigateur, puis parcourir l'accueil |
| 8 à 14 questions, progression, retour arrière, secteur prérempli | `DiagnosticJourneyTest` (DIA-04, questions obligatoires, « Je ne sais pas ») | `/diagnostic` : répondre, revenir en arrière |
| Restitution sans coordonnées | `DiagnosticJourneyTest` « parcours P1 complet » | Vérifier qu'aucun champ de contact n'est demandé avant le résultat |
| Coordonnées, consentements séparés, marketing non coché | `BriefFlowTest` (P1), `ContactFormTest` | La case marketing est décochée par défaut |
| Brief corrigé et validé, référence KLK, lien 30 j, PDF, WhatsApp, RDV | `BriefFlowTest` (BRF-02, BRF-04), `ReferenceGeneratorTest`, `DiagnosticJourneyTest` | Ouvrir le lien privé, corriger, valider, télécharger le PDF |
| Lead créé et attribué, équipe prévenue, accusé de réception | `BriefFlowTest`, `OutboundMessagesTest` | Admin → Commercial → Leads, puis Réglages → Envois |
| Mesure du parcours (sans coordonnées) | `AnalyticsTest` « parcours de diagnostic » | Pilotage → Tableau de bord direction |

### P2 — Assistant → Brief ou humain
| Étape | Preuve automatique |
|---|---|
| Réponse sourcée, version du prompt enregistrée | `AssistantApiTest` « répond, enregistre… », `RetrieverTest` |
| « Je ne sais pas » plutôt qu'une invention | corpus `kh:ai-eval` (catégorie *nosrc*) ; évaluation réelle 62/69 acceptée (D-024) |
| Brief préparé, montré, confirmé avant tout envoi | `AssistantApiTest` (proposition, payload figé, double confirmation, annulation) |
| Transfert WhatsApp / RDV | `AssistantApiTest` « transmet une demande de rendez-vous… » |

Vérification manuelle : ouvrir l'assistant, demander « Combien coûte un logiciel de caisse ? ». L'assistant ne donne aucun prix et propose le diagnostic.

### P3 — Démo Lab → Contact contextualisé
| Étape | Preuve automatique | Vérification manuelle |
|---|---|---|
| Vente, stock qui baisse, alerte, facture fictive | `DemoLabPagesTest`, `DemoLabActionsTest` | `/demo-lab/boutique` : vendre 2 huiles 1 L, ouvrir une facture |
| Carte « Vous voulez ça pour votre entreprise ? » → diagnostic `retail` | `DemoLabPagesTest`, `BriefFlowTest` (LAB-03) | Faire 2 missions, cliquer la carte |
| Données isolées par visiteur | `DemoLabActionsTest`, `DemoLabArchTest` | Ouvrir la démo dans deux navigateurs différents |

### P4 — Traitement commercial (interne)
| Étape | Preuve automatique |
|---|---|
| Doublons suggérés, jamais fusionnés automatiquement | `BriefFlowTest` « suggère un doublon… », `LeadPipelineTest` (CRM-01) |
| Attribution, qualification, étapes avec motif | `LeadPipelineTest` (CRM-02, CRM-04) |
| RDV demandé → confirmé par un humain | `AppointmentTest`, `AppointmentFormTest` |
| Gagné / perdu avec motif | `LeadPipelineTest` |
| Droits par rôle | `PoliciesTest`, `CrmResourcesTest` |

### P5 — Indisponibilité de l'IA
| Étape | Preuve automatique | Vérification manuelle |
|---|---|---|
| Interrupteur coupé : aucun appel, site intact | `AssistantApiTest` « n'appelle jamais le modèle… » | Réglages → Paramètres → couper l'assistant ; le bouton disparaît, le reste du site fonctionne |
| Budget atteint : message clair et boutons de repli | `AssistantApiTest` « coupe l'assistant une fois le budget… » | — |
| Réponse en panne : texte de repli, rien d'inventé | `AssistantApiTest` « remplace une réponse en panne… » | — |

## 2. Mesure et données personnelles (lot 6)
| Exigence | Preuve |
|---|---|
| MES-01 — liste blanche, aucune coordonnée, rien mesuré dans le navigateur sans accord, clé visiteur seulement après accord | `AnalyticsTest` ; vérifié dans Chrome le 2026-11-19 : bannière, cookies posés après « Accepter », clé visiteur absente avant l'accord et présente après |
| MES-02 — tableau de bord recalculé depuis les tables sources | `DirectionDashboardTest` |
| PRV-01 — consentements séparés, retrait tracé | `AnalyticsTest` (accord, retrait), `BriefFlowTest` |
| PRV-02 — formulaire dédié, export JSON, anonymisation complète | `PrivacyRequestPagesTest`, `PrivacyActionsTest` |
| PRV-03 — purges planifiées | `PrivacyActionsTest`, `DemoLabPagesTest`, `SessionTest` |

## 3. Critères de lancement (PRD §8)
| Critère | État en local |
|---|---|
| Critères **M** et suite de tests verte | ✅ `composer kh:check` (voir CHANGELOG du lot 6) |
| Évaluation IA : seuils et 0 échec critique | ⚠️ 62/69 ; 3 cas critiques de formulation acceptés par la direction (D-024). Un resserrement du prompt (`sys-v4`) est possible |
| Lighthouse mobile ≥ 90 (perf) et ≥ 95 (accessibilité) | ✅ PageSpeed Insights sur le site en ligne (2026-11-20, mobile) : 100 performances, 100 accessibilité, 100 bonnes pratiques, 100 SEO |
| `kh:content-audit` : 0 `[À VALIDER]` publié | ✅ 0, brouillons compris (2026-11-19) |
| Pages légales validées, coordonnées réelles, au moins 1 réalisation autorisée | ⚠️ Coordonnées réelles ✅ ; 10 réalisations autorisées ✅ ; pages légales : réglage par défaut retenu (D-026), validation juridique à prévoir avant la production |
| Budget IA mensuel et horaires renseignés | ✅ Budget mensuel 1 $ et quotidien 0,50 $ (D-023, D-026), horaires validés (D-015) |
| Redirections des anciennes URL testées | ✅ `SeoTest` (301) |

## 4. Décisions de la direction (2026-11-19, D-026) et points restants
- Tranchés : réglage par défaut des pages légales et de la mesure (désactivée jusqu'à acceptation) ; durées de conservation 30 j / 24 h / 90 j ; demandeur recontacté sous 24 h ; budget IA mensuel 1 $ ; démos guidées en captures (noms masqués) ; Lighthouse reporté au lot 7.
- Restent pour le lot 7 : validation juridique des pages légales avant la production ; mesures Lighthouse sur la préproduction ; cibles des indicateurs à 3 mois (PRD §3.3).
