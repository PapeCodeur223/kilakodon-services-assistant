<?php

// Redirections 301 de l'ancien site (TRD §5.3), appliquées par le middleware LegacyRedirects.
return [

    'paths' => [
        '/index.php' => '/',
        '/services.php' => '/solutions',
        '/shop.php' => '/demo-lab',
        '/portofolio.php' => '/realisations',
        '/contact.php' => '/contact',
        // Q-012 : sort de l'ancien espace client [À VALIDER].
        '/client/connexion.php' => '/contact',
        '/client/mon_compte.php' => '/contact',
    ],

    // Q-013 : correspondance des anciens produits [À VALIDER].
    'query' => [
        '/pages/produit_detail.php' => [
            'param' => 'id',
            'map' => [
                '1' => '/secteurs/ecoles-formation',
                '2' => '/secteurs/boulangeries-restauration',
            ],
            'fallback' => '/solutions',
        ],
    ],

];
