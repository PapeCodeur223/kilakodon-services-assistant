<?php

// Navigation publique — libellés issus de docs/CONTENT.md §3 et §4.
// Lot 1 : les solutions et secteurs seront lus depuis la base (contenus publiés).
return [

    'solutions' => [
        ['title' => 'ERP & CRM sur mesure', 'slug' => 'erp-crm-sur-mesure', 'icon' => 'layout-dashboard', 'tagline' => 'Toute votre entreprise dans un seul outil : ventes, clients, équipes, finances.'],
        ['title' => 'Stocks, facturation & caisse', 'slug' => 'stocks-facturation-caisse', 'icon' => 'package', 'tagline' => 'Sachez exactement ce que vous avez, ce que vous vendez et ce que vous encaissez.'],
        ['title' => 'Applications web métiers', 'slug' => 'applications-web-metiers', 'icon' => 'app-window', 'tagline' => 'L\'outil exact dont votre métier a besoin, accessible depuis n\'importe quel navigateur.'],
        ['title' => 'Applications mobiles', 'slug' => 'applications-mobiles', 'icon' => 'smartphone', 'tagline' => 'Vos équipes terrain et vos clients, connectés depuis leur téléphone.'],
        ['title' => 'Audit & modernisation', 'slug' => 'audit-modernisation', 'icon' => 'shield-check', 'tagline' => 'Faites le point sur vos outils actuels avant d\'investir dans de nouveaux.'],
        ['title' => 'Design & identité numérique', 'slug' => 'design-identite-numerique', 'icon' => 'pen-tool', 'tagline' => 'Une image professionnelle, cohérente sur tous vos supports.'],
    ],

    'sectors' => [
        ['title' => 'Commerce & distribution', 'slug' => 'commerce-distribution', 'icon' => 'store'],
        ['title' => 'Écoles & centres de formation', 'slug' => 'ecoles-formation', 'icon' => 'graduation-cap'],
        ['title' => 'Boulangeries & restauration', 'slug' => 'boulangeries-restauration', 'icon' => 'croissant'],
        ['title' => 'Logistique & transit', 'slug' => 'logistique-transit', 'icon' => 'truck'],
        ['title' => 'Immobilier & BTP', 'slug' => 'immobilier-btp', 'icon' => 'building-2'],
        ['title' => 'Santé & cliniques', 'slug' => 'sante-cliniques', 'icon' => 'stethoscope'],
    ],

    'main' => [
        ['title' => 'Démo Lab', 'path' => '/demo-lab'],
        ['title' => 'Réalisations', 'path' => '/realisations'],
        ['title' => 'Méthode', 'path' => '/methode'],
        ['title' => 'Ressources', 'path' => '/ressources'],
    ],

    'company' => [
        ['title' => 'Méthode', 'path' => '/methode'],
        ['title' => 'Réalisations', 'path' => '/realisations'],
        ['title' => 'À propos', 'path' => '/a-propos'],
        ['title' => 'Ressources', 'path' => '/ressources'],
        ['title' => 'Contact', 'path' => '/contact'],
    ],

    'legal' => [
        ['title' => 'Mentions légales', 'path' => '/mentions-legales'],
        ['title' => 'Confidentialité', 'path' => '/confidentialite'],
        ['title' => 'Cookies', 'path' => '/cookies'],
        // Lot 6 : formulaire dédié aux demandes de droits.
        ['title' => 'Gérer mes données', 'path' => '/confidentialite'],
    ],

];
