@props(['locale', 'coffee' => null])
@php
  use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
@endphp
@if ($coffee)
@php
  $specs = $coffee->specs ?? [];
  $origins = $coffee->origins ?? [];
@endphp
<section class="container-x py-24">
  <x-site.section-title :eyebrow="$coffee->eyebrow" :title="$coffee->title" :lede="$coffee->lede" />

  <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ($specs as $i => [$name, $description])
      <article class="group relative overflow-hidden rounded-3xl border border-earth-200/60 bg-gradient-to-br from-earth-50 to-cream p-7 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-forest-900/5">
        <div class="grid h-12 w-12 place-items-center rounded-xl bg-earth-700 text-cream font-serif font-semibold">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</div>
        <h3 class="mt-5 font-serif text-xl font-medium text-ink">{{ $name }}</h3>
        <p class="mt-2 text-sm text-ink/65 leading-relaxed">{{ $description }}</p>
      </article>
    @endforeach
  </div>

  <div class="mt-10 grid gap-6 lg:grid-cols-3 items-stretch">
    <div class="lg:col-span-2 rounded-3xl bg-forest-900 text-cream p-10">
      <div class="flex items-center gap-3">
        <span class="grid h-10 w-10 place-items-center rounded-lg bg-cream/10 text-gold">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 8h13a4 4 0 010 8h-1M3 8v8a4 4 0 004 4h5a4 4 0 004-4M6 4v2M10 4v2M14 4v2"/></svg>
        </span>
        <p class="eyebrow !text-gold">{{ $locale === 'vi' ? 'Vùng nguyên liệu' : 'Origins' }}</p>
      </div>
      <h3 class="mt-4 font-serif text-2xl sm:text-3xl text-cream leading-snug">
        {{ $locale === 'vi' ? 'Các vùng nguyên liệu cà phê' : 'Coffee origin regions' }}
      </h3>
      <ul class="mt-6 grid gap-5 sm:grid-cols-2">
        @foreach ($origins as $origin)
          <li class="flex items-start gap-3 rounded-2xl bg-cream/5 p-5 text-sm font-semibold text-cream">
            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-gold"></span>
            {{ $origin }}
          </li>
        @endforeach
      </ul>
    </div>

    <a href="{{ LaravelLocalization::getLocalizedURL($locale, '/products/coffee') }}" class="group flex flex-col justify-between rounded-3xl bg-gold/15 border border-gold/30 p-10 transition hover:bg-gold/25">
      <div>
        <p class="eyebrow text-earth-800">{{ $locale === 'vi' ? 'Đầy đủ thông tin' : 'Full catalogue' }}</p>
        <h3 class="mt-3 font-serif text-2xl text-ink leading-tight">
          {{ $locale === 'vi' ? 'Khám phá toàn bộ dải sản phẩm cà phê' : 'Explore the full coffee catalogue' }}
        </h3>
        <p class="mt-3 text-sm text-ink/65 leading-relaxed">
          {{ $locale === 'vi' ? 'Spec đầy đủ, MOQ, packaging, profile rang và nguồn gốc lô hàng.' : 'Full specs, MOQ, packaging, roast profiles and lot-level origin.' }}
        </p>
      </div>
      <span class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-forest-700">
        {{ $locale === 'vi' ? 'Xem chi tiết cà phê' : 'View coffee details' }}
        <svg class="h-3.5 w-3.5 transition group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
      </span>
    </a>
  </div>
</section>
@endif
