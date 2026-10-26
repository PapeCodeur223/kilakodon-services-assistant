---
name: kh-diagnostic-rules
description: Implémenter, modifier ou étendre le moteur de diagnostic de Kilakodon Hub (questions, conditions, règles, score de maturité, versionnage). À utiliser pour tout travail sur app/Domain/Diagnostic ou DIAGNOSTIC_SPEC.md.
---

# Travailler sur le diagnostic

Références : `docs/DIAGNOSTIC_SPEC.md`, `docs/TRD.md` §6, `docs/BACKEND_SCHEMA.md` §4.

## Invariants
- Le moteur (`ConditionEvaluator`, `VisibilityResolver`, `RuleEngine`, `MaturityScorer`, `PainCompleter`) est du **PHP pur**, sans Eloquent ni IA, entièrement testé unitairement.
- **Même entrée = même sortie** (ordre stable : poids décroissant, puis priorité de règle, puis clé).
- Chaque recommandation porte ses `reasons` (`rule_key`, `explanation`, `question_keys`).
- Un template `active` ou `retired` est **immuable**. Toute modification = dupliquer en nouvelle version `draft`, puis l'activer (Action `ActivateDiagnosticTemplate`). Les sessions gardent leur version.
- Quand une réponse change, les réponses des questions devenues invisibles (et les options devenues invisibles dans un `multi`) sont supprimées.

## Ajouter ou modifier une règle
1. Mettre à jour `docs/DIAGNOSTIC_SPEC.md` (tableau des règles) **d'abord**.
2. Écrire un test positif et un test négatif.
3. Vérifier que les cas T1–T10 passent toujours ; ajuster le cas attendu **seulement** si le changement est voulu et documenté.
4. Mettre à jour le seeder de la version concernée (ou créer `DiagnosticTemplateV2Seeder` pour une nouvelle version).

## Ajouter un opérateur de condition
Implémenter dans `ConditionEvaluator`, ajouter le test de table de vérité, puis documenter dans TRD §6.

## IA et diagnostic
L'IA reçoit uniquement le DTO `DiagnosticOutcome` (sans coordonnées) pour un résumé de 80–120 mots. Elle ne peut ni ajouter ni retirer de module. En cas d'échec : résumé gabarit.
