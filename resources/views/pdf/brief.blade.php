@php
    use App\View\Support\PdfPalette as C;

    $declared = $version->declared;
    $recommended = $version->recommended;
    $tz = config('kh.display_timezone');
    $stepTitles = \App\Livewire\Diagnostic\Wizard::STEP_TITLES;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Brief {{ $brief->reference }}</title>
<style>
    @page { margin: 22mm 18mm 24mm; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 10pt; line-height: 1.5; color: {{ C::get('ink') }}; }
    h1 { font-size: 20pt; margin: 0; color: {{ C::get('blue') }}; }
    h2 { font-size: 13pt; margin: 22px 0 8px; color: {{ C::get('ink') }}; border-bottom: 2px solid {{ C::get('blue-100') }}; padding-bottom: 4px; }
    h3 { font-size: 10.5pt; margin: 14px 0 6px; color: {{ C::get('blue') }}; }
    .muted { color: {{ C::get('slate') }}; }
    .tag { font-size: 8pt; text-transform: uppercase; letter-spacing: .08em; color: {{ C::get('slate') }}; }
    table { width: 100%; border-collapse: collapse; }
    td { vertical-align: top; padding: 6px 8px; border-bottom: 1px solid {{ C::get('line') }}; }
    td.q { width: 50%; color: {{ C::get('slate') }}; }
    .box { background: {{ C::get('fog') }}; padding: 12px 14px; margin-top: 8px; }
    .reco { background: {{ C::get('blue-50') }}; padding: 8px 12px; margin-bottom: 6px; }
    .reco strong { color: {{ C::get('blue') }}; }
    .reason { font-size: 8.5pt; color: {{ C::get('slate') }}; margin: 2px 0 0 10px; }
    .status { color: {{ C::get('success') }}; font-weight: bold; }
    footer { position: fixed; bottom: -14mm; left: 0; right: 0; font-size: 8pt; color: {{ C::get('slate') }}; border-top: 1px solid {{ C::get('line') }}; padding-top: 4px; }
</style>
</head>
<body>
    <footer>Document de travail non contractuel — {{ $brief->reference }}, version {{ $version->version }} — Kilakodon Services, Bamako (Mali).</footer>

    <table>
        <tr>
            <td style="border: 0; padding: 0;">@if ($logo)<img src="{{ $logo }}" alt="Kilakodon Services" height="44">@endif</td>
            <td style="border: 0; padding: 0; text-align: right;">
                <div class="tag">Brief de projet</div>
                <h1>{{ $brief->reference }}</h1>
                <div class="muted">Créé le {{ $brief->created_at?->timezone($tz)->translatedFormat('j F Y') }}</div>
                @if ($brief->isValidated())
                    <div class="status">Validé le {{ $brief->validated_at?->timezone($tz)->translatedFormat('j F Y à H:i') }}</div>
                @endif
            </td>
        </tr>
    </table>

    <h2>Ce que vous nous avez dit</h2>
    <table>
        <tr><td class="q">Contact</td><td>{{ $declared['contact']['full_name'] }}</td></tr>
        <tr><td class="q">Entreprise</td><td>{{ $declared['contact']['company'] ?? '—' }}</td></tr>
        @foreach (collect($declared['answers'] ?? [])->groupBy('step') as $step => $answers)
            <tr><td colspan="2" style="border: 0; padding-top: 12px;"><h3 style="margin: 0;">{{ $stepTitles[$step] ?? '' }}</h3></td></tr>
            @foreach ($answers as $answer)
                <tr><td class="q">{{ $answer['question'] }}</td><td>{{ $answer['answer'] }}</td></tr>
            @endforeach
        @endforeach
    </table>
    @if (! empty($declared['context_note']))
        <h3>Vos précisions</h3>
        <div class="box">{{ $declared['context_note'] }}</div>
    @endif

    <h2>Ce que nous recommandons</h2>
    <p><span class="tag">Niveau d'équipement numérique</span><br><strong>{{ $recommended['maturity']['label'] }}</strong> (niveau {{ $recommended['maturity']['level'] }} sur 4) — {{ $recommended['maturity']['explanation'] }}</p>

    @if (($recommended['pains'] ?? []) !== [])
        <h3>Vos priorités</h3>
        <ol>
            @foreach ($recommended['pains'] as $pain)
                <li><strong>{{ $pain['label'] }}</strong>@if ($pain['description'] !== '') — {{ $pain['description'] }}@endif</li>
            @endforeach
        </ol>
    @endif

    <h3>Modules recommandés</h3>
    @forelse ($recommended['recommendations'] ?? [] as $reco)
        <div class="reco">
            <strong>{{ $reco['label'] }}</strong>
            @foreach ($reco['reasons'] as $reason)
                @if ($reason['explanation'] !== '')<div class="reason">• {{ $reason['explanation'] }}</div>@endif
            @endforeach
        </div>
    @empty
        <p>{{ $recommended['no_recommendation_message'] ?? '' }}</p>
    @endforelse

    @if (($recommended['clarifications'] ?? []) !== [])
        <h3>À clarifier ensemble</h3>
        <ul>
            @foreach ($recommended['clarifications'] as $clarification)
                <li>{{ $clarification['label'] }}</li>
            @endforeach
        </ul>
    @endif

    <h2>Résumé</h2>
    <p class="tag">{{ $version->summary_source->label() }}</p>
    <div class="box">{!! nl2br(e($version->summary)) !!}</div>
</body>
</html>
