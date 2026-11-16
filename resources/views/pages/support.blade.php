@php
  use App\Models\Setting;
  $contact = array_replace([
    'email' => 'contact@anduongiti.com',
    'phone' => '+84 915 663 683',
  ], Setting::read('contact', []));
  $zalo = $contact['zalo'] ?? '';
  $whatsapp = $contact['whatsapp'] ?? '';
@endphp
<x-layouts.site :locale="$locale" :title="__('site.support.title')" :description="__('site.support.lede')">
  <x-site.page-header :eyebrow="__('site.support.eyebrow')" :title="__('site.support.title')" :lede="__('site.support.lede')" />

  <section class="container-x pt-20 pb-4">
    <p class="eyebrow">{{ __('site.support.channels_title') }}</p>
    <div class="mt-8 grid gap-5 md:grid-cols-3">
      <a href="mailto:{{ $contact['email'] }}"
         class="group rounded-3xl border border-earth-200/60 bg-white/70 p-8 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-forest-900/5">
        <span class="grid h-12 w-12 place-items-center rounded-xl bg-forest-50 text-forest-700">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 6 10-6"/></svg>
        </span>
        <h3 class="mt-5 font-serif text-xl font-medium text-ink">{{ __('site.support.email_label') }}</h3>
        <p class="mt-2 text-sm text-ink/65">{{ __('site.support.email_sub') }}</p>
        <p class="mt-4 text-sm font-semibold text-forest-700">{{ $contact['email'] }}</p>
      </a>

      <div class="rounded-3xl border border-earth-200/60 bg-white/70 p-8">
        <span class="grid h-12 w-12 place-items-center rounded-xl bg-forest-50 text-forest-700">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h3a2 2 0 012 1.7c.1 1 .4 2 .7 2.9a2 2 0 01-.5 2.1L8 10a16 16 0 006 6l1.3-1.3a2 2 0 012.1-.5c.9.3 1.9.6 2.9.7a2 2 0 011.7 2z"/></svg>
        </span>
        <h3 class="mt-5 font-serif text-xl font-medium text-ink">{{ __('site.support.phone_label') }}</h3>
        <p class="mt-2 text-sm text-ink/65">{{ __('site.support.phone_sub') }}</p>
        <div class="mt-4 space-y-1.5 text-sm font-semibold text-forest-700">
          <p><a href="tel:{{ str_replace(' ', '', $contact['phone']) }}" class="hover:text-forest-800">{{ $contact['phone'] }}</a></p>
          @if ($whatsapp)<p><a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}" target="_blank" rel="noopener" class="hover:text-forest-800">WhatsApp · {{ $whatsapp }}</a></p>@endif
          @if ($zalo)<p><a href="https://zalo.me/{{ preg_replace('/[^0-9]/', '', $zalo) }}" target="_blank" rel="noopener" class="hover:text-forest-800">Zalo · {{ $zalo }}</a></p>@endif
        </div>
      </div>

      <div class="rounded-3xl border border-earth-200/60 bg-white/70 p-8">
        <span class="grid h-12 w-12 place-items-center rounded-xl bg-forest-50 text-forest-700">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        </span>
        <h3 class="mt-5 font-serif text-xl font-medium text-ink">{{ __('site.support.hours_label') }}</h3>
        <p class="mt-2 text-sm text-ink/65">{{ __('site.support.hours_sub') }}</p>
        <p class="mt-4 text-sm font-semibold text-forest-700">{{ __('site.support.hours_value') }}</p>
      </div>
    </div>
  </section>

  <section class="container-x pt-20 pb-8">
    <x-site.section-title :eyebrow="$locale === 'vi' ? 'Hỏi & đáp' : 'Q&A'" :title="__('site.support.faq_title')" :lede="__('site.support.faq_lede')" />

    <div class="mx-auto mt-10 max-w-3xl space-y-10">
      @foreach ($faqs as $category => $items)
        <div>
          <h3 class="text-xs font-semibold uppercase tracking-[0.18em] text-earth-700">
            {{-- Admin-defined categories fall back to a plain ucfirst label,
                 never a raw translation key. --}}
            {{ \Illuminate\Support\Facades\Lang::has("site.support.categories.{$category}") ? __("site.support.categories.{$category}") : ucfirst($category) }}
          </h3>
          <div class="mt-4 divide-y divide-earth-200/70 overflow-hidden rounded-3xl border border-earth-200/60 bg-white/70">
            @foreach ($items as $faq)
              <details class="group">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-5 text-sm font-semibold text-ink transition hover:bg-earth-50 [&::-webkit-details-marker]:hidden">
                  {{ $faq->question }}
                  <svg class="h-4 w-4 shrink-0 text-forest-600 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
                </summary>
                <p class="px-6 pb-6 text-sm leading-relaxed text-ink/70">{{ $faq->answer }}</p>
              </details>
            @endforeach
          </div>
        </div>
      @endforeach

      @if ($faqs->isEmpty())
        <p class="text-center text-sm text-ink/65">{{ __('site.support.faq_lede') }}</p>
      @endif
    </div>
  </section>

  <section class="container-x pb-8 pt-20">
    <div class="flex flex-col items-center gap-6 rounded-3xl bg-forest-900 p-12 text-center text-cream sm:p-16">
      <h2 class="max-w-2xl font-serif text-3xl sm:text-4xl font-medium leading-snug text-cream">
        {{ __('site.support.cta_title') }}
      </h2>
      <p class="max-w-xl text-cream/70">{{ __('site.support.cta_lede') }}</p>
      <a href="{{ LaravelLocalization::getLocalizedURL($locale, '/contact') }}" class="btn-primary !bg-gold !text-earth-900 hover:!bg-gold/90">{{ __('site.support.cta_button') }}</a>
    </div>
  </section>
</x-layouts.site>
