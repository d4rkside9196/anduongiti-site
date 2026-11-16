@php $title = $locale === 'vi' ? 'Cà phê & cao su Việt Nam' : 'Vietnamese coffee & rubber'; @endphp
<x-layouts.site :locale="$locale" :title="$title">
  <x-site.hero :locale="$locale" />
  <x-site.stats-counter :locale="$locale" />
  <x-site.value-props :locale="$locale" />
  <x-site.coffee-showcase :locale="$locale" :coffee="$coffee" />
  <x-site.rubber-strip :locale="$locale" :rubber="$rubber" />
  <x-site.supply-map :locale="$locale" />
  <x-site.certifications :locale="$locale" />
  <x-site.home-cta :locale="$locale" />
</x-layouts.site>
