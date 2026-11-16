<?php

namespace App\Providers;

use App\Domain\Assistant\Conversation\FallbackTexts;
use App\Domain\Assistant\Jobs\RebuildKnowledgeForSource;
use App\Domain\Assistant\Llm\AnthropicClient;
use App\Domain\Assistant\Llm\FakeLlmClient;
use App\Domain\Assistant\Llm\LlmClient;
use App\Domain\Assistant\Llm\UnavailableLlmClient;
use App\Domain\Assistant\Models\AiActionProposal;
use App\Domain\Assistant\Models\AiFeedback;
use App\Domain\Assistant\Models\Conversation;
use App\Domain\Assistant\Models\KnowledgeDocument;
use App\Domain\Brief\Models\Brief;
use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\CaseStudy;
use App\Domain\Content\Models\ContentModel;
use App\Domain\Content\Models\Faq;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Models\Sector;
use App\Domain\Content\Models\Service;
use App\Domain\Crm\Models\Appointment;
use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Lead;
use App\Domain\Diagnostic\Models\DiagnosticSession;
use App\Domain\Diagnostic\Models\DiagnosticTemplate;
use App\Domain\Platform\Listeners\RecordSuccessfulLogin;
use App\Domain\Platform\Models\AuditLog;
use App\Domain\Platform\Models\OutboundMessage;
use App\Domain\Platform\Models\Setting;
use App\Domain\Platform\Models\User;
use App\Domain\Platform\Services\Settings;
use App\Policies\AiFeedbackPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\BriefPolicy;
use App\Policies\ContentPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\CrmRecordPolicy;
use App\Policies\DiagnosticSessionPolicy;
use App\Policies\DiagnosticTemplatePolicy;
use App\Policies\KnowledgeDocumentPolicy;
use App\Policies\LeadPolicy;
use App\Policies\OutboundMessagePolicy;
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

        // AI_DRIVER=fake : réponses déterministes ; anthropic : API réelle (clé dans .env uniquement) ; sinon repli.
        $this->app->bind(LlmClient::class, fn (): LlmClient => match (config('assistant.driver')) {
            'fake' => new FakeLlmClient,
            'anthropic' => new AnthropicClient,
            default => new UnavailableLlmClient,
        });
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
            'diagnostic_template' => DiagnosticTemplate::class,
            'diagnostic_session' => DiagnosticSession::class,
            'brief' => Brief::class,
            'appointment' => Appointment::class,
            'company' => Company::class,
            'knowledge_document' => KnowledgeDocument::class,
            'conversation' => Conversation::class,
            'ai_action_proposal' => AiActionProposal::class,
        ]);

        // Mots de passe : 12 caractères minimum (TRD §11).
        Password::defaults(fn () => Password::min(12)->letters()->numbers());

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::define('manage-settings', fn (User $user): bool => $user->isAdmin());

        foreach ([Service::class, Sector::class, CaseStudy::class, Article::class, Faq::class, Page::class] as $content) {
            Gate::policy($content, ContentPolicy::class);
            // Base de connaissances de l'assistant : reconstruite après chaque modification d'un contenu (TRD §7.1).
            $content::saved(fn (ContentModel $model) => RebuildKnowledgeForSource::dispatch($model::class, (int) $model->getKey())->afterCommit());
        }
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(KnowledgeDocument::class, KnowledgeDocumentPolicy::class);
        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(AiFeedback::class, AiFeedbackPolicy::class);
        foreach ([Contact::class, Company::class, Appointment::class] as $record) {
            Gate::policy($record, CrmRecordPolicy::class);
        }
        Gate::policy(OutboundMessage::class, OutboundMessagePolicy::class);
        Gate::policy(DiagnosticTemplate::class, DiagnosticTemplatePolicy::class);
        Gate::policy(DiagnosticSession::class, DiagnosticSessionPolicy::class);
        Gate::policy(Brief::class, BriefPolicy::class);

        Event::listen(Login::class, RecordSuccessfulLogin::class);

        // Formulaires publics : 5 envois par heure et par IP (TRD §11).
        RateLimiter::for('contact', fn (Request $request) => Limit::perHour(5)->by((string) $request->ip()));
        // Pages publiques à token (brief, reprise) : freine l'énumération.
        RateLimiter::for('public-token', fn (Request $request) => Limit::perMinute(30)->by((string) $request->ip()));
        // Assistant (TRD §7.4) : 6 requêtes par minute et par IP ; repli JSON lisible par le widget.
        RateLimiter::for('assistant', fn (Request $request) => Limit::perMinute((int) config('assistant.guards.requests_per_minute_per_ip', 6))
            ->by((string) $request->ip())
            ->response(fn () => response()->json([
                'status' => 'notice',
                'reason' => 'rate_limited',
                'message' => FallbackTexts::RATE_LIMITED,
            ], 429)));
        RateLimiter::for('assistant-actions', fn (Request $request) => Limit::perMinute(20)->by((string) $request->ip()));
    }
}
