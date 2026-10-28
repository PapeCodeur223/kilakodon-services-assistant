<?php

namespace App\Http\Requests;

use App\Domain\Brief\Models\Brief;
use App\Domain\Brief\Services\BriefAccess;
use App\Domain\Crm\Data\AppointmentRequest;
use App\Domain\Crm\Enums\AppointmentChannel;
use App\Domain\Crm\Enums\SlotPeriod;
use App\Domain\Crm\Models\Appointment;
use App\Domain\Crm\Support\PhoneNormalizer;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Formulaire public de demande de rendez-vous (RDV-01) : motif, format, 1 à 3 créneaux, coordonnées.
 */
class AppointmentFormRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tomorrow = now(config('kh.display_timezone'))->addDay()->toDateString();
        $limit = now(config('kh.display_timezone'))->addMonths(3)->toDateString();

        $date = ['date_format:Y-m-d', 'after_or_equal:'.$tomorrow, 'before_or_equal:'.$limit];

        // Une règle « slots.0.date » explicite remplacerait celle de « slots.*.date » : le premier créneau
        // reprend donc toutes les règles de date, en plus d'être obligatoire.
        return [
            'topic' => ['required', 'string', 'min:3', 'max:255'],
            'channel' => ['required', Rule::enum(AppointmentChannel::class)],
            'slots' => ['required', 'array', 'max:'.Appointment::MAX_SLOTS],
            'slots.*.date' => ['nullable', ...$date, 'required_with:slots.*.period'],
            'slots.*.period' => ['nullable', Rule::enum(SlotPeriod::class), 'required_with:slots.*.date'],
            'slots.0.date' => ['required', ...$date],
            'name' => ['required', 'string', 'min:2', 'max:160'],
            'phone' => ['required', 'string', 'max:30', function (string $attribute, mixed $value, Closure $fail): void {
                if (PhoneNormalizer::toE164(is_string($value) ? $value : null) === null) {
                    $fail('Ce numéro ne semble pas valide. Exemple : 74 66 64 89');
                }
            }],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'company' => ['nullable', 'string', 'max:160'],
            'brief' => ['nullable', 'string', 'max:64'],
            'consent_processing' => ['accepted'],
            'consent_marketing' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slots.0.date.required' => 'Proposez au moins un créneau.',
            'slots.*.date.after_or_equal' => 'Choisissez une date à partir de demain.',
            'slots.*.date.before_or_equal' => 'Choisissez une date dans les trois prochains mois.',
            'slots.*.date.required_with' => 'Indiquez la date de ce créneau.',
            'slots.*.period.required_with' => 'Indiquez le moment de la journée.',
            'consent_processing.accepted' => 'Merci d’accepter le traitement de votre demande pour que nous puissions vous répondre.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'topic' => 'motif',
            'channel' => 'format',
            'name' => 'nom',
            'phone' => 'téléphone / WhatsApp',
            'email' => 'e-mail',
            'company' => 'entreprise',
        ];
    }

    /**
     * Brief d'où vient le visiteur (lien privé encore valable), sinon null.
     */
    public function brief(): ?Brief
    {
        $brief = $this->filled('brief') ? app(BriefAccess::class)->find($this->string('brief')->toString()) : null;

        return $brief?->isAccessible() ? $brief : null;
    }

    public function toAppointmentRequest(): AppointmentRequest
    {
        /** @var array<string, mixed> $stored */
        $stored = (array) $this->session()->get('kh.utm', []);
        $utm = collect($stored)
            ->only(['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'])
            ->map(fn ($value): string => mb_substr((string) $value, 0, 100))
            ->all();

        $slots = collect((array) $this->input('slots', []))
            ->filter(fn (mixed $slot): bool => is_array($slot) && filled($slot['date'] ?? null) && filled($slot['period'] ?? null))
            ->map(fn (array $slot): array => ['date' => (string) $slot['date'], 'period' => (string) $slot['period']])
            ->unique(fn (array $slot): string => $slot['date'].'|'.$slot['period'])
            ->values()
            ->all();

        return new AppointmentRequest(
            fullName: $this->string('name')->toString(),
            phone: $this->string('phone')->toString(),
            email: $this->filled('email') ? $this->string('email')->toString() : null,
            company: $this->filled('company') ? $this->string('company')->toString() : null,
            topic: $this->string('topic')->toString(),
            channel: AppointmentChannel::from($this->string('channel')->toString()),
            slots: $slots,
            marketingOptIn: $this->boolean('consent_marketing'),
            ip: $this->ip(),
            utm: $utm,
            brief: $this->brief(),
        );
    }
}
