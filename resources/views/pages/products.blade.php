<x-layouts.site :locale="$locale" :title="__('site.products.title')">
  <x-site.page-header :eyebrow="$locale === 'vi' ? 'Danh mục' : 'Catalogue'" :title="__('site.products.title')" :lede="__('site.products.lede')" />
  <x-site.coffee-showcase :locale="$locale" :coffee="$coffee" />
  <x-site.rubber-strip :locale="$locale" :rubber="$rubber" />
</x-layouts.site>
