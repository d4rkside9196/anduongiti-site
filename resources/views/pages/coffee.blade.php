@php
  use Illuminate\Support\Str;
  use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
  $shopee = $social['shopee'] ?? '';
@endphp
<x-layouts.site :locale="$locale" :title="__('site.coffee.title')" :description="__('site.coffee.lede')">
  <x-site.page-header :eyebrow="__('site.coffee.eyebrow')" :title="__('site.coffee.title')" :lede="__('site.coffee.lede')" />

  @foreach ($categories as $category)
    <section class="container-x pt-20 pb-4 first-of-type:pt-16">
      <div class="flex items-end justify-between gap-6 border-b border-earth-200/60 pb-5">
        <h2 class="font-serif text-3xl sm:text-4xl font-medium tracking-tight text-ink">
          {{ $category['name'] }}
        </h2>
        <p class="pr-16 sm:pr-20 text-xs font-semibold uppercase tracking-widest text-earth-700 whitespace-nowrap">
          {{ str_pad(count($category['products']), 2, '0', STR_PAD_LEFT) }} {{ $locale === 'vi' ? 'sản phẩm' : 'products' }}
        </p>
      </div>

      <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($category['products'] as $product)
          <article class="group flex flex-col overflow-hidden rounded-3xl border border-earth-200/60 bg-white/70 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-forest-900/10">
            <a href="{{ LaravelLocalization::getLocalizedURL($locale, '/coffee/'.$product->slug) }}" class="block overflow-hidden bg-earth-50">
              <img src="{{ $product->image_url }}" alt="{{ $product->image_alt ?: $product->title }}"
                   class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" />
            </a>
            <div class="flex flex-1 flex-col p-7">
              <div class="flex items-center gap-2">
                <span class="rounded-full bg-forest-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-forest-700">
                  {{ $category['name'] }}
                </span>
                @if ($product->eyebrow)
                  <span class="text-[11px] uppercase tracking-widest text-earth-600">{{ $product->eyebrow }}</span>
                @endif
              </div>
              <h3 class="mt-4 font-serif text-xl font-medium leading-snug text-ink">
                <a href="{{ LaravelLocalization::getLocalizedURL($locale, '/coffee/'.$product->slug) }}">{{ $product->title }}</a>
              </h3>
              <p class="mt-2 flex-1 text-sm leading-relaxed text-ink/65">{{ Str::limit($product->description, 120) }}</p>
              <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                <a href="{{ LaravelLocalization::getLocalizedURL($locale, '/coffee/'.$product->slug) }}"
                   class="inline-flex items-center gap-1.5 text-sm font-semibold text-forest-700 transition hover:text-forest-800">
                  {{ __('site.coffee.view_details') }}
                  <svg class="h-3.5 w-3.5 transition group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                </a>
                @if ($shopee)
                  <a href="{{ $shopee }}" target="_blank" rel="noopener"
                     class="inline-flex items-center gap-1.5 rounded-full border border-gold/50 bg-gold/10 px-3 py-1.5 text-xs font-semibold text-earth-800 transition hover:bg-gold/25">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 7V6a6 6 0 0112 0v1h3v13a1 1 0 01-1 1H4a1 1 0 01-1-1V7h3zm2 0h8V6a4 4 0 00-8 0v1z"/></svg>
                    {{ __('site.coffee.buy_shopee') }}
                  </a>
                @endif
              </div>
            </div>
          </article>
        @endforeach
      </div>
    </section>
  @endforeach

  @if ($categories->isEmpty())
    <section class="container-x py-24 text-center">
      <p class="eyebrow">{{ __('site.coffee.eyebrow') }}</p>
      <p class="mt-4 text-lg text-ink/60">{{ __('site.coffee.lede') }}</p>
    </section>
  @endif

  <section class="container-x pb-8 pt-20">
    <div class="flex flex-col items-center gap-6 rounded-3xl bg-forest-900 p-12 text-center text-cream sm:p-16">
      <h2 class="max-w-2xl font-serif text-3xl sm:text-4xl font-medium leading-snug text-cream">
        {{ __('site.home.cta_title') }}
      </h2>
      <p class="max-w-xl text-cream/70">{{ __('site.home.cta_lede') }}</p>
      <a href="{{ route('contact') }}" class="btn-primary !bg-gold !text-earth-900 hover:!bg-gold/90">{{ __('site.cta.get_in_touch') }}</a>
    </div>
  </section>
</x-layouts.site>
