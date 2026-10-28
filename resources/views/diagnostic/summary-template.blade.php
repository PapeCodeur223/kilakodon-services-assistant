{{-- Résumé gabarit (repli de l'IA) : uniquement des éléments calculés par les règles. --}}
@php
    $pains = array_map(fn ($p) => mb_strtolower($p['label']), $outcome->pains);
    $modules = array_column($outcome->recommendations, 'label');
@endphp
Votre organisation se situe au niveau « {{ $outcome->maturityLabel() }} » : {{ $outcome->maturityExplanation() }}
@if ($pains !== [])
Vos priorités : {{ implode(' ; ', $pains) }}.
@endif
@if ($modules !== [])
Nous recommandons d'étudier en premier lieu : {{ implode(', ', $modules) }}.
@else
{{ \App\Domain\Diagnostic\Engine\Catalog::NO_RECOMMENDATION_MESSAGE }}
@endif
@if ($outcome->clarifications !== [])
Nous préciserons ensemble {{ count($outcome->clarifications) === 1 ? 'un point' : count($outcome->clarifications).' points' }} lors d'un premier échange.
@endif
