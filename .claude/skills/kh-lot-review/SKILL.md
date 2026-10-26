---
name: kh-lot-review
description: Checklist de fin de lot pour Kilakodon Hub — vérifier critères d'acceptation, tests, qualité, accessibilité et documentation avant de demander la validation humaine.
---

# Clôturer un lot

## 1. Vérifications automatiques
- [ ] `composer kh:check` vert (Pint, Larastan niveau 6, Pest).
- [ ] `php artisan migrate:fresh --seed` passe sur une base vide.
- [ ] `php artisan kh:content-audit` exécuté : noter le nombre de `[À VALIDER]` restants (ne bloque pas avant le lot 6).
- [ ] Lots 4 et suivants : `php artisan kh:ai-eval --driver=fake` sans régression.

## 2. Critères d'acceptation
- Reprendre chaque critère du lot (IMPLEMENTATION_PLAN) et chaque exigence PRD citée (ID).
- Pour chacun, indiquer **comment** il est prouvé : nom du test, ou étapes manuelles. Un critère sans preuve n'est pas terminé.

## 3. Contrôles transverses
- [ ] Aucune donnée inventée (client, chiffre, prix, témoignage) dans le code, les seeders ou les vues.
- [ ] Aucun secret ni `.env` dans Git ; `.env.example` à jour.
- [ ] Politiques testées pour `admin`, `sales` et `editor` sur les nouvelles ressources.
- [ ] Pages publiques nouvelles : `x-kh.seo`, lisibles sans JS, navigables au clavier, contrastes respectés, `prefers-reduced-motion` respecté.
- [ ] Pas de couleur en dur, pas de requête N+1 évidente (`Model::preventLazyLoading()` actif en local).
- [ ] Les textes publics viennent de `docs/CONTENT.md` ou suivent `kh-content-copy`.

## 4. Revue indépendante
Pour les lots 2, 4, 5 et 6 : déléguer une revue au sous-agent `kh-reviewer` en lui donnant le numéro du lot et le diff (`git diff main...HEAD --stat`). Corriger les points bloquants.

## 5. Documentation et livraison
- [ ] `docs/CHANGELOG.md` : entrée du lot.
- [ ] `docs/DECISIONS.md` : écarts et interprétations notés.
- [ ] Mise à jour du tableau « Suivi » (statut « en revue »).
- [ ] PR (ou résumé si pas de remote) : ce qui a été fait, **comment tester pas à pas**, captures si UI, limites connues, questions pour l'équipe.

Puis **s'arrêter** et attendre la validation.
