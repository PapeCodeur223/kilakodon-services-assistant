<?php

// Textes de l'accueil — source : docs/CONTENT.md §2.
// Un élément contenant « [À VALIDER] » n'est affiché qu'en prévisualisation équipe (jamais au public)
// et apparaît dans `php artisan kh:content-audit`.
return [
    'hero' => [
        'eyebrow' => 'Logiciels de gestion sur mesure — Bamako',
        'title' => 'Pilotez votre entreprise avec des outils *faits pour elle*.',
        'lead' => 'Stock, caisse, clients, factures, équipes : nous concevons les logiciels qui vous donnent enfin une vision claire de votre activité — même quand la connexion coupe. Commencez par un diagnostic gratuit : en 3 minutes, vous saurez ce dont votre entreprise a vraiment besoin.',
        'cta_primary' => 'Faire mon diagnostic gratuit',
        'cta_secondary' => 'Essayer le Démo Lab',
        'reassurance' => '3 minutes · sans inscription · résultat immédiat',
    ],

    'realities' => [
        'title' => 'Conçu pour la façon dont on travaille ici',
        'items' => [
            ['icon' => 'wifi-off', 'title' => 'Fonctionne hors-ligne', 'text' => 'La caisse continue quand Internet coupe ; tout se synchronise au retour du réseau.'],
            ['icon' => 'smartphone', 'title' => 'Mobile Money intégré', 'text' => 'Orange Money, Moov Money, Wave… selon vos besoins et les accès disponibles. [À VALIDER : opérateurs réellement intégrés]'],
            ['icon' => 'banknote', 'title' => 'En FCFA, à vos règles', 'text' => 'Factures, taxes et rapports adaptés à votre activité.'],
            ['icon' => 'map-pin', 'title' => 'Une équipe à Bamako', 'text' => 'Un interlocuteur qui se déplace, forme vos équipes et répond en français.'],
        ],
    ],

    'recognize' => [
        'title' => 'Les signes qu\'il est temps de *passer à un vrai outil*',
        'items' => [
            ['icon' => 'package', 'quote' => 'Je ne sais jamais exactement ce qu\'il reste en stock.', 'answer' => 'Un stock à jour à chaque vente, avec alertes avant la rupture.', 'solution' => 'stocks-facturation-caisse', 'solution_label' => 'Stocks & caisse'],
            ['icon' => 'coins', 'quote' => 'Les chiffres de la caisse ne collent pas en fin de journée.', 'answer' => 'Chaque vente tracée, par vendeur et par moyen de paiement.', 'solution' => 'stocks-facturation-caisse', 'solution_label' => 'Stocks & caisse'],
            ['icon' => 'users', 'quote' => 'Mes clients et mes relances sont dans mon téléphone.', 'answer' => 'Un fichier clients partagé, des relances qui ne s\'oublient plus.', 'solution' => 'erp-crm-sur-mesure', 'solution_label' => 'ERP & CRM'],
            ['icon' => 'chart-line', 'quote' => 'Je passe mes soirées à faire des rapports sur Excel.', 'answer' => 'Des tableaux de bord prêts chaque matin, même à distance.', 'solution' => 'applications-web-metiers', 'solution_label' => 'Applications métiers'],
        ],
    ],

    'solutions' => [
        'title' => 'Six expertises, *un seul interlocuteur*',
        'lead' => 'Du logiciel de caisse à l\'ERP complet, nous construisons ce dont vous avez besoin — ni plus, ni moins.',
    ],

    'diagnostic' => [
        'eyebrow' => 'Diagnostic gratuit',
        'title' => 'Savoir quoi construire avant de *dépenser un franc*',
        'steps' => [
            ['title' => 'Répondez à quelques questions', 'text' => 'Votre activité, vos outils actuels, vos priorités. Environ 3 minutes.'],
            ['title' => 'Recevez une analyse immédiate', 'text' => 'Votre niveau d\'équipement numérique, vos 3 priorités et les modules recommandés, avec l\'explication de chaque recommandation.'],
            ['title' => 'Repartez avec votre brief', 'text' => 'Un document clair, à partager avec vos associés, qui sert de base à notre échange.'],
        ],
        'cta' => 'Commencer le diagnostic',
        'note' => 'Aucune inscription demandée pour voir le résultat.',
    ],

    'lab' => [
        'title' => 'Ne nous croyez pas sur parole : *essayez*',
        'text' => 'Faites une vente, regardez le stock baisser, déclenchez une alerte, imprimez une facture. Le Démo Lab est un vrai logiciel, avec des données fictives, que vous pouvez manipuler librement.',
        'cta' => 'Ouvrir la démo Boutique',
    ],

    'method' => [
        'title' => 'Une méthode qui *sécurise* votre projet',
        'steps' => [
            ['title' => 'Écoute & cahier des charges', 'text' => 'Nous partons de votre terrain : vos processus, vos contraintes, vos priorités. Vous validez un périmètre clair avant tout développement.'],
            ['title' => 'Développement par modules', 'text' => 'Vous voyez le logiciel avancer : démonstration régulière de chaque module et ajustements au fil de l\'eau.'],
            ['title' => 'Tests & recette', 'text' => 'Nous testons avec vos vraies situations avant la mise en service. Rien n\'est livré sans votre validation.'],
            ['title' => 'Lancement & accompagnement', 'text' => 'Installation, formation de vos équipes, migration de vos données et support après la mise en service.'],
        ],
        'cta' => 'Découvrir notre méthode',
    ],

    'why' => [
        'accent' => ['title' => 'Un logiciel n\'est utile que s\'il est *utilisé*.', 'text' => 'Nous concevons des outils simples pour vos équipes, nous les formons, et nous restons joignables après la livraison.'],
        'cards' => [
            ['title' => 'Sur mesure, pas « presque adapté »', 'text' => 'Nous partons de vos processus au lieu de vous imposer ceux d\'un logiciel générique.'],
            ['title' => 'Vos données vous appartiennent', 'text' => 'Export possible à tout moment, documentation fournie. [À VALIDER : conditions de propriété du code et des données]'],
            ['title' => 'Transparence', 'text' => 'Un périmètre écrit, un délai indicatif, un devis détaillé. Pas de surprise.'],
        ],
    ],

    'cta' => [
        'title' => 'Votre prochain logiciel commence par *une bonne question*',
        'text' => '3 minutes pour clarifier votre besoin. Un conseiller pour en parler.',
        'primary' => 'Faire mon diagnostic gratuit',
    ],
];
