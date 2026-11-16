@props(['locale'])
<a
  href="#main-content"
  class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-full focus:bg-forest-900 focus:px-5 focus:py-3 focus:text-sm focus:font-semibold focus:text-cream focus:shadow-lg"
>
  {{ $locale === 'vi' ? 'Chuyển đến nội dung chính' : 'Skip to main content' }}
</a>
