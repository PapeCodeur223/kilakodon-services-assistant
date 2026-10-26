# DIAGNOSTIC SPEC — Template v1

> Source du `DiagnosticTemplateV1Seeder`. Format des conditions et des effets : TRD §6. La valeur `__unknown` correspond à « Je ne sais pas ».
> Le moteur est **déterministe** : ces règles décident ; l'IA ne fait que reformuler.

---

## 1. Catalogue des modules (cibles des recommandations)

| Code | Libellé affiché | Solution liée (slug) |
|---|---|---|
| `MOD_STOCK_POS` | Gestion de stock & caisse | `stocks-facturation-caisse` |
| `MOD_INVOICING` | Facturation & devis | `stocks-facturation-caisse` |
| `MOD_CRM` | Suivi clients & relances (CRM) | `erp-crm-sur-mesure` |
| `MOD_ERP_CORE` | ERP intégré (ventes, achats, finances) | `erp-crm-sur-mesure` |
| `MOD_HR_PAYROLL` | RH & paie | `erp-crm-sur-mesure` |
| `MOD_MULTISITE` | Multi-sites & transferts | `stocks-facturation-caisse` |
| `MOD_REPORTING` | Tableaux de bord direction | `applications-web-metiers` |
| `MOD_MOBILE_APP` | Application mobile terrain | `applications-mobiles` |
| `MOD_MOBILE_MONEY` | Paiements Mobile Money | `applications-mobiles` |
| `MOD_OFFLINE` | Fonctionnement hors-ligne | `stocks-facturation-caisse` |
| `MOD_SCHOOL` | Gestion scolaire | `applications-web-metiers` |
| `MOD_BAKERY` | Production boulangerie / restauration | `applications-web-metiers` |
| `MOD_WEB_APP` | Application web métier spécifique | `applications-web-metiers` |
| `MOD_AUDIT` | Audit & migration de l'existant | `audit-modernisation` |
| `MOD_DESIGN` | Identité & présence en ligne | `design-identite-numerique` |

