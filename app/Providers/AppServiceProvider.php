<?php

namespace App\Providers;

use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\CaseStudy;
use App\Domain\Content\Models\Faq;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Models\Sector;
use App\Domain\Content\Models\Service;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Lead;
use App\Domain\Platform\Listeners\RecordSuccessfulLogin;
use App\Domain\Platform\Models\AuditLog;
use App\Domain\Platform\Models\Setting;
use App\Domain\Platform\Models\User;
use App\Domain\Platform\Services\Settings;
use App\Policies\AuditLogPolicy;
use App\Policies\ContentPolicy;
use App\Policies\LeadPolicy;
use App\Policies\UserPolicy;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
            'service' => Service::class,
            'sector' => Sector::class,
            'case_study' => CaseStudy::class,
            'article' => Article::class,
            'faq' => Faq::class,
            'page' => Page::class,
            'contact' => Contact::class,
            'lead' => Lead::class,
        ]);

        // Mots de passe : 12 caractères minimum (TRD §11).
        Password::defaults(fn () => Password::min(12)->letters()->numbers());

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::define('manage-settings', fn (User $user): bool => $user->isAdmin());

        foreach ([Service::class, Sector::class, CaseStudy::class, Article::class, Faq::class, Page::class] as $content) {
            Gate::policy($content, ContentPolicy::class);
        }
        Gate::policy(Lead::class, LeadPolicy::class);

        Event::listen(Login::class, RecordSuccessfulLogin::class);

        // Formulaires publics : 5 envois par heure et par IP (TRD §11).
        RateLimiter::for('contact', fn (Request $request) => Limit::perHour(5)->by((string) $request->ip()));
    }
}
