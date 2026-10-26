<?php

namespace App\Providers;

use App\Domain\Platform\Listeners\RecordSuccessfulLogin;
use App\Domain\Platform\Models\AuditLog;
use App\Domain\Platform\Models\Setting;
use App\Domain\Platform\Models\User;
use App\Domain\Platform\Services\Settings;
use App\Policies\AuditLogPolicy;
use App\Policies\UserPolicy;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Settings::class);
    }

    public function boot(): void
    {
        // Lazy loading, attributs inconnus et affectations silencieuses : erreurs hors production.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Types polymorphes stables (indépendants des noms de classes).
        Relation::enforceMorphMap([
            'user' => User::class,
            'setting' => Setting::class,
            'audit_log' => AuditLog::class,
        ]);

        // Mots de passe : 12 caractères minimum (TRD §11).
        Password::defaults(fn () => Password::min(12)->letters()->numbers());

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::define('manage-settings', fn (User $user): bool => $user->isAdmin());

        Event::listen(Login::class, RecordSuccessfulLogin::class);
    }
}
