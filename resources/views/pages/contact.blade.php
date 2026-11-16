@php
  use App\Models\Setting;
  use App\Support\ContactProductOptions;
  $contact = array_replace([
    'email' => 'contact@anduongiti.com',
    'phone' => '+84 915 663 683',
    'address_en' => '112 Me Tri Ha, Tu Liem Ward, Hanoi, Vietnam',
    'address_vi' => '112 Mễ Trì Hạ, Phường Từ Liêm, TP Hà Nội, Việt Nam',
    'linkedin' => 'https://www.linkedin.com/company/anduongiti',
  ], Setting::read('contact', []));
  $social = Setting::read('social', []);
  $linkedin = $social['linkedin'] ?? '' ?: ($contact['linkedin'] ?? '');
  $facebook = $social['facebook'] ?? '';
  $shopee = $social['shopee'] ?? '';
  $alibaba = $social['alibaba'] ?? '';
  $address = $locale === 'vi' ? $contact['address_vi'] : $contact['address_en'];
  $pdf = config('site.profile_pdf');
  $productOptions = ContactProductOptions::all($locale);
  $selectedProduct = in_array(old('product', $product), array_keys($productOptions), true) ? old('product', $product) : null;
  $selectedSourceSlug = $errors->has('source_slug') ? null : old('source_slug', $sourceSlug);
  $productDescribedBy = implode(' ', array_filter([
    $errors->has('product') ? 'product-error' : null,
    $errors->has('source_slug') ? 'source_slug-error' : null,
  ])) ?: null;
