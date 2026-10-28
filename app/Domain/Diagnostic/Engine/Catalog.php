<?php

namespace App\Domain\Diagnostic\Engine;

/**
 * Référentiels fixes du diagnostic (DIAGNOSTIC_SPEC §1, §3, §5) : modules, priorités,
 * clarifications, barème de maturité. PHP pur, sans Laravel.
 */
final class Catalog
{
    public const UNKNOWN = '__unknown';

    /** Seuils de sélection des modules (DIAGNOSTIC_SPEC §1). */
    public const RECOMMEND_THRESHOLD = 3;

    public const FALLBACK_THRESHOLD = 2;

    public const MAX_RECOMMENDATIONS = 5;

    public const MAX_PAINS = 3;

    public const MATURITY_CAP = 6.0;

    public const TEXT_MAX_LENGTH = 500;

    /** @var array<string, array{label: string, solution: string}> */
    public const MODULES = [
        'MOD_STOCK_POS' => ['label' => 'Gestion de stock & caisse', 'solution' => 'stocks-facturation-caisse'],
        'MOD_INVOICING' => ['label' => 'Facturation & devis', 'solution' => 'stocks-facturation-caisse'],
        'MOD_CRM' => ['label' => 'Suivi clients & relances (CRM)', 'solution' => 'erp-crm-sur-mesure'],
        'MOD_ERP_CORE' => ['label' => 'ERP intégré (ventes, achats, finances)', 'solution' => 'erp-crm-sur-mesure'],
        'MOD_HR_PAYROLL' => ['label' => 'RH & paie', 'solution' => 'erp-crm-sur-mesure'],
        'MOD_MULTISITE' => ['label' => 'Multi-sites & transferts', 'solution' => 'stocks-facturation-caisse'],
        'MOD_REPORTING' => ['label' => 'Tableaux de bord direction', 'solution' => 'applications-web-metiers'],
        'MOD_MOBILE_APP' => ['label' => 'Application mobile terrain', 'solution' => 'applications-mobiles'],
        'MOD_MOBILE_MONEY' => ['label' => 'Paiements Mobile Money', 'solution' => 'applications-mobiles'],
        'MOD_OFFLINE' => ['label' => 'Fonctionnement hors-ligne', 'solution' => 'stocks-facturation-caisse'],
        'MOD_SCHOOL' => ['label' => 'Gestion scolaire', 'solution' => 'applications-web-metiers'],
        'MOD_BAKERY' => ['label' => 'Production boulangerie / restauration', 'solution' => 'applications-web-metiers'],
        'MOD_WEB_APP' => ['label' => 'Application web métier spécifique', 'solution' => 'applications-web-metiers'],
        'MOD_AUDIT' => ['label' => 'Audit & migration de l\'existant', 'solution' => 'audit-modernisation'],
        'MOD_DESIGN' => ['label' => 'Identité & présence en ligne', 'solution' => 'design-identite-numerique'],
    ];

    /** @var array<string, array{title: string, description: string}> */
    public const PAINS = [
        'stock_gaps' => ['title' => 'Fiabiliser le stock', 'description' => 'Savoir à tout moment ce qui est disponible et éviter les ruptures.'],
        'cash_control' => ['title' => 'Sécuriser la caisse', 'description' => 'Tracer chaque encaissement par vendeur et moyen de paiement.'],
        'slow_invoicing' => ['title' => 'Accélérer la facturation', 'description' => 'Des devis et factures propres en quelques clics.'],
        'customer_followup' => ['title' => 'Organiser le suivi client', 'description' => 'Historique, relances, fidélisation.'],
        'management_reports' => ['title' => 'Automatiser le reporting', 'description' => 'Des chiffres à jour sans ressaisie.'],
        'hr_payroll' => ['title' => 'Simplifier RH et paie', 'description' => ''],
        'multisite_coordination' => ['title' => 'Coordonner vos sites', 'description' => 'Une vision consolidée et des transferts tracés.'],
        'field_teams' => ['title' => 'Connecter le terrain', 'description' => 'Remontée d\'information en temps réel.'],
        'school_fees' => ['title' => 'Maîtriser les frais de scolarité et les impayés', 'description' => ''],
        'school_grades' => ['title' => 'Produire notes et bulletins sans y passer des nuits', 'description' => ''],
        'production_waste' => ['title' => 'Ajuster la production et réduire les pertes', 'description' => ''],
        'online_presence' => ['title' => 'Renforcer votre image et votre présence en ligne', 'description' => ''],
        'legacy_software' => ['title' => 'Remplacer ou moderniser un outil devenu inadapté', 'description' => ''],
    ];

