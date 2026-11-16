@props(['locale'])
@php use Mcamara\LaravelLocalization\Facades\LaravelLocalization; @endphp
<section class="container-x py-24">
  <div class="relative overflow-hidden rounded-3xl bg-earth-800 px-8 py-16 sm:px-16 sm:py-20">
    <div class="absolute -bottom-40 -right-32 h-[420px] w-[420px] rounded-full bg-gold/15 blur-3xl"></div>
    <div class="relative max-w-2xl">
      <p class="eyebrow !text-gold">{{ $locale === 'vi' ? 'Bắt đầu hợp tác' : 'Start a partnership' }}</p>
      <h2 class="mt-3 text-3xl sm:text-4xl md:text-5xl font-medium leading-tight text-cream">{{ __('site.home.cta_title') }}</h2>
      <p class="mt-5 text-cream/70 leading-relaxed">{{ __('site.home.cta_lede') }}</p>
      <div class="mt-9 flex flex-wrap gap-3">
        <a href="{{ LaravelLocalization::getLocalizedURL($locale, '/contact') }}" class="btn-primary !bg-gold !text-earth-900 hover:!bg-[#d6b87a]">
          {{ __('site.cta.get_in_touch') }}
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
        </a>
        <a href="{{ LaravelLocalization::getLocalizedURL($locale, '/sustainability') }}" class="inline-flex items-center gap-2 rounded-full border border-cream/30 px-6 py-3 text-sm font-semibold text-cream transition hover:bg-cream/10">
          {{ $locale === 'vi' ? 'Xem chứng nhận' : 'View compliance' }}
        </a>
      </div>
    </div>
  </div>
</section>
