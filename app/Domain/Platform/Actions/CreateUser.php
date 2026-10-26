<?php

namespace App\Domain\Platform\Actions;

use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class CreateUser
{
    /**
     * @throws ValidationException
     */
    public function handle(string $name, string $email, string $password, UserRole $role): User
    {
        $data = Validator::make(
            ['name' => trim($name), 'email' => mb_strtolower(trim($email)), 'password' => $password],
            [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email:rfc', 'max:190', Rule::unique(User::class, 'email')],
                'password' => ['required', Password::defaults()],
            ],
            attributes: ['name' => 'nom', 'email' => 'e-mail', 'password' => 'mot de passe'],
        )->validate();

        return User::create([...$data, 'role' => $role, 'is_active' => true]);
    }
}
