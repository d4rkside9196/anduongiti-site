@php
  use App\Models\CoffeeProduct;
  use App\Models\Post;
  use App\Models\Product;
  use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

  $locale = $locale ?? app()->getLocale();
  $altLocale = $locale === 'en' ? 'vi' : 'en';
  $currentPath = parse_url(url()->current(), PHP_URL_PATH) ?: '/';
  if ($locale === 'vi') {
      $currentPath = preg_replace('#^/vi(?=/|$)#', '', $currentPath) ?: '/';
  }
  $routeName = request()->route()?->getName();
  $slug = request()->route('slug');
  $key = request()->route('key');

  $localizedTarget = function (string $targetLocale) use ($routeName, $slug, $key, $currentPath) {
      $translationExists = match ($routeName) {
          'coffee.show' => CoffeeProduct::visible()->forLocale($targetLocale)->where('slug', $slug)->exists(),
          'news.show' => Post::published()->forLocale($targetLocale)->where('slug', $slug)->exists(),
          'products.show' => Product::where(['product_key' => $key, 'locale' => $targetLocale])->exists(),
          default => true,
      };
      $fallback = match ($routeName) {
          'coffee.show' => '/coffee',
          'news.show' => '/news',
          'products.show' => '/products',
          default => $currentPath,
      };

      return LaravelLocalization::getLocalizedURL(
          $targetLocale,
          $translationExists ? $currentPath : $fallback,
      );
  };

  $altUrl = $localizedTarget($altLocale);
  $xDefaultUrl = $localizedTarget('en');
  $title ??= config('site.name');
  $description ??= ($locale === 'vi'
      ? 'An Duong ITI — thương mại nông sản xuất khẩu Việt Nam: cà phê hòa tan OEM/ODM và cao su thiên nhiên.'
      : 'An Duong ITI — Vietnamese agricultural exports: instant-coffee OEM/ODM and natural rubber.');
@endphp
<!doctype html>
<html lang="{{ $locale }}">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>{{ $title }} — {{ config('site.name') }}</title>
  <meta name="description" content="{{ $description }}" />
  <link rel="canonical" href="{{ url()->current() }}" />
  <link rel="alternate" hreflang="{{ $locale }}" href="{{ url()->current() }}" />
  <link rel="alternate" hreflang="{{ $altLocale }}" href="{{ $altUrl }}" />
  <link rel="alternate" hreflang="x-default" href="{{ $xDefaultUrl }}" />
  <link rel="icon" type="image/svg+xml" href="/brand/favicon.svg" />
  <link rel="apple-touch-icon" sizes="180x180" href="/brand/apple-touch-icon.png" />

  <meta property="og:type" content="website" />
  <meta property="og:title" content="{{ $title }} — {{ config('site.name') }}" />
  <meta property="og:description" content="{{ $description }}" />
  <meta property="og:url" content="{{ url()->current() }}" />
  <meta property="og:image" content="{{ asset('brand/og-default.png') }}" />
  <meta property="og:image:width" content="1200" />
  <meta property="og:image:height" content="630" />
  <meta property="og:image:alt" content="{{ config('site.name') }}" />
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="{{ $title }} — {{ config('site.name') }}" />
  <meta name="twitter:description" content="{{ $description }}" />
  <meta name="twitter:image" content="{{ asset('brand/og-default.png') }}" />
  <meta name="twitter:image:alt" content="{{ config('site.name') }}" />

  @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen flex flex-col">
  <x-site.skip-link :locale="$locale" />
  <x-site.header :locale="$locale" :alt-url="$altUrl" />
  <main id="main-content" class="flex-1" tabindex="-1">
    {{ $slot }}
  </main>
  <x-site.footer :locale="$locale" />
  <x-site.floating-contact :locale="$locale" />

  <script>
    (() => {
      const header = document.querySelector('header[data-site-header]');
      if (header) {
        const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
      }

      const sections = document.querySelectorAll('main section');
      sections.forEach(section => section.classList.add('reveal'));
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        sections.forEach(section => section.classList.add('is-visible'));
        return;
      }

      const observer = new IntersectionObserver((entries) => {
        for (const entry of entries) if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
      sections.forEach(section => observer.observe(section));
      setTimeout(() => sections.forEach(section => section.classList.add('is-visible')), 3000);
    })();
  </script>
  <style>
    header[data-site-header].is-scrolled {
      background: color-mix(in oklab, var(--color-cream) 92%, transparent);
      backdrop-filter: blur(10px);
      box-shadow: 0 1px 0 0 color-mix(in oklab, var(--color-earth-200) 60%, transparent);
    }
    header[data-site-header].is-scrolled > div { padding-top: 0.85rem; padding-bottom: 0.85rem; }
  </style>
</body>
</html>
