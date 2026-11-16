@props(['locale'])
@php use Mcamara\LaravelLocalization\Facades\LaravelLocalization; @endphp
<section class="relative overflow-hidden bg-cream pt-12 pb-24 sm:pb-32">
  <div class="pointer-events-none absolute inset-0 -z-10">
    <div class="absolute -top-40 -right-32 h-[520px] w-[520px] rounded-full bg-forest-200/30 blur-3xl"></div>
    <div class="absolute top-40 -left-24 h-[420px] w-[420px] rounded-full bg-earth-200/40 blur-3xl"></div>
  </div>

  <div class="container-x grid items-center gap-14 lg:grid-cols-12 lg:gap-10">
    <div class="lg:col-span-7 fade-up">
      <p class="eyebrow">{{ __('site.home.hero_eyebrow') }}</p>
      <h1 class="mt-5 text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-medium leading-[1.02] tracking-tight text-ink">
        {{ __('site.home.hero_title') }}
      </h1>
      <p class="mt-7 max-w-2xl text-lg text-ink/70 leading-relaxed">{{ __('site.home.hero_lede') }}</p>
      <div class="mt-9 flex flex-wrap items-center gap-3">
        <a href="{{ LaravelLocalization::getLocalizedURL($locale, '/contact') }}" class="btn-primary">
          {{ __('site.cta.partner') }}
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
        </a>
        <a href="{{ LaravelLocalization::getLocalizedURL($locale, '/products') }}" class="btn-ghost">
          {{ __('site.cta.learn_more') }}
        </a>
      </div>
      <dl class="mt-14 grid grid-cols-3 gap-6 max-w-xl">
        <div>
          <dt class="text-xs uppercase tracking-widest text-earth-700">{{ $locale === 'vi' ? 'Năm thành lập' : 'Founded' }}</dt>
          <dd class="mt-1 text-2xl font-medium text-ink">{{ config('site.founded') }}</dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-widest text-earth-700">{{ $locale === 'vi' ? 'Quốc gia XK' : 'Export markets' }}</dt>
          <dd class="mt-1 text-2xl font-medium text-ink">12+</dd>
        </div>
        <div>
          <dt class="text-xs uppercase tracking-widest text-earth-700">{{ $locale === 'vi' ? 'Vùng nguyên liệu' : 'Sourcing regions' }}</dt>
          <dd class="mt-1 text-2xl font-medium text-ink">8</dd>
        </div>
      </dl>
    </div>

    <div class="lg:col-span-5">
      <div class="relative aspect-[4/5] overflow-hidden rounded-3xl bg-earth-100 shadow-2xl shadow-forest-900/10">
        <svg viewBox="0 0 400 500" xmlns="http://www.w3.org/2000/svg" class="h-full w-full" preserveAspectRatio="xMidYMid slice">
          <defs>
            <linearGradient id="sky" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#faf6e8"/><stop offset="100%" stop-color="#e8d9b8"/></linearGradient>
            <linearGradient id="hill1" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#74c69d"/><stop offset="100%" stop-color="#1f7c4b"/></linearGradient>
            <linearGradient id="hill2" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#40916c"/><stop offset="100%" stop-color="#0f3623"/></linearGradient>
          </defs>
          <rect width="400" height="500" fill="url(#sky)"/>
          <circle cx="320" cy="110" r="48" fill="#fef3d3" opacity="0.9"/>
          <path d="M0 320 Q 100 250 200 290 T 400 280 L 400 500 L 0 500 Z" fill="url(#hill1)"/>
          <path d="M0 380 Q 120 320 240 360 T 400 350 L 400 500 L 0 500 Z" fill="url(#hill2)"/>
          <g transform="translate(60 380)">
            <ellipse cx="0" cy="0" rx="22" ry="28" fill="#5d4030"/>
            <path d="M0 -28 Q 4 0 0 28" stroke="#251812" stroke-width="2" fill="none"/>
            <ellipse cx="40" cy="20" rx="22" ry="28" fill="#7b5739" transform="rotate(20 40 20)"/>
            <path d="M40 -8 Q 44 20 40 48" stroke="#251812" stroke-width="2" fill="none" transform="rotate(20 40 20)"/>
          </g>
        </svg>
        <div class="absolute bottom-5 left-5 right-5 rounded-2xl bg-cream/90 backdrop-blur p-5 ring-1 ring-earth-200/60">
          <p class="text-xs uppercase tracking-widest text-forest-700">{{ $locale === 'vi' ? 'Vùng trọng điểm' : 'Sourcing spotlight' }}</p>
          <p class="mt-1 text-base font-semibold text-ink">Đắk Lắk · Sơn La · Lâm Đồng</p>
          <p class="mt-1 text-sm text-ink/60">{{ $locale === 'vi' ? 'Robusta & Arabica đặc sản, truy xuất từng lô.' : 'Specialty Robusta & Arabica, traceable lot by lot.' }}</p>
        </div>
      </div>
    </div>
  </div>
</section>
