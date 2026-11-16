@props(['locale'])
@php
  use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
  use App\Models\Setting;
  use App\Support\Navigation;
  $navigationItems = Navigation::items();
  $year = date('Y');
  $contact = array_replace([
    'email' => 'contact@anduongiti.com',
    'phone' => '+84 915 663 683',
    'address_en' => '112 Me Tri Ha, Tu Liem Ward, Hanoi, Vietnam',
    'address_vi' => '112 Mễ Trì Hạ, Phường Từ Liêm, TP Hà Nội, Việt Nam',
    'linkedin' => 'https://www.linkedin.com/company/anduongiti',
  ], Setting::read('contact', []));
  // `social` is the canonical row; contact.linkedin kept as fallback so
  // sites configured before the social key keep working.
  $social = Setting::read('social', []);
  $linkedin = $social['linkedin'] ?? '' ?: ($contact['linkedin'] ?? '');
  $facebook = $social['facebook'] ?? '';
  $shopee = $social['shopee'] ?? '';
  $alibaba = $social['alibaba'] ?? '';
  $address = $locale === 'vi' ? $contact['address_vi'] : $contact['address_en'];
  $tagline = config('site.tagline.'.$locale);
  $pdf = config('site.profile_pdf');
@endphp
<footer class="mt-32 bg-forest-900 text-cream/90">
  <div class="container-x grid gap-12 py-20 md:grid-cols-4">
    <div class="md:col-span-2">
      <x-site.logo mark="light" class="h-9 w-auto" />
      <p class="mt-6 max-w-md text-sm leading-relaxed text-cream/70">{{ $tagline }}. {{ config('site.legal_name') }}.</p>
      <div class="mt-6 flex items-center gap-3">
        @if ($facebook)
          <a href="{{ $facebook }}" target="_blank" rel="noopener" aria-label="Facebook"
             class="grid h-11 w-11 place-items-center rounded-full border border-cream/20 transition hover:bg-cream/10">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 22v-8h2.7l.4-3.1h-3.1V8.9c0-.9.3-1.5 1.6-1.5h1.7V4.6c-.3 0-1.3-.1-2.4-.1-2.4 0-4.1 1.5-4.1 4.2v2.2H7.6V14h2.7v8h3.2z"/></svg>
          </a>
        @endif
        @if ($linkedin)
          <a href="{{ $linkedin }}" target="_blank" rel="noopener" aria-label="LinkedIn"
             class="grid h-11 w-11 place-items-center rounded-full border border-cream/20 transition hover:bg-cream/10">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M4.98 3.5C4.98 4.88 3.87 6 2.5 6S0 4.88 0 3.5 1.12 1 2.5 1s2.48 1.12 2.48 2.5zM.22 8h4.56v14H.22V8zm7.7 0h4.37v1.92h.06c.61-1.15 2.1-2.36 4.32-2.36 4.62 0 5.47 3.04 5.47 6.99V22h-4.56v-6.6c0-1.57-.03-3.6-2.2-3.6-2.2 0-2.54 1.72-2.54 3.49V22H7.92V8z"/></svg>
          </a>
        @endif
        @if ($shopee)
          <a href="{{ $shopee }}" target="_blank" rel="noopener" aria-label="Shopee"
             class="grid h-11 w-11 place-items-center rounded-full border border-cream/20 transition hover:bg-cream/10">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6.5 8V7a5.5 5.5 0 0111 0v1"/><path d="M3.5 8h17l-1.2 12.1a2 2 0 01-2 1.9H6.7a2 2 0 01-2-1.9L3.5 8z"/><path d="M9 12.5a3 3 0 006 0"/></svg>
          </a>
        @endif
        @if ($alibaba)
          <a href="{{ $alibaba }}" target="_blank" rel="noopener" aria-label="Alibaba"
             class="grid h-11 w-11 place-items-center rounded-full border border-cream/20 transition hover:bg-cream/10">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 9l1.2-4.5A2 2 0 016.1 3h11.8a2 2 0 011.9 1.5L21 9"/><path d="M3 9h18v2a3 3 0 01-3 3h0a3 3 0 01-3-3v0a3 3 0 01-3 3h0a3 3 0 01-3-3v0a3 3 0 01-3 3h0a3 3 0 01-3-3V9z"/><path d="M5 15.8V21h14v-5.2"/><path d="M9.5 21v-3.5a1.5 1.5 0 013 0V21"/></svg>
          </a>
        @endif
        <a href="mailto:{{ $contact['email'] }}" aria-label="Email"
           class="grid h-11 w-11 place-items-center rounded-full border border-cream/20 transition hover:bg-cream/10">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 6 10-6"/></svg>
        </a>
      </div>
    </div>

    <div>
      <h4 class="text-xs font-semibold uppercase tracking-widest text-gold">{{ __('site.footer.explore') }}</h4>
      <nav aria-label="{{ $locale === 'vi' ? 'Điều hướng chân trang' : 'Footer navigation' }}">
        <ul class="mt-5 space-y-1 text-sm">
          @foreach ($navigationItems as $item)
            <li><a href="{{ LaravelLocalization::getLocalizedURL($locale, $item['href']) }}" class="inline-flex min-h-11 items-center text-cream/70 transition hover:text-cream">{{ $item['label'] }}</a></li>
          @endforeach
        <li class="flex items-center gap-2">
          <a href="{{ $pdf['href'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-cream/70 hover:text-cream">
            {{ $locale === 'vi' ? 'Tài liệu giới thiệu' : 'Company Profile' }}
            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17L17 7M9 7h8v8"/></svg>
          </a>
          <a href="{{ $pdf['href'] }}" download class="text-cream/40 hover:text-gold" aria-label="Download">
            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v12M5 13l7 7 7-7"/></svg>
          </a>
        </li>
        </ul>
      </nav>
    </div>

    <div>
      <h4 class="text-xs font-semibold uppercase tracking-widest text-gold">{{ __('site.footer.contact') }}</h4>
      <ul class="mt-5 space-y-3 text-sm text-cream/70">
        <li>
          <span class="block text-cream/60">{{ __('site.footer.address') }}</span>
          {{ $address }}
        </li>
        <li><a href="tel:{{ str_replace(' ', '', $contact['phone']) }}" class="hover:text-cream">{{ $contact['phone'] }}</a></li>
        <li><a href="mailto:{{ $contact['email'] }}" class="hover:text-cream">{{ $contact['email'] }}</a></li>
      </ul>
    </div>
  </div>
  <div class="border-t border-cream/10">
    <div class="container-x flex flex-col gap-2 py-6 text-xs text-cream/60 sm:flex-row sm:items-center sm:justify-between">
      <p>&copy; {{ $year }} {{ config('site.legal_name') }}. {{ __('site.footer.rights') }}</p>
      <p>Crafted in Hà Nội · {{ str_replace('https://', '', config('site.url')) }}</p>
    </div>
  </div>
</footer>
