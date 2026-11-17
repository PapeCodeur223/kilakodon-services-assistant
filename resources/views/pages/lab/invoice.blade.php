@php
    use App\View\Support\Money;

    /** @var \App\Domain\DemoLab\Models\DemoSale $sale */
    $date = $sale->sold_at->setTimezone('Africa/Bamako')->locale('fr');
@endphp
{{-- Facture FICTIVE du Démo Lab (TRD §8) : HTML imprimable, filigrane « DÉMO — DOCUMENT FICTIF ». --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Facture {{ $sale->number }} — Démo, document fictif</title>
    @vite(['resources/css/app.css'])
    <style nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        @media print { .no-print { display: none !important; } body { background: var(--color-white); } .sheet { box-shadow: none; margin: 0; } }
        .watermark { transform: translate(-50%, -50%) rotate(-30deg); }
    </style>
</head>
<body class="bg-fog text-ink">
    <div class="no-print mx-auto flex max-w-3xl flex-wrap items-center justify-between gap-3 px-4 pt-6">
        <a href="{{ route('lab.retail', ['onglet' => 'ventes']) }}" class="inline-flex min-h-11 items-center font-semibold text-kh-blue-700 hover:underline">← Retour à la démo</a>
        <button type="button" id="kh-print" class="inline-flex min-h-11 items-center gap-2 rounded-full bg-kh-blue-700 px-5 font-semibold text-white hover:bg-kh-blue-600">
            <x-kh.icon name="printer" :size="18" /> Imprimer la facture
        </button>
    </div>

    <main class="sheet relative mx-auto my-6 max-w-3xl overflow-hidden bg-white p-8 shadow-pop sm:p-12">
        <p class="watermark pointer-events-none absolute top-1/2 left-1/2 whitespace-nowrap font-display text-[clamp(2rem,7vw,4rem)] font-extrabold text-kh-red-500/10 select-none" aria-hidden="true">DÉMO — DOCUMENT FICTIF</p>

        <header class="relative flex flex-wrap items-start justify-between gap-6 border-b border-line pb-6">
            <div>
                <p class="font-display text-h3 font-extrabold">Boutique Démo</p>
                <p class="text-small text-slate">Commerce fictif · Bamako</p>
                <p class="mt-2 inline-block rounded-full bg-kh-red-50 px-3 py-0.5 text-tag font-semibold text-kh-red-600">Document fictif — sans valeur</p>
            </div>
            <div class="text-end">
                <h1 class="font-display text-h2 font-extrabold">Facture</h1>
                <p class="font-semibold">{{ $sale->number }}</p>
                <p class="text-small text-slate">{{ $date->translatedFormat('j F Y, H:i') }}</p>
            </div>
        </header>

        <dl class="relative mt-6 grid gap-4 text-small sm:grid-cols-2">
            <div><dt class="text-tag uppercase text-slate">Client</dt><dd class="font-semibold">{{ $sale->customer?->name ?? 'Client de passage' }}</dd>@if ($sale->customer)<dd class="text-slate">{{ $sale->customer->phone_display }}</dd>@endif</div>
            <div class="sm:text-end"><dt class="text-tag uppercase text-slate">Paiement</dt><dd class="font-semibold">{{ $sale->payment_method->label() }}</dd></div>
        </dl>

        <table class="relative mt-8 w-full text-small">
            <thead class="border-b border-line text-tag uppercase text-slate">
                <tr><th scope="col" class="py-2 text-start">Article</th><th scope="col" class="py-2 text-end">Qté</th><th scope="col" class="py-2 text-end">Prix unitaire</th><th scope="col" class="py-2 text-end">Total</th></tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($sale->items as $item)
                    <tr>
                        <td class="py-3">{{ $item->product->name }} <span class="block text-small text-slate">{{ $item->product->unit }}</span></td>
                        <td class="py-3 text-end tabular-nums">{{ $item->qty }}</td>
                        <td class="py-3 text-end tabular-nums">{{ Money::xof($item->unit_price_xof) }}</td>
                        <td class="py-3 text-end font-semibold tabular-nums">{{ Money::xof($item->line_total_xof) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-ink">
                    <th scope="row" colspan="3" class="pt-4 text-end font-semibold">Total à payer</th>
                    <td class="pt-4 text-end font-display text-h3 font-extrabold tabular-nums">{{ Money::xof($sale->total_xof) }}</td>
                </tr>
            </tfoot>
        </table>

        <footer class="relative mt-12 border-t border-line pt-4 text-small text-slate">
            Facture générée par le Démo Lab de Kilakodon Services avec des données fictives. Elle n'a aucune valeur commerciale ni comptable.
        </footer>
    </main>

    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        document.getElementById('kh-print').addEventListener('click', () => window.print());
    </script>
</body>
</html>
