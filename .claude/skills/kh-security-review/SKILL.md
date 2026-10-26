---
name: kh-security-review
description: Revue de sécurité ciblée de Kilakodon Hub (endpoints publics, tokens, politiques, IA, Démo Lab, données personnelles). À exécuter avant de clôturer les lots 2, 4 et 6.
---

# Revue de sécurité

Parcourir chaque point, prouver par un test ou une lecture de code, et lister les écarts avec leur gravité (bloquant / majeur / mineur).

## Accès et rôles
- [ ] Chaque ressource Filament a une Policy ; tests pour `admin`, `sales` et `editor` (lecture, création, modification, suppression, actions personnalisées).
- [ ] MFA obligatoire pour `admin` ; limitation des tentatives de connexion.
- [ ] Aucun accès possible à un brief, une session ou un espace démo par ID séquentiel.

## Tokens publics
- [ ] 32 octets aléatoires, base64url, stockés en SHA-256, comparaison `hash_equals`, expiration et révocation testées, réponse 404 identique pour un token inexistant ou expiré (sauf la page « lien expiré » quand le hash existe).

## Entrées et sorties
- [ ] Form Requests partout ; limites de longueur ; honeypot et throttle sur les formulaires publics.
- [ ] Aucun `{!! !!}` sur une donnée utilisateur ; Markdown en mode sans HTML ; liens de l'assistant filtrés sur le domaine.
- [ ] En-têtes de sécurité présents (CSP, X-Frame-Options, Referrer-Policy, Permissions-Policy).
- [ ] Pas de fuite de stack trace (`APP_DEBUG=false` testé en mode production simulé).

## IA
- [ ] Gardes avant appel ; payload d'action haché et vérifié ; idempotence ; cas d'injection du corpus réussis.
- [ ] Clé API uniquement dans `.env` ; jamais journalisée.

## Démo Lab
- [ ] Global scope `workspace_id` sur tous les modèles `Demo*` (test d'architecture) ; aucun envoi de message ; limites actives.

## Données personnelles
- [ ] IP stockées hachées (HMAC) ; aucune coordonnée dans `analytics_events` ni dans les logs ; anonymisation couvrant contact, briefs, conversations et tokens ; consentements en écriture seule.

## Dépendances
- [ ] `composer audit` et `npm audit --omit=dev` sans vulnérabilité haute non traitée.

Consigner le résultat dans la PR du lot et créer les tâches correctives.
