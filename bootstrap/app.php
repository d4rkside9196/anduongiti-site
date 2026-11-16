<?php

use Bepsvpt\SecureHeaders\SecureHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Hostinger's ingress forwards X-Forwarded-For/Port/Proto so Laravel
        // sees the real client IP for rate limiting and the real scheme.
        // Trust is bound to private/loopback peers (how a reverse proxy
        // reaches the origin), NOT to '*' — a wildcard let any direct peer
        // become "the proxy" and choose request()->ip() with a fake
        // X-Forwarded-For, defeating throttle keying and polluting the
        // audit trail. If the Hostinger ingress ever connects from a
        // public IP, add its exact CIDR here.
        // X-Forwarded-Host is deliberately NOT trusted: generated URLs and
        // signed reset links must never inherit a client-chosen host
        // (AppServiceProvider additionally pins the URL root in production).
        $middleware->trustProxies(at: [
            '127.0.0.0/8',
            '::1',
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
        ], headers:
            Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO,
        );

        // Send CSP / HSTS / X-Frame-Options / nosniff on every response.
        $middleware->web(append: [SecureHeadersMiddleware::class]);

        $middleware->alias([
            'localize'                => LaravelLocalizationRoutes::class,
            'localizationRedirect'    => LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect'   => LocaleSessionRedirect::class,
            'localeViewPath'          => LaravelLocalizationViewPath::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
