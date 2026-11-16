{{-- Fil d'une conversation (lecture seule). Tout est échappé : les messages viennent de visiteurs. --}}
@php
    /** @var \App\Domain\Assistant\Models\Conversation $record */
    $record = $getRecord();
    $tz = config('kh.display_timezone');
    $messages = $record->messages()->with('feedback')->get();
@endphp
<div class="space-y-3">
    @forelse ($messages as $message)
        @php $isUser = $message->role === \App\Domain\Assistant\Enums\MessageRole::User; @endphp
        <div @class(['flex', 'justify-end' => $isUser])>
            <div @class([
                'max-w-[80%] rounded-xl px-4 py-3 text-sm',
                'bg-primary-600 text-white' => $isUser,
                'bg-gray-100 text-gray-900 dark:bg-white/5 dark:text-white' => ! $isUser,
            ])>
                <p class="whitespace-pre-line">{{ $message->content }}</p>
                <p @class(['mt-2 text-xs', 'text-white/80' => $isUser, 'text-gray-500' => ! $isUser])>
                    {{ $message->created_at?->timezone($tz)->format('d/m H:i') }}
                    @unless ($isUser)
                        · {{ $message->grounding?->label() ?? '—' }}
                        @if ($message->suggested_action) · {{ $message->suggested_action->label() }} @endif
                        @if ($message->error_code) · repli « {{ $message->error_code }} » @endif
                        @if ($message->model) · {{ $message->model }} ({{ $message->prompt_version }}) @endif
                        @if ($message->feedback) · retour {{ $message->feedback->rating->label() }} @endif
                    @endunless
                </p>
                @if (! $isUser && ! empty($message->citations))
                    <ul class="mt-2 space-y-0.5 text-xs">
                        @foreach ($message->citations as $citation)
                            <li>Source : {{ $citation['title'] ?? '' }} — <span class="text-gray-500">{{ $citation['url'] ?? '' }}</span></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500">Aucun message.</p>
    @endforelse
</div>
