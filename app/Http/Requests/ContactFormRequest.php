<?php

namespace App\Http\Requests;

use App\Domain\Crm\Data\ContactRequest;
use App\Domain\Crm\Enums\ContactSubject;
use App\Domain\Crm\Support\PhoneNormalizer;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactFormRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:160'],
            'phone' => ['required', 'string', 'max:30', function (string $attribute, mixed $value, Closure $fail): void {
                if (PhoneNormalizer::toE164(is_string($value) ? $value : null) === null) {
                    $fail('Ce numéro ne semble pas valide. Exemple : 74 66 64 89');
                }
            }],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'subject' => ['required', Rule::enum(ContactSubject::class)],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
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
            'consent_processing.accepted' => 'Merci d’accepter le traitement de votre demande pour que nous puissions vous répondre.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'phone' => 'téléphone / WhatsApp',
            'email' => 'e-mail',
            'subject' => 'sujet',
            'message' => 'message',
        ];
    }

    public function toContactRequest(): ContactRequest
    {
        $utm = collect($this->session()->get('kh.utm', []))
            ->only(['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'])
            ->map(fn ($value): string => mb_substr((string) $value, 0, 100))
            ->all();

        return new ContactRequest(
            fullName: $this->string('name')->toString(),
            phone: $this->string('phone')->toString(),
            email: $this->filled('email') ? $this->string('email')->toString() : null,
            subject: $this->string('subject')->toString(),
            message: $this->string('message')->toString(),
            marketingOptIn: $this->boolean('consent_marketing'),
            ip: $this->ip(),
            utm: $utm,
        );
    }
}