    /** @var array<string, string> */
    public const CLARIFICATIONS = [
        'offline_scope' => 'Quelles opérations doivent absolument fonctionner sans connexion ?',
        'process_mapping' => 'Cartographier vos processus métier lors d\'un premier atelier.',
        'data_migration' => 'Quelles données reprendre (fichiers, historique) et dans quel état sont-elles ?',
        'integration_existing' => 'Que garder de votre outil actuel et comment le connecter ?',
        'phasing' => 'Définir un premier module prioritaire adapté à votre enveloppe.',
        'budget' => 'Préciser une enveloppe indicative pour proposer une solution réaliste.',
        'priorities' => 'Identifier le besoin le plus urgent pour démarrer.',
        'decision_makers' => 'Qui doit être associé à la décision ?',
        'scope_unclear' => 'Préciser votre besoin lors d\'un court échange.',
    ];

    /** Barème de maturité par outil (DIAGNOSTIC_SPEC §3). @var array<string, float> */
    public const TOOL_POINTS = [
        'none' => 0.0,
        'paper' => 0.0,
        'whatsapp' => 0.5,
        'excel' => 1.0,
        'pos_software' => 2.0,
        'accounting_software' => 2.0,
        'custom_app' => 2.5,
        'erp' => 3.0,
    ];

    /** @var array<int, array{label: string, explanation: string}> */
    public const MATURITY_LEVELS = [
        1 => ['label' => 'Démarrage', 'explanation' => 'Votre activité repose surtout sur le papier, la mémoire et les messages. Un premier outil bien choisi aura un impact immédiat.'],
        2 => ['label' => 'Outillé', 'explanation' => 'Vous utilisez déjà des outils (souvent Excel), mais ils ne communiquent pas entre eux. L\'enjeu : centraliser.'],
        3 => ['label' => 'Structuré', 'explanation' => 'Vous avez des logiciels en place. L\'enjeu : les relier, automatiser et mieux piloter.'],
        4 => ['label' => 'Intégré', 'explanation' => 'Votre équipement est avancé. L\'enjeu : optimiser, sécuriser et étendre (mobile, reporting, intégrations).'],
    ];

    public const MATURITY_NOTE = 'Indicateur simple basé sur vos outils actuels, pour orienter notre échange.';

    public const NO_RECOMMENDATION_MESSAGE = 'Votre besoin mérite un court échange pour être précisé.';

    /**
     * Complément des priorités par l'objectif principal (DIAGNOSTIC_SPEC §5) :
     * la première priorité de la liste qui n'est pas déjà présente.
     *
     * @var array<string, list<string>>
     */
    public const GOAL_PAINS = [
        'control' => ['stock_gaps', 'cash_control'],
        'time' => ['management_reports'],
        'growth' => ['customer_followup'],
        'visibility' => ['management_reports'],
        'modernize' => ['legacy_software'],
        'image' => ['online_presence'],
    ];

    public static function moduleLabel(string $code): string
    {
        return self::MODULES[$code]['label'] ?? $code;
    }

    public static function moduleOrder(string $code): int
    {
        $index = array_search($code, array_keys(self::MODULES), true);

        return $index === false ? PHP_INT_MAX : $index;
    }
}