@endphp
<x-layouts.site :locale="$locale" :title="__('site.contact.title')">
  <x-site.page-header :eyebrow="__('site.contact.eyebrow')" :title="__('site.contact.title')" :lede="__('site.contact.lede')" />

  <section class="container-x py-20 grid gap-12 lg:grid-cols-5">
    <div class="lg:col-span-3 min-w-0 rounded-3xl border border-earth-200/60 bg-white/70 p-8 sm:p-10">
      @if ($sent)
        <p class="eyebrow">{{ $locale === 'vi' ? 'Đã nhận' : 'Received' }}</p>
        <x-site.flash-message type="success" :message="__('site.contact.sent_title')" class="mt-3" />
        <p class="mt-4 text-ink/70 leading-relaxed">{{ __('site.contact.sent_body') }}</p>
        <a href="{{ url()->current() }}" class="btn-ghost mt-8">{{ __('site.contact.sent_back') }}</a>
      @else
        @if ($errors->any())
          <x-site.flash-message
            type="error"
            :message="__('site.contact.error_title')"
            id="contact-error-summary"
            tabindex="-1"
            class="mb-8"
          >
            <ul class="mt-2.5 list-inside list-disc space-y-1">
              @foreach ($errors->getMessages() as $field => $messages)
                @foreach ($messages as $message)
                  <li><a href="#{{ $field === 'source_slug' ? 'product' : str_replace(['.', '[', ']'], ['-', '-', ''], $field) }}" class="underline underline-offset-2">{{ $message }}</a></li>
                @endforeach
              @endforeach
            </ul>
          </x-site.flash-message>
          <script>document.getElementById('contact-error-summary')?.focus();</script>
        @endif
        <form method="POST" action="{{ route('contact.store') }}">
          @csrf
          @if ($selectedSourceSlug)
            <input type="hidden" name="source_slug" value="{{ $selectedSourceSlug }}" />
          @endif
          <div class="grid gap-5 sm:grid-cols-2">
            <x-site.form.input
              name="name"
              :label="__('site.contact.name')"
              autocomplete="name"
              required
            />
            <x-site.form.input
              name="company"
              :label="__('site.contact.company')"
              autocomplete="organization"
            />
            <x-site.form.input
              name="email"
              :label="__('site.contact.email')"
              type="email"
              autocomplete="email"
              required
            />
            <x-site.form.input
              name="country"
              :label="__('site.contact.country')"
              autocomplete="country-name"
            />
          </div>
          <x-site.form.select
            name="product"
            :label="__('site.contact.interest')"
            :options="$productOptions"
            :selected="$selectedProduct"
            :placeholder="__('site.contact.interest_placeholder')"
            :aria-describedby="$productDescribedBy"
            class="mt-5"
          />
          @error('source_slug')<p id="source_slug-error" class="field-error">{{ $message }}</p>@enderror
          <x-site.form.textarea
            name="message"
            :label="__('site.contact.message')"
            :rows="5"
            required
            class="mt-5"
          />
          <button type="submit" class="btn-primary mt-7">
            {{ __('site.contact.submit') }}
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </button>
        </form>
      @endif
    </div>

    <aside class="lg:col-span-2 min-w-0 space-y-6">
      <div class="rounded-3xl bg-forest-900 text-cream p-8">
        <p class="eyebrow !text-gold">{{ __('site.footer.address') }}</p>
        <p class="mt-4 text-base leading-relaxed">{{ $address }}</p>
        <div class="mt-6 space-y-2 text-sm">
          <a href="tel:{{ str_replace(' ', '', $contact['phone']) }}" class="block hover:text-gold">{{ $contact['phone'] }}</a>
          <a href="mailto:{{ $contact['email'] }}" class="block hover:text-gold">{{ $contact['email'] }}</a>
          @if ($linkedin)
            <a href="{{ $linkedin }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 hover:text-gold">
              LinkedIn
              <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17L17 7M9 7h8v8"/></svg>
            </a>
          @endif
        </div>
        @if ($facebook || $shopee || $alibaba || $linkedin)
          <div class="mt-6 border-t border-cream/15 pt-5">
            <p class="text-xs font-semibold uppercase tracking-widest text-gold">{{ __('site.contact.follow_title') }}</p>
            <div class="mt-3 flex items-center gap-3">
              @if ($facebook)
                <a href="{{ $facebook }}" target="_blank" rel="noopener" aria-label="Facebook"
                   class="grid h-12 w-12 place-items-center rounded-full border border-cream/20 transition hover:bg-cream/10">
                  <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 22v-8h2.7l.4-3.1h-3.1V8.9c0-.9.3-1.5 1.6-1.5h1.7V4.6c-.3 0-1.3-.1-2.4-.1-2.4 0-4.1 1.5-4.1 4.2v2.2H7.6V14h2.7v8h3.2z"/></svg>
                </a>
              @endif
              @if ($linkedin)
                <a href="{{ $linkedin }}" target="_blank" rel="noopener" aria-label="LinkedIn"
                   class="grid h-12 w-12 place-items-center rounded-full border border-cream/20 transition hover:bg-cream/10">
                  <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M4.98 3.5C4.98 4.88 3.87 6 2.5 6S0 4.88 0 3.5 1.12 1 2.5 1s2.48 1.12 2.48 2.5zM.22 8h4.56v14H.22V8zm7.7 0h4.37v1.92h.06c.61-1.15 2.1-2.36 4.32-2.36 4.62 0 5.47 3.04 5.47 6.99V22h-4.56v-6.6c0-1.57-.03-3.6-2.2-3.6-2.2 0-2.54 1.72-2.54 3.49V22H7.92V8z"/></svg>
                </a>
              @endif
              @if ($shopee)
                <a href="{{ $shopee }}" target="_blank" rel="noopener" aria-label="Shopee"
                   class="grid h-12 w-12 place-items-center rounded-full border border-cream/20 transition hover:bg-cream/10">
                  <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6.5 8V7a5.5 5.5 0 0111 0v1"/><path d="M3.5 8h17l-1.2 12.1a2 2 0 01-2 1.9H6.7a2 2 0 01-2-1.9L3.5 8z"/><path d="M9 12.5a3 3 0 006 0"/></svg>
                </a>
              @endif
              @if ($alibaba)
                <a href="{{ $alibaba }}" target="_blank" rel="noopener" aria-label="Alibaba"
                   class="grid h-12 w-12 place-items-center rounded-full border border-cream/20 transition hover:bg-cream/10">
                  <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 9l1.2-4.5A2 2 0 016.1 3h11.8a2 2 0 011.9 1.5L21 9"/><path d="M3 9h18v2a3 3 0 01-3 3h0a3 3 0 01-3-3v0a3 3 0 01-3 3h0a3 3 0 01-3-3v0a3 3 0 01-3 3h0a3 3 0 01-3-3V9z"/><path d="M5 15.8V21h14v-5.2"/><path d="M9.5 21v-3.5a1.5 1.5 0 013 0V21"/></svg>
                </a>
              @endif
            </div>
          </div>
        @endif
      </div>

      @if ($shopee)
        <a href="{{ $shopee }}" target="_blank" rel="noopener"
           class="group flex items-center gap-3 rounded-2xl border border-earth-200/60 bg-white/70 p-4 transition hover:bg-white hover:shadow-lg hover:shadow-forest-900/5">
          <span class="grid h-16 w-16 shrink-0 place-items-center rounded-lg bg-gold/15 text-earth-800">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6.5 8V7a5.5 5.5 0 0111 0v1"/><path d="M3.5 8h17l-1.2 12.1a2 2 0 01-2 1.9H6.7a2 2 0 01-2-1.9L3.5 8z"/><path d="M9 12.5a3 3 0 006 0"/></svg>
          </span>
          <span class="min-w-0 flex-1">
            <span class="block text-xs font-semibold uppercase tracking-widest text-earth-700">{{ __('site.contact.follow_title') }}</span>
            <span class="mt-0.5 block truncate font-semibold text-ink">{{ __('site.contact.shopee_label') }}</span>
            <span class="block truncate text-xs text-ink/65">{{ __('site.contact.shopee_sub') }}</span>
          </span>
          <svg class="h-4 w-4 shrink-0 text-forest-700 transition group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 17L17 7M9 7h8v8"/></svg>
        </a>
      @endif
      @if ($alibaba)
        <a href="{{ $alibaba }}" target="_blank" rel="noopener"
           class="group flex items-center gap-3 rounded-2xl border border-earth-200/60 bg-white/70 p-4 transition hover:bg-white hover:shadow-lg hover:shadow-forest-900/5">
          <span class="grid h-16 w-16 shrink-0 place-items-center rounded-lg bg-forest-50 text-forest-700">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 9l1.2-4.5A2 2 0 016.1 3h11.8a2 2 0 011.9 1.5L21 9"/><path d="M3 9h18v2a3 3 0 01-3 3h0a3 3 0 01-3-3v0a3 3 0 01-3 3h0a3 3 0 01-3-3v0a3 3 0 01-3 3h0a3 3 0 01-3-3V9z"/><path d="M5 15.8V21h14v-5.2"/><path d="M9.5 21v-3.5a1.5 1.5 0 013 0V21"/></svg>
          </span>
          <span class="min-w-0 flex-1">
            <span class="block text-xs font-semibold uppercase tracking-widest text-earth-700">B2B</span>
            <span class="mt-0.5 block truncate font-semibold text-ink">{{ __('site.contact.alibaba_label') }}</span>
            <span class="block truncate text-xs text-ink/65">{{ __('site.contact.alibaba_sub') }}</span>
          </span>
          <svg class="h-4 w-4 shrink-0 text-forest-700 transition group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 17L17 7M9 7h8v8"/></svg>
        </a>
      @endif

      <div class="overflow-hidden rounded-3xl border border-earth-200/60 aspect-[4/3]">
        <iframe title="Map" class="h-full w-full" loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                src="https://maps.google.com/maps?q=Me%20Tri%20Ha%20Hanoi&t=&z=14&ie=UTF8&iwloc=&output=embed"></iframe>
      </div>
    </aside>
  </section>
</x-layouts.site>
