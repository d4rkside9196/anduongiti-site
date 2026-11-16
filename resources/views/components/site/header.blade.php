@props(['locale', 'altUrl'])
@php
  use App\Support\Navigation;
  use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
  $current = '/'.trim(parse_url(url()->current(), PHP_URL_PATH) ?? '', '/');
  if ($locale === 'vi') $current = '/'.preg_replace('#^/?vi/?#', '', $current);
  $current = $current === '' ? '/' : $current;
  $navigationItems = Navigation::items();
@endphp
<header data-site-header class="sticky top-0 z-40 transition-all duration-300">
  <div class="container-x flex items-center justify-between gap-6 py-5 transition-[padding] duration-300">
    <a href="{{ LaravelLocalization::getLocalizedURL($locale, '/') }}" class="inline-flex min-h-11 shrink-0 items-center">
      <x-site.logo class="h-8 w-auto" />
    </a>
    <nav class="hidden lg:flex items-center gap-8" aria-label="{{ $locale === 'vi' ? 'Điều hướng chính' : 'Primary navigation' }}">
      @foreach ($navigationItems as $item)
        @php
          $href = LaravelLocalization::getLocalizedURL($locale, $item['href']);
          $active = $current === $item['href'] || ($item['href'] !== '/' && str_starts_with($current, $item['href']));
        @endphp
        <a href="{{ $href }}" @if ($active) aria-current="page" @endif class="relative inline-flex min-h-11 items-center text-sm font-medium transition {{ $active ? 'text-forest-700' : 'text-ink/80 hover:text-forest-700' }}">
          {{ $item['label'] }}
          @if ($active)<span class="absolute bottom-0 left-0 right-0 h-0.5 rounded-full bg-forest-500" aria-hidden="true"></span>@endif
        </a>
      @endforeach
    </nav>
    <div class="flex items-center gap-3">
      <a href="{{ $altUrl }}" class="hidden sm:inline-flex min-h-11 items-center gap-1.5 text-xs font-semibold uppercase tracking-widest text-earth-700 hover:text-forest-700">
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20"/></svg>
        {{ __('site.lang.switch') }}
      </a>
      <a href="{{ LaravelLocalization::getLocalizedURL($locale, '/contact') }}" class="btn-primary !py-2.5 !px-5 text-xs">
        {{ __('site.cta.partner') }}
      </a>
      <button type="button" id="menu-btn" class="interactive-target lg:hidden -mr-2 p-2" aria-label="{{ $locale === 'vi' ? 'Mở menu' : 'Open menu' }}" aria-controls="mobile-menu" aria-expanded="false">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
      </button>
    </div>
  </div>
  <div id="mobile-menu" class="lg:hidden border-t border-earth-200/60 bg-cream" hidden>
    <nav class="container-x flex flex-col py-3" aria-label="{{ $locale === 'vi' ? 'Điều hướng di động' : 'Mobile navigation' }}">
      @foreach ($navigationItems as $item)
        @php
          $active = $current === $item['href'] || ($item['href'] !== '/' && str_starts_with($current, $item['href']));
        @endphp
        <a href="{{ LaravelLocalization::getLocalizedURL($locale, $item['href']) }}" @if ($active) aria-current="page" @endif class="flex min-h-11 items-center border-b border-earth-100 py-3 text-base font-medium text-ink/90 last:border-0">
          {{ $item['label'] }}
        </a>
      @endforeach
      <a href="{{ $altUrl }}" class="py-3 text-sm font-semibold uppercase tracking-wider text-earth-700 flex min-h-11 items-center">{{ __('site.lang.switch') }}</a>
    </nav>
  </div>
</header>
<script>
  (() => {
    const header = document.querySelector('header[data-site-header]');
    const menuButton = document.getElementById('menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    if (!header || !menuButton || !mobileMenu) return;

    const setMenu = (open, { moveFocus = false, restoreFocus = false } = {}) => {
      mobileMenu.hidden = !open;
      menuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open && moveFocus) mobileMenu.querySelector('a')?.focus();
      if (!open && restoreFocus) menuButton.focus();
    };
    const toggleMenu = () => setMenu(mobileMenu.hidden, { moveFocus: mobileMenu.hidden });

    menuButton.addEventListener('click', () => setMenu(mobileMenu.hidden, { moveFocus: mobileMenu.hidden }));
    menuButton.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        toggleMenu();
      }
    });
    mobileMenu.addEventListener('click', (event) => {
      if (event.target.closest('a')) setMenu(false);
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !mobileMenu.hidden) {
        event.preventDefault();
        setMenu(false, { restoreFocus: true });
      }
    });
    document.addEventListener('pointerdown', (event) => {
      if (!mobileMenu.hidden && !header.contains(event.target)) setMenu(false);
    });
  })();
</script>
