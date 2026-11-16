@php
  use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
  $zalo = \App\Models\Setting::read('contact', [])['zalo'] ?? '';
  $whatsapp = \App\Models\Setting::read('contact', [])['whatsapp'] ?? '';
@endphp
<x-layouts.site :locale="$locale" :title="$product->title" :description="\Illuminate\Support\Str::limit($product->description, 150)">
  <section class="container-x pt-16 pb-4">
    <a href="{{ LaravelLocalization::getLocalizedURL($locale, '/coffee') }}"
       class="inline-flex items-center gap-1.5 text-sm font-semibold text-earth-700 transition hover:text-forest-700">
      <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      {{ __('site.coffee.back') }}
    </a>
  </section>

  <section class="container-x py-10 grid gap-12 lg:grid-cols-2 items-start">
    <div class="overflow-hidden rounded-3xl border border-earth-200/60 bg-earth-50">
      <img src="{{ $product->image_url }}" alt="{{ $product->image_alt ?: $product->title }}" class="aspect-[4/3] w-full object-cover" />
    </div>

    <div>
      <div class="flex flex-wrap items-center gap-2">
        <span class="rounded-full bg-forest-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-forest-700">
          {{ $product->category->name }}
        </span>
        @if ($product->eyebrow)
          <span class="text-xs uppercase tracking-widest text-earth-600">{{ $product->eyebrow }}</span>
        @endif
      </div>
      <h1 class="mt-4 text-4xl sm:text-5xl font-medium leading-[1.05] tracking-tight text-ink">{{ $product->title }}</h1>

      @if ($product->specs && count($product->specs))
        <p class="eyebrow mt-10">{{ __('site.coffee.specs_title') }}</p>
        <ul class="mt-4 grid gap-2 sm:grid-cols-2">
          @foreach ($product->specs as $spec)
            <li class="flex items-start gap-2.5 rounded-2xl border border-earth-200/60 bg-white/60 px-4 py-3 text-sm text-ink/80">
              <svg class="mt-0.5 h-4 w-4 shrink-0 text-forest-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
              {{ $spec }}
            </li>
          @endforeach
        </ul>
      @endif

      <div class="mt-10 flex flex-wrap items-center gap-3">
        <a href="{{ route('contact', ['product' => $product->slug]) }}" class="btn-primary">
          {{ __('site.coffee.request_quote') }}
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
        </a>
        @if ($whatsapp)
          <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}" target="_blank" rel="noopener"
             class="btn-ghost">
            <svg class="h-4 w-4 text-forest-600" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2zm5.5 14.1c-.2.6-1.2 1.2-1.7 1.2-.4.1-1 .1-1.6-.1-.4-.1-.9-.3-1.5-.5-2.6-1.1-4.3-3.7-4.4-3.9-.1-.2-1.1-1.4-1.1-2.7 0-1.3.7-1.9.9-2.2.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.4.2.5.7 1.8.8 1.9.1.1.1.3 0 .5-.1.2-.2.4-.3.5l-.4.5c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.7-.1.2-.2.7-.8.9-1.1.2-.3.4-.2.7-.1.2.1 1.6.8 1.8.9.3.1.5.2.5.3.1.2.1.7-.1 1.2z"/></svg>
            {{ __('site.coffee.chat_whatsapp') }}
          </a>
        @endif
        @if ($zalo)
          <a href="https://zalo.me/{{ preg_replace('/[^0-9]/', '', $zalo) }}" target="_blank" rel="noopener"
             class="btn-ghost">
            <svg class="h-4 w-4 text-sky-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.5 2 2 5.9 2 10.7c0 2.6 1.3 4.9 3.4 6.5-.2.9-.7 2.2-1.6 3.2 0 0 2.2-.4 4-1.5.1-.1.3-.2.4-.3.6.1 1.2.2 1.8.2 5.5 0 10-3.9 10-8.1S17.5 2 12 2zm4.9 11.2c0 .5-.4.9-.9.9h-.5v.4c0 .5-.4.9-.9.9h-.3c-.5 0-.9-.4-.9-.9v-.4H9.6c-.5 0-.9-.4-.9-.9v-.4c0-.5.4-.9.9-.9h3.2l-3.9-4.4c-.3-.4-.3-1 .1-1.3.4-.3.9-.3 1.2 0l2.6 2.9V7.4c0-.5.4-.9.9-.9h.3c.5 0 .9.4.9.9v2.7l2.6-2.9c.3-.4.9-.4 1.2 0 .4.3.4.9.1 1.3l-2.4 2.8h1c.5 0 .9.4.9.9v1z"/></svg>
            {{ __('site.coffee.chat_zalo') }}
          </a>
        @endif
      </div>

      <p class="mt-6 text-xs leading-relaxed text-ink/50">{{ __('site.coffee.wholesale_note') }}</p>
    </div>
  </section>

  <section class="container-x py-12">
    <div class="grid gap-10 lg:grid-cols-3">
      <div class="lg:col-span-2">
        <p class="eyebrow">{{ __('site.coffee.description_title') }}</p>
        <p class="mt-4 whitespace-pre-line text-base leading-relaxed text-ink/75">{{ $product->description }}</p>
      </div>
      <aside class="rounded-3xl bg-forest-50 p-8">
        <p class="eyebrow">{{ __('site.cta.get_in_touch') }}</p>
        <p class="mt-4 text-sm leading-relaxed text-ink/70">{{ __('site.coffee.wholesale_note') }}</p>
        <a href="{{ route('contact', ['product' => $product->slug]) }}" class="btn-primary mt-6 w-full justify-center">
          {{ __('site.coffee.request_quote') }}
        </a>
        @if (($social['shopee'] ?? '') || ($social['alibaba'] ?? ''))
          <div class="mt-6 border-t border-earth-200/70 pt-5 space-y-2 text-sm">
            @if ($social['shopee'] ?? '')
              <a href="{{ $social['shopee'] }}" target="_blank" rel="noopener" class="flex items-center gap-2 text-ink/70 hover:text-forest-700">
                {{ __('site.contact.shopee_label') }} — {{ __('site.coffee.buy_shopee') }}
              </a>
            @endif
            @if ($social['alibaba'] ?? '')
              <a href="{{ $social['alibaba'] }}" target="_blank" rel="noopener" class="flex items-center gap-2 text-ink/70 hover:text-forest-700">
                {{ __('site.contact.alibaba_label') }} — B2B
              </a>
            @endif
          </div>
        @endif
      </aside>
    </div>
  </section>

  @if ($related->count())
    <section class="container-x pb-24">
      <p class="eyebrow">{{ __('site.coffee.related_title') }}</p>
      <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($related as $r)
          <a href="{{ LaravelLocalization::getLocalizedURL($locale, '/coffee/'.$r->slug) }}"
             class="group overflow-hidden rounded-3xl border border-earth-200/60 bg-white/70 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-forest-900/10">
            <div class="overflow-hidden bg-earth-50">
              <img src="{{ $r->image_url }}" alt="{{ $r->image_alt ?: $r->title }}" class="aspect-[16/10] w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" />
            </div>
            <div class="p-5">
              <h3 class="font-serif text-lg font-medium leading-snug text-ink">{{ $r->title }}</h3>
              <p class="mt-1 text-xs text-ink/50">{{ Str::limit($r->description, 70) }}</p>
            </div>
          </a>
        @endforeach
      </div>
    </section>
  @endif

  <script type="application/ld+json">
    {!! json_encode([
      '@context' => 'https://schema.org',
      '@type' => 'Product',
      'name' => $product->title,
      'sku' => $product->slug,
      'image' => url($product->image_url),
      'description' => \Illuminate\Support\Str::limit($product->description, 300),
      'category' => $product->category->name,
      'brand' => ['@type' => 'Brand', 'name' => config('site.name')],
      'countryOfOrigin' => ['@type' => 'Country', 'name' => 'Vietnam'],
      'additionalProperty' => collect($product->specs ?? [])->take(10)->map(fn ($spec) => [
        '@type' => 'PropertyValue',
        'name' => 'Specification',
        'value' => $spec,
      ])->all(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
  </script>
</x-layouts.site>
