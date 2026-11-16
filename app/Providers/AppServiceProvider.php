<?php

namespace App\Providers;

use App\Listeners\AuthEventSubscriber;
use App\Models\CoffeeProduct;
use App\Models\Faq;
use App\Models\Partner;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Observers\CoffeeProductObserver;
use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    private const CATALOG_MODELS = [
        Post::class,
        Product::class,
        CoffeeProduct::class,
        ProductCategory::class,
        Partner::class,
        Faq::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->pinRequestIdentity();
        Event::subscribe(AuthEventSubscriber::class);
        CoffeeProduct::observe(CoffeeProductObserver::class);
        $this->registerCatalogAuditObservers();
        $this->registerUserAuditObservers();
    }

    /**
     * Bind URL generation and host identity to the configured app URL.
     *
     * Providers boot after the framework bootstrappers, so config() is
     * authoritative here (unlike a raw env() read inside the bootstrap
     * middleware closure, which fires before .env is loaded and again
     * after config:cache).
     */
    private function pinRequestIdentity(): void
    {
        // Reject request hosts outside this allowlist so a client-supplied
        // Host header can never steer generated URLs, canonicals or signed
        // links — even on a direct-to-origin request where no trusted proxy
        // rewrites it.
        Request::setTrustedHosts([
            'anduongiti\.com',
            'www\.anduongiti\.com',
            'anduongiti\.dev',
            'localhost',
            '127\.0\.0\.1',
        ]);

        if (config('app.env') !== 'production') {
            return;
        }

        // Generated route()/url() links (password-reset and invite emails
        // included) always carry the configured https root, so a plain-HTTP
        // origin request or a spoofed forwarded scheme cannot leak http://
        // links; signed URLs validate against this same pinned root.
        URL::forceScheme('https');
        URL::forceRootUrl(rtrim((string) config('app.url'), '/'));

        // Fail loud instead of silently writing auth emails (signed reset
        // URLs, lead PII) into storage/logs on a production host.
        if (config('mail.default') === 'log') {
            throw new RuntimeException(
                'MAIL_MAILER=log is not allowed when APP_ENV=production; configure real SMTP (see DEPLOY-HOSTINGER.md).'
            );
        }
    }

    private function registerCatalogAuditObservers(): void
    {
        foreach (self::CATALOG_MODELS as $modelClass) {
            $modelClass::created(function (Model $model): void {
                app(ActivityLogger::class)->record('catalog.created', $model, [
                    'attributes' => array_keys($model->getAttributes()),
                ]);
            });
            $modelClass::updated(function (Model $model): void {
                app(ActivityLogger::class)->record('catalog.updated', $model, [
                    'changed' => array_values(array_diff(array_keys($model->getChanges()), ['updated_at'])),
                ]);
            });
            $modelClass::deleted(function (Model $model): void {
                app(ActivityLogger::class)->record('catalog.deleted', $model, [
                    'attributes' => array_keys($model->getAttributes()),
                ]);
            });
        }
    }

    private function registerUserAuditObservers(): void
    {
        User::updated(function (User $user): void {
            $changed = array_values(array_intersect(
                array_keys($user->getChanges()),
                ['name', 'email', 'role', 'is_active', 'email_verified_at'],
            ));

            if ($changed !== []) {
                app(ActivityLogger::class)->record('user.updated', $user, ['changed' => $changed]);
            }
        });
        User::deleted(function (User $user): void {
            app(ActivityLogger::class)->record('user.deleted', $user);
        });
    }
}
