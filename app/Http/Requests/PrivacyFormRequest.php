<?php

namespace App\Http\Requests;

use App\Domain\Crm\Enums\PrivacyRequestType;
use App\Domain\Crm\Support\PhoneNormalizer;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrivacyFormRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:160'],
            'channel' => ['required', 'string', 'max:190', function (string $attribute, mixed $value, Closure $fail): void {
                $value = is_string($value) ? trim($value) : '';
                if (filter_var($value, FILTER_VALIDATE_EMAIL) === false && PhoneNormalizer::toE164($value) === null) {
                    $fail('Indiquez un numéro (exemple : 74 66 64 89) ou une adresse e-mail valide.');
                }
            }],
            'type' => ['required', Rule::enum(PrivacyRequestType::class)],
            'details' => ['nullable', 'string', 'max:2000', Rule::requiredIf($this->input('type') === PrivacyRequestType::Rectification->value)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'details.required' => 'Indiquez ce qui doit être corrigé.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'channel' => 'téléphone ou e-mail',
            'type' => 'demande',
            'details' => 'précisions',
        ];
    }
}
