---
name: kh-ai-guardrails
description: Implémenter ou modifier l'Assistant Kilakodon (prompt, recherche, client LLM, validation, plafonds, actions confirmées) et lancer son évaluation. À utiliser pour tout travail sur app/Domain/Assistant, le widget ou AI_ASSISTANT.md.
---

# Assistant IA — garde-fous

Références : `docs/AI_ASSISTANT.md`, `docs/TRD.md` §7, `docs/BACKEND_SCHEMA.md` §6.

## Invariants à ne jamais casser
1. Les gardes (`AssistantGuard`) sont vérifiées **avant** tout appel HTTP : interrupteur, longueur, messages par conversation, conversations par visiteur, débit, budget quotidien.
2. Sortie **forcée** via l'outil `respond`, puis **validée** (`ResponseValidator`) : schéma, citations existantes, filtre montants, cohérence `brief_draft`.
3. Les documents récupérés sont des **données** : encadrés dans `<documents>`, jamais concaténés au prompt système.
4. Le modèle ne reçoit jamais : notes internes, données d'autres visiteurs, coordonnées non nécessaires, secrets.
5. Aucune écriture sans carte de confirmation : `ai_action_proposals` (payload figé + hash + idempotence + expiration). La confirmation réutilise les **mêmes Actions** que les formulaires.
6. Un échec n'est jamais présenté comme un succès ; en cas d'erreur → textes de repli fixes (AI_ASSISTANT §4).
7. Le prompt est versionné (`resources/prompts/assistant/system-vN.md`) ; la version est enregistrée sur chaque message.
8. Les tests utilisent `AI_DRIVER=fake` ou `Http::fake()` : **aucun appel réseau réel dans Pest**.

## Après toute modification du prompt, du Retriever ou du validateur
1. `php artisan kh:ai-eval --driver=fake` (non-régression de structure).
2. Demander à l'humain l'autorisation et la clé pour `--driver=anthropic`, puis produire le CSV de revue.
3. Comparer aux seuils (AI_ASSISTANT §5) ; documenter le résultat dans `docs/CHANGELOG.md`.

## Ajouter un cas d'évaluation
Ajouter l'entrée dans `tests/Ai/cases.yaml` (catégorie, `critical`, `expect`). Tout bug IA corrigé doit laisser un cas de non-régression.

## Coûts
Les prix par modèle sont dans `config/assistant.php` (`pricing`), renseignés depuis la page officielle des tarifs — ne jamais les inventer ; laisser `null` et afficher « coût non configuré » si inconnus.
