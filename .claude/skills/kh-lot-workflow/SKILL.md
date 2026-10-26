---
name: kh-lot-workflow
description: Démarrer et exécuter un lot du plan d'implémentation de Kilakodon Hub (lots 0 à 6). À utiliser au début de chaque lot ou quand l'utilisateur dit « passe au lot suivant ».
---

# Exécuter un lot

## Préparation
1. Relire `CLAUDE.md` et la section du lot dans `docs/IMPLEMENTATION_PLAN.md`.
2. Lire **toutes** les sections de documentation citées par le lot (« Docs : »). Ne pas travailler de mémoire.
3. Vérifier que le lot précédent est marqué validé dans le tableau « Suivi » du plan. Sinon, demander.
4. Créer la branche `lot-<n>-<slug>`.
5. Transformer chaque case `- [ ]` du lot en tâche suivie, en ajoutant pour chacune les tests associés.

## Exécution
- Ordre recommandé : migrations et modèles → enums → Actions (avec tests) → politiques → ressources Filament → vues publiques / Livewire → seeders → commandes et planification.
- Pour toute logique métier : écrire le test Pest **avant** l'implémentation (moteurs, transitions, idempotence, permissions).
- Interfaces : charger `kh-design-system`. Textes : `kh-content-copy`. Diagnostic : `kh-diagnostic-rules`. IA : `kh-ai-guardrails`.
- Lancer `composer kh:check` régulièrement, pas seulement à la fin.
- Commits petits, en conventional commits français.
- Toute interprétation ou contradiction → une ligne dans le journal de `docs/DECISIONS.md`.

## Points d'arrêt obligatoires (demander à l'humain)
- Décision commerciale (prix, délai, promesse), texte juridique, clé API réelle, production, suppression de données, dépendance non listée dans TRD §2.

## Clôture
Appliquer le skill `kh-lot-review`, puis s'arrêter et présenter le résumé du lot.
