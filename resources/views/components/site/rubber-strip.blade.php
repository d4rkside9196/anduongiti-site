@props(['locale', 'rubber' => null])
@php
  use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
@endphp
@if ($rubber)
@php
  $specs = $rubber->specs ?? [];
@endphp
<section class="container-x pb-16">
  <a href="{{ LaravelLocalization::getLocalizedURL($locale, '/products/rubber') }}" class="group flex flex-col gap-6 rounded-2xl border border-earth-200/60 bg-white/50 p-7 transition hover:bg-white hover:shadow-lg sm:flex-row sm:items-center sm:gap-10">
    <div class="flex items-center gap-4 sm:min-w-[220px]">
      <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-forest-50 text-forest-700">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 010 18M12 3a14 14 0 000 18"/></svg>
      </span>
      <div>
        <p class="text-[11px] font-semibold uppercase tracking-widest text-earth-700">{{ $rubber->eyebrow }}</p>
        <p class="font-serif text-xl font-medium text-ink leading-tight">{{ $rubber->title }}</p>
      </div>
    </div>
    <div class="flex flex-1 flex-col gap-3">
      <div class="flex flex-wrap items-center gap-2">
        @foreach ($specs as [$name, $description])
          <span class="inline-flex items-center rounded-full border border-earth-200 bg-cream px-3 py-1 text-xs font-semibold text-ink/80">{{ $name }}</span>
        @endforeach
      </div>
      <p class="text-sm text-ink/65 leading-relaxed">{{ $rubber->lede }}</p>
      <dl class="grid gap-1 text-xs text-ink/65">
        @foreach ($specs as [$name, $description])
          <div class="flex items-start gap-1.5">
            <dt class="shrink-0 font-semibold text-ink/70">{{ $name }}:</dt>
            <dd>{{ $description }}</dd>
          </div>
        @endforeach
      </dl>
    </div>
    <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-forest-700 shrink-0">
      {{ $locale === 'vi' ? 'Chi tiết' : 'Details' }}
      <svg class="h-3.5 w-3.5 transition group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
    </span>
  </a>
</section>
@endif
