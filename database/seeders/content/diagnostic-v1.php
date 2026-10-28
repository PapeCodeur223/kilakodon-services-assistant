<?php

/**
 * Questionnaire de diagnostic v1 — transcription de docs/DIAGNOSTIC_SPEC.md (§2 questions, §4 règles).
 * Format des conditions et des effets : TRD §6. Source unique du DiagnosticTemplateV1Seeder
 * et des tests du moteur (les tests vérifient donc les règles réellement seedées).
 */

$q = fn (string $question, string $op, mixed $value = null): array => array_filter(
    ['q' => $question, 'op' => $op, 'v' => $value],
    fn (mixed $v): bool => $v !== null,
);

$options = fn (array $pairs): array => array_map(
    fn (string $value, string|array $label): array => is_array($label) ? ['value' => $value, ...$label] : ['value' => $value, 'label' => $label],
    array_keys($pairs),
    $pairs,
);

$recommend = fn (string $module, int $weight): array => ['type' => 'recommend', 'module' => $module, 'weight' => $weight];
$pain = fn (string $key): array => ['type' => 'pain', 'key' => $key];
$clarify = fn (string $key): array => ['type' => 'clarify', 'key' => $key];

$isSchool = ['all' => [$q('sector', 'eq', 'school')]];

return [
    'name' => 'Diagnostic v1',
    'version' => 1,
    'notes' => 'Template initial transcrit de DIAGNOSTIC_SPEC.md. Tranches de budget [À VALIDER].',

    'questions' => [
        // Étape 1 — Votre activité
        [
            'key' => 'sector', 'step' => 1, 'type' => 'single', 'required' => true,
            'label' => 'Quel est votre secteur d\'activité ?',
            'options' => $options([
                'retail' => 'Commerce de détail / boutique',
                'wholesale' => 'Commerce de gros / distribution',
                'school' => 'École / centre de formation',
                'bakery_food' => 'Boulangerie, pâtisserie, restauration',
                'logistics' => 'Logistique / transit',
                'real_estate_construction' => 'Immobilier / BTP',
                'health' => 'Santé / clinique / pharmacie',
                'services' => 'Services aux entreprises',
                'other' => 'Autre',
            ]),
        ],
        [
            'key' => 'team_size', 'step' => 1, 'type' => 'single', 'required' => true,
            'label' => 'Combien de personnes travaillent dans votre structure ?',
            'options' => $options([
                '1_5' => '1 à 5',
                '6_20' => '6 à 20',
                '21_50' => '21 à 50',
                '51_200' => '51 à 200',
                '200_plus' => 'Plus de 200',
            ]),
        ],
        [
            'key' => 'sites', 'step' => 1, 'type' => 'single', 'required' => true,
            'label' => 'Sur combien de sites (boutiques, agences, dépôts) ?',
            'options' => $options([
                '1' => 'Un seul',
                '2_3' => '2 à 3',
                '4_10' => '4 à 10',
                '10_plus' => 'Plus de 10',
            ]),
        ],
        [
            'key' => 'school_size', 'step' => 1, 'type' => 'single', 'required' => true, 'allow_unknown' => true,
            'label' => 'Combien d\'élèves accueillez-vous ?',
            'show_if' => $isSchool,
            'options' => $options([
                'lt_300' => 'Moins de 300',
                '300_1000' => '300 à 1 000',
                'gt_1000' => 'Plus de 1 000',
            ]),
        ],

        // Étape 2 — Votre façon de travailler
        [
            'key' => 'current_tools', 'step' => 2, 'type' => 'multi', 'required' => true,
            'label' => 'Avec quels outils gérez-vous votre activité aujourd\'hui ?',
            'options' => $options([
                'paper' => 'Cahiers / papier',
                'excel' => 'Excel ou Google Sheets',
                'whatsapp' => 'Groupes WhatsApp',
                'pos_software' => 'Logiciel de caisse',
                'accounting_software' => 'Logiciel de comptabilité',
                'erp' => 'ERP ou logiciel de gestion complet',
                'custom_app' => 'Application développée sur mesure',
                'none' => ['label' => 'Rien de formalisé', 'exclusive' => true],
            ]),
        ],
        [
            'key' => 'pains', 'step' => 2, 'type' => 'multi', 'required' => true, 'allow_unknown' => true, 'max_selections' => 4,
            'label' => 'Qu\'est-ce qui vous fait perdre le plus de temps ou d\'argent ?',
            'help' => '4 réponses au maximum.',
            'options' => $options([
                'stock_gaps' => 'Écarts ou ruptures de stock',
                'cash_control' => 'Contrôle de la caisse',
                'slow_invoicing' => 'Factures et devis lents',
                'customer_followup' => 'Suivi des clients et relances',
                'management_reports' => 'Rapports pour la direction',
                'hr_payroll' => 'RH et paie',
                'multisite_coordination' => 'Coordination entre sites',
                'field_teams' => 'Suivi des équipes terrain',
                'school_fees' => ['label' => 'Suivi des frais de scolarité', 'show_if' => $isSchool],
                'school_grades' => ['label' => 'Notes et bulletins', 'show_if' => $isSchool],
                'production_waste' => ['label' => 'Production et pertes', 'show_if' => ['all' => [$q('sector', 'eq', 'bakery_food')]]],
                'online_presence' => 'Image et visibilité en ligne',
                'legacy_software' => 'Logiciel actuel inadapté',
            ]),
        ],
        [
            'key' => 'existing_satisfaction', 'step' => 2, 'type' => 'single', 'required' => true,
            'label' => 'Votre logiciel actuel vous convient-il ?',
            'show_if' => ['all' => [$q('current_tools', 'contains_any', ['pos_software', 'accounting_software', 'erp', 'custom_app'])]],
            'options' => $options([
                'ok' => 'Globalement oui',
                'partial' => 'En partie',
                'no' => 'Non, il faut le remplacer',
            ]),
        ],
        [
            'key' => 'field_work', 'step' => 2, 'type' => 'single', 'required' => true,
            'label' => 'Une partie de vos équipes travaille-t-elle hors du bureau ?',
            'options' => $options([
                'none' => 'Non',
                'some' => 'Quelques personnes',
                'many' => 'Une grande partie (commerciaux, livreurs, agents)',
            ]),
        ],

        // Étape 3 — Votre contexte
        [
            'key' => 'connectivity', 'step' => 3, 'type' => 'single', 'required' => true,
            'label' => 'Comment est la connexion Internet sur vos sites ?',
            'options' => $options([
                'stable' => 'Stable',
                'unstable' => 'Parfois coupée',
                'very_unstable' => 'Souvent coupée ou absente',
            ]),
        ],
        [
            'key' => 'mobile_money', 'step' => 3, 'type' => 'single', 'required' => true,
            'label' => 'Encaissez-vous (ou voulez-vous encaisser) par Mobile Money ?',
            'options' => $options([
                'yes_now' => 'Oui, déjà',
                'wanted' => 'Nous aimerions',
                'no' => 'Non',
            ]),
        ],
        [
            'key' => 'main_goal', 'step' => 3, 'type' => 'single', 'required' => true,
            'label' => 'Quel est votre objectif principal ?',
            'options' => $options([
                'control' => 'Mieux contrôler (stock, caisse, fraude)',
                'time' => 'Gagner du temps',
                'growth' => 'Vendre plus / fidéliser',
                'visibility' => 'Avoir une vision claire des chiffres',
                'modernize' => 'Moderniser un outil existant',
                'image' => 'Améliorer notre image',
            ]),
        ],

        // Étape 4 — Votre projet
        [
            'key' => 'timeline', 'step' => 4, 'type' => 'single', 'required' => true,
            'label' => 'Quand souhaitez-vous démarrer ?',
            'options' => $options([
                'asap' => 'Dès que possible (moins d\'1 mois)',
                '1_3m' => 'Dans 1 à 3 mois',
                '3_6m' => 'Dans 3 à 6 mois',
                'exploring' => 'Je me renseigne',
            ]),
        ],
        [
            'key' => 'budget_band', 'step' => 4, 'type' => 'single', 'required' => false, 'allow_unknown' => true,
            'label' => 'Avez-vous une enveloppe en tête ? (facultatif)',
            'help' => 'Cela nous aide à proposer une solution réaliste. Ce n\'est pas un engagement.',
            // [À VALIDER : tranches] — DIAGNOSTIC_SPEC §2.
            'options' => $options([
                'lt_500k' => 'Moins de 500 000 FCFA',
                '500k_2m' => '500 000 à 2 M FCFA',
                '2m_5m' => '2 à 5 M FCFA',
                '5m_15m' => '5 à 15 M FCFA',
                'gt_15m' => 'Plus de 15 M FCFA',
            ]),
        ],
        [
            'key' => 'decision_role', 'step' => 4, 'type' => 'single', 'required' => true,
            'label' => 'Quel est votre rôle dans la décision ?',
            'options' => $options([
                'decider' => 'Je décide',
                'influencer' => 'Je prépare la décision',
                'researcher' => 'Je me renseigne pour quelqu\'un',
            ]),
        ],
        [
            'key' => 'context_note', 'step' => 4, 'type' => 'text', 'required' => false,
            'label' => 'Quelque chose à ajouter ? (facultatif)',
        ],
    ],

    'rules' => [
        ['key' => 'r_stock_pain', 'priority' => 10, 'conditions' => ['all' => [$q('pains', 'contains', 'stock_gaps')]], 'effects' => [$recommend('MOD_STOCK_POS', 3), $pain('stock_gaps')], 'explanation' => 'Vous signalez des écarts ou ruptures de stock.'],
        ['key' => 'r_cash_pain', 'priority' => 10, 'conditions' => ['all' => [$q('pains', 'contains', 'cash_control')]], 'effects' => [$recommend('MOD_STOCK_POS', 3), $pain('cash_control')], 'explanation' => 'Le contrôle de la caisse est une difficulté pour vous.'],
        ['key' => 'r_retail_base', 'priority' => 20, 'conditions' => ['all' => [$q('sector', 'in', ['retail', 'wholesale'])]], 'effects' => [$recommend('MOD_STOCK_POS', 2), $recommend('MOD_INVOICING', 1)], 'explanation' => 'Dans le commerce, stock et caisse sont le socle de la gestion.'],
        ['key' => 'r_invoicing', 'priority' => 15, 'conditions' => ['all' => [$q('pains', 'contains', 'slow_invoicing')]], 'effects' => [$recommend('MOD_INVOICING', 3), $pain('slow_invoicing')], 'explanation' => 'Vos factures et devis prennent trop de temps.'],
        ['key' => 'r_crm', 'priority' => 15, 'conditions' => ['all' => [$q('pains', 'contains', 'customer_followup')]], 'effects' => [$recommend('MOD_CRM', 3), $pain('customer_followup')], 'explanation' => 'Le suivi de vos clients et des relances vous échappe.'],
        ['key' => 'r_growth_crm', 'priority' => 30, 'conditions' => ['all' => [$q('main_goal', 'eq', 'growth')]], 'effects' => [$recommend('MOD_CRM', 2)], 'explanation' => 'Votre objectif est de vendre plus et de fidéliser.'],
        ['key' => 'r_reports', 'priority' => 15, 'conditions' => ['all' => [$q('pains', 'contains', 'management_reports')]], 'effects' => [$recommend('MOD_REPORTING', 3), $pain('management_reports')], 'explanation' => 'Les rapports pour la direction vous coûtent du temps.'],
        ['key' => 'r_visibility_goal', 'priority' => 30, 'conditions' => ['all' => [$q('main_goal', 'eq', 'visibility')]], 'effects' => [$recommend('MOD_REPORTING', 2)], 'explanation' => 'Vous voulez une vision claire de vos chiffres.'],
        ['key' => 'r_hr', 'priority' => 15, 'conditions' => ['all' => [$q('pains', 'contains', 'hr_payroll')]], 'effects' => [$recommend('MOD_HR_PAYROLL', 3), $pain('hr_payroll')], 'explanation' => 'La gestion RH et la paie sont une difficulté.'],
        ['key' => 'r_hr_size', 'priority' => 40, 'conditions' => ['all' => [$q('team_size', 'in', ['51_200', '200_plus'])]], 'effects' => [$recommend('MOD_HR_PAYROLL', 1), $recommend('MOD_ERP_CORE', 1)], 'explanation' => 'Avec plus de 50 personnes, RH et gestion intégrée deviennent clés.'],
        ['key' => 'r_multisite', 'priority' => 10, 'conditions' => ['all' => [$q('sites', 'in', ['2_3', '4_10', '10_plus'])]], 'effects' => [$recommend('MOD_MULTISITE', 3)], 'explanation' => 'Vous travaillez sur plusieurs sites.'],
        ['key' => 'r_multisite_pain', 'priority' => 15, 'conditions' => ['all' => [$q('pains', 'contains', 'multisite_coordination')]], 'effects' => [$recommend('MOD_MULTISITE', 2), $recommend('MOD_REPORTING', 1), $pain('multisite_coordination')], 'explanation' => 'La coordination entre vos sites est difficile.'],
        ['key' => 'r_field', 'priority' => 15, 'conditions' => ['any' => [$q('field_work', 'eq', 'many'), $q('pains', 'contains', 'field_teams')]], 'effects' => [$recommend('MOD_MOBILE_APP', 3), $pain('field_teams')], 'explanation' => 'Une grande partie de vos équipes travaille sur le terrain.'],
        ['key' => 'r_field_some', 'priority' => 40, 'conditions' => ['all' => [$q('field_work', 'eq', 'some')]], 'effects' => [$recommend('MOD_MOBILE_APP', 1)], 'explanation' => 'Certaines personnes travaillent hors du bureau.'],
        ['key' => 'r_mm', 'priority' => 20, 'conditions' => ['all' => [$q('mobile_money', 'in', ['yes_now', 'wanted'])]], 'effects' => [$recommend('MOD_MOBILE_MONEY', 3)], 'explanation' => 'Vous encaissez, ou voulez encaisser, par Mobile Money.'],
        ['key' => 'r_offline', 'priority' => 10, 'conditions' => ['all' => [$q('connectivity', 'in', ['unstable', 'very_unstable'])]], 'effects' => [$recommend('MOD_OFFLINE', 3), $clarify('offline_scope')], 'explanation' => 'Votre connexion n\'est pas toujours fiable.'],
        ['key' => 'r_school', 'priority' => 5, 'conditions' => ['all' => [$q('sector', 'eq', 'school')]], 'effects' => [$recommend('MOD_SCHOOL', 4)], 'explanation' => 'Vous dirigez un établissement d\'enseignement.'],
        ['key' => 'r_school_fees', 'priority' => 10, 'conditions' => ['all' => [$q('pains', 'contains', 'school_fees')]], 'effects' => [$recommend('MOD_SCHOOL', 1), $pain('school_fees')], 'explanation' => 'Le suivi des frais de scolarité est une priorité.'],
        ['key' => 'r_school_grades', 'priority' => 10, 'conditions' => ['all' => [$q('pains', 'contains', 'school_grades')]], 'effects' => [$recommend('MOD_SCHOOL', 1), $pain('school_grades')], 'explanation' => 'Notes et bulletins vous prennent beaucoup de temps.'],
        ['key' => 'r_bakery', 'priority' => 5, 'conditions' => ['all' => [$q('sector', 'eq', 'bakery_food')]], 'effects' => [$recommend('MOD_BAKERY', 4), $recommend('MOD_STOCK_POS', 1)], 'explanation' => 'Vous êtes dans la boulangerie ou la restauration.'],
        ['key' => 'r_bakery_waste', 'priority' => 10, 'conditions' => ['all' => [$q('pains', 'contains', 'production_waste')]], 'effects' => [$recommend('MOD_BAKERY', 1), $pain('production_waste')], 'explanation' => 'Production et pertes sont à mieux maîtriser.'],
        ['key' => 'r_specific_sector', 'priority' => 25, 'conditions' => ['all' => [$q('sector', 'in', ['logistics', 'real_estate_construction', 'health', 'services'])]], 'effects' => [$recommend('MOD_WEB_APP', 3), $clarify('process_mapping')], 'explanation' => 'Votre secteur a des processus spécifiques qui méritent un outil adapté.'],
        ['key' => 'r_erp_paper_big', 'priority' => 30, 'conditions' => ['all' => [$q('team_size', 'in', ['21_50', '51_200', '200_plus']), $q('current_tools', 'contains_any', ['paper', 'excel', 'none']), $q('current_tools', 'contains_none', ['erp'])]], 'effects' => [$recommend('MOD_ERP_CORE', 3)], 'explanation' => 'Votre taille dépasse ce que le papier et Excel permettent de piloter.'],
        ['key' => 'r_legacy', 'priority' => 10, 'conditions' => ['any' => [$q('pains', 'contains', 'legacy_software'), $q('existing_satisfaction', 'eq', 'no')]], 'effects' => [$recommend('MOD_AUDIT', 3), $pain('legacy_software'), $clarify('data_migration')], 'explanation' => 'Votre logiciel actuel ne vous convient plus.'],
        ['key' => 'r_partial', 'priority' => 30, 'conditions' => ['all' => [$q('existing_satisfaction', 'eq', 'partial')]], 'effects' => [$recommend('MOD_AUDIT', 2), $clarify('integration_existing')], 'explanation' => 'Votre outil actuel ne répond qu\'en partie à vos besoins.'],
        ['key' => 'r_modernize', 'priority' => 30, 'conditions' => ['all' => [$q('main_goal', 'eq', 'modernize')]], 'effects' => [$recommend('MOD_AUDIT', 2)], 'explanation' => 'Votre objectif est de moderniser l\'existant.'],
        ['key' => 'r_excel_migration', 'priority' => 35, 'conditions' => ['all' => [$q('current_tools', 'contains', 'excel')]], 'effects' => [$clarify('data_migration')], 'explanation' => 'Vos données Excel pourront être reprises.'],
        ['key' => 'r_image', 'priority' => 15, 'conditions' => ['any' => [$q('pains', 'contains', 'online_presence'), $q('main_goal', 'eq', 'image')]], 'effects' => [$recommend('MOD_DESIGN', 3), $pain('online_presence')], 'explanation' => 'Vous souhaitez améliorer votre image et votre visibilité.'],
        ['key' => 'r_budget_small', 'priority' => 50, 'conditions' => ['all' => [$q('budget_band', 'eq', 'lt_500k')]], 'effects' => [$clarify('phasing')], 'explanation' => 'Une enveloppe réduite : nous proposerons un démarrage par étapes.'],
        ['key' => 'r_unknown_budget', 'priority' => 50, 'conditions' => ['any' => [$q('budget_band', 'is_unknown'), $q('budget_band', 'not_answered')]], 'effects' => [$clarify('budget')], 'explanation' => ''],
        ['key' => 'r_exploring', 'priority' => 50, 'conditions' => ['all' => [$q('timeline', 'eq', 'exploring')]], 'effects' => [$clarify('priorities')], 'explanation' => ''],
        ['key' => 'r_not_decider', 'priority' => 50, 'conditions' => ['all' => [$q('decision_role', 'in', ['influencer', 'researcher'])]], 'effects' => [$clarify('decision_makers')], 'explanation' => ''],
    ],
];
