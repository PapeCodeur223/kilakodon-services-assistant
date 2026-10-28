<?php

// Liens fixes de la navigation publique. Solutions et secteurs viennent des contenus publiés
// (App\Domain\Content\Services\PublicNavigation). `page` : lien affiché seulement si la page
// est visible ; `cases` : seulement s'il existe une réalisation publiée.
return [

    'main' => [
        ['title' => 'Démo Lab', 'path' => '/demo-lab'],
        ['title' => 'Réalisations', 'path' => '/realisations', 'cases' => true],
        ['title' => 'Méthode', 'path' => '/methode', 'page' => 'methode'],
        ['title' => 'Ressources', 'path' => '/ressources'],
    ],

    'company' => [
        ['title' => 'Méthode', 'path' => '/methode', 'page' => 'methode'],
        ['title' => 'Réalisations', 'path' => '/realisations', 'cases' => true],
        ['title' => 'À propos', 'path' => '/a-propos', 'page' => 'a-propos'],
        ['title' => 'Ressources', 'path' => '/ressources'],
        ['title' => 'Contact', 'path' => '/contact'],
        ['title' => 'Demander un rendez-vous', 'path' => '/rendez-vous'],
    ],

    'legal' => [
        ['title' => 'Mentions légales', 'path' => '/mentions-legales', 'page' => 'mentions-legales'],
        ['title' => 'Confidentialité', 'path' => '/confidentialite', 'page' => 'confidentialite'],
        ['title' => 'Cookies', 'path' => '/cookies', 'page' => 'cookies'],
        // Lot 6 : formulaire dédié aux demandes de droits.
        ['title' => 'Gérer mes données', 'path' => '/confidentialite', 'page' => 'confidentialite'],
    ],

];
