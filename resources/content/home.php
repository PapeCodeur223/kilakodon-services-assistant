<?php

// Textes de l'accueil — sources : docs/CONTENT.md §2 et, pour les ajouts du 2026-10-28, le site actuel
// kilakodon-services.com (textes rédigés par Kilakodon). Un élément contenant « [À VALIDER] » n'est
// affiché qu'en prévisualisation équipe (jamais au public) et apparaît dans `php artisan kh:content-audit`.
return [
    'hero' => [
        'eyebrow' => 'Écosystème logiciel & ERP — Bamako',
        'title_lines' => ['Pilotez votre entreprise', 'avec des outils', '*faits pour elle*.'],
        'title' => 'Pilotez votre entreprise avec des outils *faits pour elle*.',
        'lead' => 'Stock, caisse, clients, factures, équipes : nous concevons les logiciels qui vous donnent enfin une vision claire de votre activité — même quand la connexion coupe. Commencez par un diagnostic gratuit : en 3 minutes, vous saurez ce dont votre entreprise a vraiment besoin.',
        'cta_primary' => 'Faire mon diagnostic gratuit',
        'cta_secondary' => 'Essayer le Démo Lab',
        'reassurance' => ['3 minutes', 'Sans inscription', 'Résultat immédiat'],
    ],

    // Bandeau défilant : savoir-faire (aucun nom de client, aucun chiffre).
    'expertise' => [
        'ERP & CRM sur mesure', 'Gestion de stock', 'Caisse & facturation', 'Applications web métiers',
        'Applications mobiles', 'Mode hors-ligne', 'Tableaux de bord', 'Multi-sites & multi-dépôts',
        'PC, tablette & cloud', 'Audit & modernisation', 'Migration de vos fichiers Excel', 'Design d\'interfaces',
    ],

    'realities' => [
        'title' => 'Conçu pour la façon dont on travaille ici',
        'items' => [
            ['icon' => 'wifi-off', 'title' => 'Fonctionne hors-ligne', 'text' => 'La caisse continue quand Internet coupe ; tout se synchronise au retour du réseau.'],
            ['icon' => 'smartphone', 'title' => 'Mobile Money intégré', 'text' => 'Orange Money, Moov Money, Wave… selon vos besoins et les accès disponibles. [À VALIDER : opérateurs réellement intégrés]'],
            ['icon' => 'monitor-smartphone', 'title' => 'PC, tablette et cloud', 'text' => 'Pilotez votre activité à distance, depuis le bureau comme depuis votre téléphone.'],
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
        // CONTENT §3 (titres et accroches, sans [À VALIDER]). Lien vers la fiche si elle est publiée.
        'items' => [
            ['slug' => 'erp-crm-sur-mesure', 'icon' => 'layout-dashboard', 'title' => 'ERP & CRM sur mesure', 'tagline' => 'Toute votre entreprise dans un seul outil : ventes, clients, équipes, finances.'],
            ['slug' => 'stocks-facturation-caisse', 'icon' => 'package', 'title' => 'Stocks, facturation & caisse', 'tagline' => 'Sachez exactement ce que vous avez, ce que vous vendez et ce que vous encaissez.'],
            ['slug' => 'applications-web-metiers', 'icon' => 'app-window', 'title' => 'Applications web métiers', 'tagline' => 'L\'outil exact dont votre métier a besoin, accessible depuis n\'importe quel navigateur.'],
            ['slug' => 'applications-mobiles', 'icon' => 'smartphone', 'title' => 'Applications mobiles', 'tagline' => 'Vos équipes terrain et vos clients, connectés depuis leur téléphone.'],
            ['slug' => 'audit-modernisation', 'icon' => 'shield-check', 'title' => 'Audit & modernisation', 'tagline' => 'Faites le point sur vos outils actuels avant d\'investir dans de nouveaux.'],
            ['slug' => 'design-identite-numerique', 'icon' => 'pen-tool', 'title' => 'Design & identité numérique', 'tagline' => 'Une image professionnelle, cohérente sur tous vos supports.'],
        ],
    ],

    // Source : kilakodon-services.com, « Nos solutions logicielles » (prix non repris : Q-004).
    'products' => [
        'eyebrow' => 'Logiciels éprouvés',
        'title' => 'Des logiciels *déjà en service*, adaptés à votre structure',
        'lead' => 'En plus du sur-mesure, nous proposons des suites de gestion prêtes à l\'emploi, que nous adaptons à chaque client.',
        'items' => [
            [
                'icon' => 'graduation-cap',
                'name' => 'Gestion de lycée privé',
                'text' => 'Élèves, enseignants, classes, inscriptions, scolarité, paiements, notes, absences, finances et administration.',
                'features' => ['Inscriptions et scolarité', 'Paiements et impayés', 'Notes, bulletins et absences', 'Finances et administration'],
                'sector' => 'ecoles-formation',
                'whatsapp' => 'Bonjour Kilakodon, je souhaite une démonstration du logiciel de gestion de lycée privé.',
            ],
            [
                'icon' => 'croissant',
                'name' => 'Gestion boulangerie & pâtisserie',
                'text' => 'Pour boulangeries, pâtisseries, viennoiseries, ateliers de production et points de vente.',
                'features' => ['Production et ateliers', 'Ventes par point de vente', 'Pertes mesurées', 'Recettes consolidées'],
                'sector' => 'boulangeries-restauration',
                'whatsapp' => 'Bonjour Kilakodon, je souhaite une démonstration du logiciel de gestion boulangerie-pâtisserie.',
            ],
        ],
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
        'title' => 'L\'exigence technique au service de *votre croissance*',
        'accent' => ['title' => 'Un logiciel n\'est utile que s\'il est *utilisé*.', 'text' => 'Nous concevons des outils simples pour vos équipes, nous les formons, et nous restons joignables après la livraison.'],
        'cards' => [
            ['icon' => 'workflow', 'title' => 'Sur mesure, pas « presque adapté »', 'text' => 'Nous partons de vos processus au lieu de vous imposer ceux d\'un logiciel générique.'],
            ['icon' => 'lock-keyhole', 'title' => 'Vos données protégées', 'text' => 'Accès par rôle pour chaque employé, sauvegardes automatisées et traçabilité des opérations.'],
            ['icon' => 'database', 'title' => 'Vos données vous appartiennent', 'text' => 'Export possible à tout moment, documentation fournie. [À VALIDER : conditions de propriété du code et des données]'],
            ['icon' => 'clipboard-list', 'title' => 'Transparence', 'text' => 'Un périmètre écrit, un délai indicatif, un devis détaillé. Pas de surprise.'],
            ['icon' => 'headset', 'title' => 'Support de proximité', 'text' => 'Une équipe d\'ingénieurs basée à Bamako pour la formation, l\'assistance et la maintenance.'],
        ],
    ],

    'cta' => [
        'title' => 'Votre prochain logiciel commence par *une bonne question*',
        'text' => '3 minutes pour clarifier votre besoin. Un conseiller pour en parler.',
        'primary' => 'Faire mon diagnostic gratuit',
    ],
];
