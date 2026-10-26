<?php

// Source : docs/CONTENT.md §6, §7 et §10. Les pages légales sont des squelettes à valider
// par la direction et un conseil juridique : elles restent en brouillon.
return [
    [
        'slug' => 'methode',
        'template' => 'default',
        'title' => 'Une méthode claire, *du premier échange au support*',
        'seo_title' => 'Notre méthode de projet logiciel',
        'seo_description' => 'De l\'écoute au support : six étapes claires, un livrable validé par vous à chaque étape. La méthode Kilakodon pour sécuriser votre projet logiciel.',
        'body' => <<<'MD'
Chaque projet suit les mêmes étapes. À chacune, vous savez ce qui est attendu de vous, ce que nous faisons, et ce que vous recevez.

## 1. Écoute & cahier des charges

Nous partons de votre terrain : vos processus, vos contraintes, vos priorités. Vous validez un périmètre clair avant tout développement.

**Livrable :** cahier des charges et devis détaillé.

## 2. Conception

Nous dessinons les écrans avec ceux qui les utiliseront, avant d'écrire la moindre ligne de code.

**Livrable :** maquettes validées.

## 3. Développement par modules

Vous voyez le logiciel avancer : démonstration régulière de chaque module et ajustements au fil de l'eau.

**Livrable :** démonstrations régulières [À VALIDER : fréquence, l'ancien site disait « tests hebdomadaires »].

## 4. Tests & recette

Nous testons avec vos vraies situations avant la mise en service. Rien n'est livré sans votre validation.

**Livrable :** procès-verbal de recette signé.

## 5. Mise en service & formation

Installation, formation de vos équipes et migration de vos données.

**Livrable :** logiciel installé, équipes formées, données migrées.

## 6. Support & évolutions

Nous restons joignables après la mise en service et faisons évoluer le logiciel avec vous.

**Livrable :** contrat de maintenance [À VALIDER : formules].

## Nos engagements

- Un périmètre écrit
- Votre validation à chaque étape
- La documentation remise
- Un interlocuteur dédié
MD,
    ],
    [
        'slug' => 'a-propos',
        'template' => 'default',
        'title' => 'Une équipe de Bamako qui *construit* des logiciels utiles',
        'seo_title' => 'À propos de Kilakodon Services',
        'seo_description' => 'Kilakodon Services conçoit à Bamako des logiciels de gestion et des solutions numériques pour les entreprises du Mali et d\'Afrique de l\'Ouest.',
        'body' => <<<'MD'
Kilakodon Services conçoit des logiciels de gestion et des solutions numériques pour les entreprises du Mali et d'Afrique de l'Ouest.

Notre conviction : un bon logiciel part du terrain, se construit avec ceux qui l'utiliseront, et doit fonctionner dans les conditions réelles — coupures de réseau, téléphones d'entrée de gamme, paiements Mobile Money compris.

## Notre histoire

[À VALIDER : date de création]

## Notre équipe

[À VALIDER : prénoms, rôles, photos réelles]

## Nos domaines d'expertise

[À VALIDER : domaines d'expertise techniques]

## Notre mission

[À VALIDER : mission]
MD,
    ],
    [
        'slug' => 'mentions-legales',
        'template' => 'legal',
        'title' => 'Mentions légales',
        'seo_title' => 'Mentions légales',
        'seo_description' => 'Mentions légales du site Kilakodon Services : éditeur, directeur de la publication, hébergeur et propriété intellectuelle.',
        'body' => <<<'MD'
> Squelette à valider par la direction et un conseil juridique (Q-001).

## Éditeur du site

- Raison sociale et forme : [À VALIDER]
- Capital : [À VALIDER]
- RCCM : [À VALIDER]
- NIF : [À VALIDER]
- Adresse : [À VALIDER : adresse précise], Bamako, Mali
- Téléphone : +223 74 66 64 89
- E-mail : kilakodon@gmail.com

## Directeur de la publication

[À VALIDER]

## Hébergeur

LWS — [À VALIDER : dénomination et adresse]

## Propriété intellectuelle

[À VALIDER]
MD,
    ],
    [
        'slug' => 'confidentialite',
        'template' => 'legal',
        'title' => 'Politique de confidentialité',
        'seo_title' => 'Politique de confidentialité',
        'seo_description' => 'Comment Kilakodon Services collecte, utilise et protège vos données personnelles, et comment exercer vos droits.',
        'body' => <<<'MD'
> Squelette à valider par la direction et un conseil juridique (Q-011).

## Responsable du traitement

[À VALIDER : raison sociale et coordonnées]

## Données collectées et finalités

- **Demande de contact** : nom, téléphone, e-mail facultatif, message.
- **Diagnostic et brief** : réponses au diagnostic et coordonnées si vous demandez votre brief.
- **Rendez-vous** : coordonnées et créneaux souhaités.
- **Assistant** : messages échangés avec l'assistant.
- **Mesure d'audience** : uniquement si vous l'acceptez.

## Bases du traitement

[À VALIDER]

## Destinataires

L'équipe Kilakodon, l'hébergeur du site, et le fournisseur d'IA (Anthropic) pour les messages envoyés à l'assistant.

## Durées de conservation

- Sessions de diagnostic anonymes non converties : 30 jours [À VALIDER]
- Conversations non liées à une demande : 90 jours [À VALIDER]
- Espaces du Démo Lab : 24 heures

## Vos droits

Vous pouvez demander l'accès, la rectification, la suppression de vos données ou vous opposer à leur traitement. [À VALIDER : moyen d'exercer ces droits]

## Cadre légal

Loi malienne n° 2013-015 relative à la protection des données à caractère personnel et Autorité de Protection des Données à caractère Personnel (APDP). [À VALIDER par un conseil]
MD,
    ],
    [
        'slug' => 'cookies',
        'template' => 'legal',
        'title' => 'Cookies',
        'seo_title' => 'Politique cookies',
        'seo_description' => 'Les cookies utilisés sur le site Kilakodon Services : strictement nécessaires, mesure d\'audience first-party si vous l\'acceptez, aucun cookie publicitaire.',
        'body' => <<<'MD'
> Squelette à valider par la direction et un conseil juridique (Q-011).

## Cookies strictement nécessaires

Session, protection contre la falsification de requêtes (CSRF) et espace du Démo Lab. Ils sont indispensables au fonctionnement du site.

## Mesure d'audience

Mesure first-party, uniquement si vous l'acceptez. [À VALIDER : régime exact du consentement]

## Publicité

Aucun cookie publicitaire.
MD,
    ],
];
