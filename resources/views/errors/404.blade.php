@php
  $locale = app()->getLocale();
  use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
@endphp
<x-layouts.site :locale="$locale" title="404">
  <section class="container-x grid place-items-center py-32 text-center">
    <p class="font-serif text-[8rem] font-medium leading-none text-forest-300">404</p>
    <h1 class="mt-4 text-3xl sm:text-4xl font-medium text-ink">{{ $locale === 'vi' ? 'Không tìm thấy trang' : 'Page not found' }}</h1>
    <p class="mt-4 max-w-md text-ink/65">
      {{ $locale === 'vi' ? 'Có vẻ trang bạn tìm đã bị di chuyển hoặc không tồn tại.' : 'The page you are looking for has moved or never existed.' }}
    </p>
    <a href="{{ LaravelLocalization::getLocalizedURL($locale, '/') }}" class="btn-primary mt-8">
      {{ $locale === 'vi' ? 'Về trang chủ' : 'Back to home' }}
    </a>
  </section>
</x-layouts.site>
