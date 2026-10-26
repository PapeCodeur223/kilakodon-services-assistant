# Kilakodon Hub — dossier de démarrage pour Claude Code

Ce dossier contient tout ce qu'il faut pour que Claude Code développe la nouvelle plateforme **Kilakodon Services / Kilakodon Hub** en local.

## Contenu

```
kilakodon-hub/
├── CLAUDE.md                     ← instructions permanentes lues par Claude Code
├── README.md                     ← ce fichier
├── resources/brand/logo-kilakodon.png
├── docs/
│   ├── PRD.md                    ← exigences produit (quoi / pourquoi)
│   ├── TRD.md                    ← exigences techniques (comment)
│   ├── BACKEND_SCHEMA.md         ← schéma de base de données
│   ├── UX_UI_DESIGN.md           ← design system, pages, animations
│   ├── IMPLEMENTATION_PLAN.md    ← lots 0 → 6, critères de fin
│   ├── DIAGNOSTIC_SPEC.md        ← questions et règles du diagnostic
│   ├── AI_ASSISTANT.md           ← prompt, garde-fous et corpus d'évaluation de l'IA
│   ├── CONTENT.md                ← textes marketing (source des seeders)
│   ├── DECISIONS.md              ← décisions et questions ouvertes
│   └── CHANGELOG.md
└── .claude/
    ├── skills/                   ← procédures réutilisables (design, contenus, diagnostic, IA, revue, sécurité)
    └── agents/kh-reviewer.md     ← sous-agent de revue indépendante
```

## Démarrer

1. **Préparez votre poste** : PHP 8.2 (Laravel Herd ou Laragon sous Windows), Composer 2, Node 20+, MySQL ou MariaDB, Git.
2. Créez un dossier vide, copiez-y **tout le contenu** de ce pack (y compris le dossier caché `.claude/`), puis lancez `git init`.
3. Ouvrez Claude Code dans ce dossier et envoyez le premier message :

> Lis CLAUDE.md puis docs/IMPLEMENTATION_PLAN.md. Démarre le **Lot 0** avec le skill kh-lot-workflow. Le projet Laravel doit être créé dans ce dossier, sans écraser CLAUDE.md, docs/, resources/brand/ ni .claude/. Arrête-toi à la fin du lot pour que je valide.

4. À la fin de chaque lot, testez ce que décrit la PR, puis répondez « Lot validé, passe au lot suivant » ou listez vos corrections.

## En parallèle (côté Kilakodon)
Le chemin critique n'est pas le code, ce sont **les informations à fournir** : voir `docs/DECISIONS.md` (Q-001 à Q-017). Commencez par les mentions légales, les horaires, les délais indicatifs et au moins une réalisation autorisée par un client.