Sélection : on additionne les poids par module, on garde ceux **≥ 3**, triés par poids décroissant (en cas d'égalité : priorité de règle la plus faible), **5 au maximum**. Si aucun module n'atteint 3, on retient le meilleur module s'il a ≥ 2 et on ajoute la clarification `scope_unclear`. Si aucun n'atteint 2, la liste reste vide et seule `scope_unclear` est ajoutée.

## 2. Questions

### Étape 1 — Votre activité
| key | Type | Libellé | Options (`value` : libellé) | Règles |
|---|---|---|---|---|
| `sector` | single | Quel est votre secteur d'activité ? | `retail` : Commerce de détail / boutique · `wholesale` : Commerce de gros / distribution · `school` : École / centre de formation · `bakery_food` : Boulangerie, pâtisserie, restauration · `logistics` : Logistique / transit · `real_estate_construction` : Immobilier / BTP · `health` : Santé / clinique / pharmacie · `services` : Services aux entreprises · `other` : Autre | requis |
| `team_size` | single | Combien de personnes travaillent dans votre structure ? | `1_5` · `6_20` · `21_50` · `51_200` · `200_plus` (libellés « 1 à 5 », etc.) | requis |
| `sites` | single | Sur combien de sites (boutiques, agences, dépôts) ? | `1` : Un seul · `2_3` : 2 à 3 · `4_10` : 4 à 10 · `10_plus` : Plus de 10 | requis |
| `school_size` | single | Combien d'élèves accueillez-vous ? | `lt_300` : Moins de 300 · `300_1000` : 300 à 1 000 · `gt_1000` : Plus de 1 000 | `show_if sector eq school` ; `allow_unknown` |

### Étape 2 — Votre façon de travailler
| key | Type | Libellé | Options | Règles |
|---|---|---|---|---|
| `current_tools` | multi | Avec quels outils gérez-vous votre activité aujourd'hui ? | `paper` : Cahiers / papier · `excel` : Excel ou Google Sheets · `whatsapp` : Groupes WhatsApp · `pos_software` : Logiciel de caisse · `accounting_software` : Logiciel de comptabilité · `erp` : ERP ou logiciel de gestion complet · `custom_app` : Application développée sur mesure · `none` : Rien de formalisé | requis ; `none` exclusif |
| `pains` | multi (max 4) | Qu'est-ce qui vous fait perdre le plus de temps ou d'argent ? | `stock_gaps` : Écarts ou ruptures de stock · `cash_control` : Contrôle de la caisse · `slow_invoicing` : Factures et devis lents · `customer_followup` : Suivi des clients et relances · `management_reports` : Rapports pour la direction · `hr_payroll` : RH et paie · `multisite_coordination` : Coordination entre sites · `field_teams` : Suivi des équipes terrain · `school_fees` : Suivi des frais de scolarité · `school_grades` : Notes et bulletins · `production_waste` : Production et pertes · `online_presence` : Image et visibilité en ligne · `legacy_software` : Logiciel actuel inadapté | requis ; `allow_unknown` (exclusif) ; options `school_*` visibles si `sector = school` ; `production_waste` si `sector = bakery_food` (filtrage d'options : `options[].show_if`) |
| `existing_satisfaction` | single | Votre logiciel actuel vous convient-il ? | `ok` : Globalement oui · `partial` : En partie · `no` : Non, il faut le remplacer | `show_if current_tools contains_any [pos_software, accounting_software, erp, custom_app]` |
| `field_work` | single | Une partie de vos équipes travaille-t-elle hors du bureau ? | `none` : Non · `some` : Quelques personnes · `many` : Une grande partie (commerciaux, livreurs, agents) | requis |

### Étape 3 — Votre contexte
| key | Type | Libellé | Options | Règles |
|---|---|---|---|---|
| `connectivity` | single | Comment est la connexion Internet sur vos sites ? | `stable` : Stable · `unstable` : Parfois coupée · `very_unstable` : Souvent coupée ou absente | requis |
| `mobile_money` | single | Encaissez-vous (ou voulez-vous encaisser) par Mobile Money ? | `yes_now` : Oui, déjà · `wanted` : Nous aimerions · `no` : Non | requis |
| `main_goal` | single | Quel est votre objectif principal ? | `control` : Mieux contrôler (stock, caisse, fraude) · `time` : Gagner du temps · `growth` : Vendre plus / fidéliser · `visibility` : Avoir une vision claire des chiffres · `modernize` : Moderniser un outil existant · `image` : Améliorer notre image | requis |

### Étape 4 — Votre projet
| key | Type | Libellé | Options | Règles |
|---|---|---|---|---|
| `timeline` | single | Quand souhaitez-vous démarrer ? | `asap` : Dès que possible (moins d'1 mois) · `1_3m` : Dans 1 à 3 mois · `3_6m` : Dans 3 à 6 mois · `exploring` : Je me renseigne | requis |
| `budget_band` | single | Avez-vous une enveloppe en tête ? (facultatif) | `lt_500k` : Moins de 500 000 FCFA · `500k_2m` : 500 000 à 2 M FCFA · `2m_5m` : 2 à 5 M FCFA · `5m_15m` : 5 à 15 M FCFA · `gt_15m` : Plus de 15 M FCFA | facultatif ; `allow_unknown` ; aide : « Cela nous aide à proposer une solution réaliste. Ce n'est pas un engagement. » [À VALIDER : tranches] |
| `decision_role` | single | Quel est votre rôle dans la décision ? | `decider` : Je décide · `influencer` : Je prépare la décision · `researcher` : Je me renseigne pour quelqu'un | requis |
| `context_note` | text (500) | Quelque chose à ajouter ? (facultatif) | — | facultatif ; **jamais** envoyé à l'IA sans le reste du DTO ; affiché tel quel dans le brief |

## 3. Score de maturité

Points par outil coché dans `current_tools` (on prend la **somme**, plafonnée à 6) : `none` 0 · `paper` 0 · `whatsapp` 0,5 · `excel` 1 · `pos_software` 2 · `accounting_software` 2 · `custom_app` 2,5 · `erp` 3.
Bonus : `existing_satisfaction = ok` +1.

| Score | Niveau | Libellé | Explication affichée |
|---|---|---|---|
| 0 – 1 | 1 | **Démarrage** | Votre activité repose surtout sur le papier, la mémoire et les messages. Un premier outil bien choisi aura un impact immédiat. |
| 1,5 – 3 | 2 | **Outillé** | Vous utilisez déjà des outils (souvent Excel), mais ils ne communiquent pas entre eux. L'enjeu : centraliser. |
| 3,5 – 5 | 3 | **Structuré** | Vous avez des logiciels en place. L'enjeu : les relier, automatiser et mieux piloter. |
| > 5 | 4 | **Intégré** | Votre équipement est avancé. L'enjeu : optimiser, sécuriser et étendre (mobile, reporting, intégrations). |

Mention sous le niveau : « Indicateur simple basé sur vos outils actuels, pour orienter notre échange. »

## 4. Règles (v1)

Notation : `C:` conditions, `E:` effets (`R(module, poids)`, `P(pain)`, `Q(clarification)`), `X:` explication affichée.

| key | prio | C | E | X |
|---|---|---|---|---|
| `r_stock_pain` | 10 | pains contains `stock_gaps` | R(MOD_STOCK_POS,3) P(stock_gaps) | Vous signalez des écarts ou ruptures de stock. |
| `r_cash_pain` | 10 | pains contains `cash_control` | R(MOD_STOCK_POS,3) P(cash_control) | Le contrôle de la caisse est une difficulté pour vous. |
| `r_retail_base` | 20 | sector in [`retail`,`wholesale`] | R(MOD_STOCK_POS,2) R(MOD_INVOICING,1) | Dans le commerce, stock et caisse sont le socle de la gestion. |
| `r_invoicing` | 15 | pains contains `slow_invoicing` | R(MOD_INVOICING,3) P(slow_invoicing) | Vos factures et devis prennent trop de temps. |
| `r_crm` | 15 | pains contains `customer_followup` | R(MOD_CRM,3) P(customer_followup) | Le suivi de vos clients et des relances vous échappe. |
| `r_growth_crm` | 30 | main_goal eq `growth` | R(MOD_CRM,2) | Votre objectif est de vendre plus et de fidéliser. |
| `r_reports` | 15 | pains contains `management_reports` | R(MOD_REPORTING,3) P(management_reports) | Les rapports pour la direction vous coûtent du temps. |
| `r_visibility_goal` | 30 | main_goal eq `visibility` | R(MOD_REPORTING,2) | Vous voulez une vision claire de vos chiffres. |
| `r_hr` | 15 | pains contains `hr_payroll` | R(MOD_HR_PAYROLL,3) P(hr_payroll) | La gestion RH et la paie sont une difficulté. |
| `r_hr_size` | 40 | team_size in [`51_200`,`200_plus`] | R(MOD_HR_PAYROLL,1) R(MOD_ERP_CORE,1) | Avec plus de 50 personnes, RH et gestion intégrée deviennent clés. |
| `r_multisite` | 10 | sites in [`2_3`,`4_10`,`10_plus`] | R(MOD_MULTISITE,3) | Vous travaillez sur plusieurs sites. |
| `r_multisite_pain` | 15 | pains contains `multisite_coordination` | R(MOD_MULTISITE,2) R(MOD_REPORTING,1) P(multisite_coordination) | La coordination entre vos sites est difficile. |
| `r_field` | 15 | field_work eq `many` **or** pains contains `field_teams` | R(MOD_MOBILE_APP,3) P(field_teams) | Une grande partie de vos équipes travaille sur le terrain. |
| `r_field_some` | 40 | field_work eq `some` | R(MOD_MOBILE_APP,1) | Certaines personnes travaillent hors du bureau. |
| `r_mm` | 20 | mobile_money in [`yes_now`,`wanted`] | R(MOD_MOBILE_MONEY,3) | Vous encaissez, ou voulez encaisser, par Mobile Money. |
| `r_offline` | 10 | connectivity in [`unstable`,`very_unstable`] | R(MOD_OFFLINE,3) Q(offline_scope) | Votre connexion n'est pas toujours fiable. |
| `r_school` | 5 | sector eq `school` | R(MOD_SCHOOL,4) | Vous dirigez un établissement d'enseignement. |
| `r_school_fees` | 10 | pains contains `school_fees` | R(MOD_SCHOOL,1) P(school_fees) | Le suivi des frais de scolarité est une priorité. |
| `r_school_grades` | 10 | pains contains `school_grades` | R(MOD_SCHOOL,1) P(school_grades) | Notes et bulletins vous prennent beaucoup de temps. |
| `r_bakery` | 5 | sector eq `bakery_food` | R(MOD_BAKERY,4) R(MOD_STOCK_POS,1) | Vous êtes dans la boulangerie ou la restauration. |
| `r_bakery_waste` | 10 | pains contains `production_waste` | R(MOD_BAKERY,1) P(production_waste) | Production et pertes sont à mieux maîtriser. |
| `r_specific_sector` | 25 | sector in [`logistics`,`real_estate_construction`,`health`,`services`] | R(MOD_WEB_APP,3) Q(process_mapping) | Votre secteur a des processus spécifiques qui méritent un outil adapté. |
| `r_erp_paper_big` | 30 | team_size in [`21_50`,`51_200`,`200_plus`] **and** current_tools contains_any [`paper`,`excel`,`none`] **and** current_tools contains_none [`erp`] | R(MOD_ERP_CORE,3) | Votre taille dépasse ce que le papier et Excel permettent de piloter. |
| `r_legacy` | 10 | pains contains `legacy_software` **or** existing_satisfaction eq `no` | R(MOD_AUDIT,3) P(legacy_software) Q(data_migration) | Votre logiciel actuel ne vous convient plus. |
| `r_partial` | 30 | existing_satisfaction eq `partial` | R(MOD_AUDIT,2) Q(integration_existing) | Votre outil actuel ne répond qu'en partie à vos besoins. |
| `r_modernize` | 30 | main_goal eq `modernize` | R(MOD_AUDIT,2) | Votre objectif est de moderniser l'existant. |
| `r_excel_migration` | 35 | current_tools contains `excel` | Q(data_migration) | Vos données Excel pourront être reprises. |
| `r_image` | 15 | pains contains `online_presence` **or** main_goal eq `image` | R(MOD_DESIGN,3) P(online_presence) | Vous souhaitez améliorer votre image et votre visibilité. |
| `r_budget_small` | 50 | budget_band eq `lt_500k` | Q(phasing) | Une enveloppe réduite : nous proposerons un démarrage par étapes. |
| `r_unknown_budget` | 50 | budget_band is_unknown **or** budget_band not_answered | Q(budget) | — |
| `r_exploring` | 50 | timeline eq `exploring` | Q(priorities) | — |
| `r_not_decider` | 50 | decision_role in [`influencer`,`researcher`] | Q(decision_makers) | — |

## 5. Libellés des priorités (`pain`) et des clarifications (`clarify`)

**Priorités** (titre, phrase) :
- `stock_gaps` — Fiabiliser le stock : savoir à tout moment ce qui est disponible et éviter les ruptures.
- `cash_control` — Sécuriser la caisse : tracer chaque encaissement par vendeur et moyen de paiement.
- `slow_invoicing` — Accélérer la facturation : des devis et factures propres en quelques clics.
- `customer_followup` — Organiser le suivi client : historique, relances, fidélisation.
- `management_reports` — Automatiser le reporting : des chiffres à jour sans ressaisie.
- `hr_payroll` — Simplifier RH et paie.
- `multisite_coordination` — Coordonner vos sites : une vision consolidée et des transferts tracés.
- `field_teams` — Connecter le terrain : remontée d'information en temps réel.
- `school_fees` — Maîtriser les frais de scolarité et les impayés.
- `school_grades` — Produire notes et bulletins sans y passer des nuits.
- `production_waste` — Ajuster la production et réduire les pertes.
- `online_presence` — Renforcer votre image et votre présence en ligne.
- `legacy_software` — Remplacer ou moderniser un outil devenu inadapté.

Si moins de 3 priorités sont déclenchées, compléter par l'objectif principal (`main_goal`) avec la priorité correspondante : `control` → stock_gaps/cash_control (celle qui n'est pas déjà présente), `time` → management_reports, `growth` → customer_followup, `visibility` → management_reports, `modernize` → legacy_software, `image` → online_presence.

**Clarifications** :
- `offline_scope` — Quelles opérations doivent absolument fonctionner sans connexion ?
- `process_mapping` — Cartographier vos processus métier lors d'un premier atelier.
- `data_migration` — Quelles données reprendre (fichiers, historique) et dans quel état sont-elles ?
- `integration_existing` — Que garder de votre outil actuel et comment le connecter ?
- `phasing` — Définir un premier module prioritaire adapté à votre enveloppe.
- `budget` — Préciser une enveloppe indicative pour proposer une solution réaliste.
- `priorities` — Identifier le besoin le plus urgent pour démarrer.
- `decision_makers` — Qui doit être associé à la décision ?
- `scope_unclear` — Préciser votre besoin lors d'un court échange.

## 6. Cas de test obligatoires (Pest)

| # | Réponses clés | Attendu |
|---|---|---|
| T1 | retail, 6_20, sites 2_3, tools [excel, whatsapp], pains [stock_gaps, cash_control], connectivity unstable, mm wanted | Modules : STOCK_POS (8), MULTISITE (3), OFFLINE (3), MOBILE_MONEY (3) ; maturité 1,5 → niveau 2 ; priorités stock_gaps, cash_control + complément selon l'objectif |
| T2 | school, 21_50, 1 site, tools [paper, excel], pains [school_fees, school_grades] | SCHOOL en tête (6) ; ERP_CORE (3) via r_erp_paper_big ; clarification data_migration |
| T3 | bakery_food, 6_20, sites 4_10, pains [production_waste] | BAKERY (5), MULTISITE (3) ; STOCK_POS seulement 1 → absent |
| T4 | logistics, tools [erp], existing_satisfaction no | WEB_APP (3), AUDIT (3) ; maturité 3 → niveau 2 ; clarifications process_mapping, data_migration |
| T5 | other, 1_5, 1 site, tools [none], pains [online_presence], goal image | DESIGN (3) ; maturité 0 → niveau 1 |
| T6 | other, 1_5, 1 site, tools [paper], pains [customer_followup] uniquement, goal time | CRM (3) seul |
| T7 | Changer `sector` de school à retail après avoir répondu `school_size` | `school_size` et les pains `school_*` sont supprimés des réponses |
| T8 | Mêmes réponses deux fois | Résultats JSON strictement identiques |
| T9 | other, 1_5, 1 site, tools [excel], pains `__unknown`, field none, stable, mm no, goal growth | Aucun module ≥ 3 → CRM (2) retenu + clarification scope_unclear |
| T10 | Comme T9 mais goal time | Aucun module ≥ 2 → liste vide, message « Votre besoin mérite un court échange pour être précisé » + scope_unclear (jamais de recommandation inventée) |
