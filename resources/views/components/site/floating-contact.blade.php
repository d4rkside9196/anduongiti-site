@props(['locale'])
@php
  use App\Models\Setting;
  $contact = array_replace([
    'email' => 'contact@anduongiti.com',
    'phone' => '+84 915 663 683',
  ], Setting::read('contact', []));
  $phone = $contact['phone'] ?? '';
  $email = $contact['email'] ?? '';
  $zalo = $contact['zalo'] ?? '';
  $whatsapp = $contact['whatsapp'] ?? '';
@endphp
@if ($zalo || $whatsapp || $phone || $email)
  <nav class="mx-5 mb-5 flex flex-row items-center justify-end gap-3 sm:fixed sm:bottom-5 sm:right-5 sm:z-30 sm:mx-0 sm:mb-0 sm:flex-col" aria-label="{{ $locale === 'vi' ? 'Liên hệ nhanh' : 'Quick contact' }}">
    @if ($zalo)
      <a href="https://zalo.me/{{ preg_replace('/[^0-9]/', '', $zalo) }}" target="_blank" rel="noopener"
         aria-label="Zalo" title="Zalo · {{ $zalo }}"
         class="grid h-12 w-12 place-items-center rounded-full bg-[#0180c7] text-white shadow-lg shadow-forest-900/20 transition hover:scale-110">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.5 2 2 5.9 2 10.7c0 2.6 1.3 4.9 3.4 6.5-.2.9-.7 2.2-1.6 3.2 0 0 2.2-.4 4-1.5.1-.1.3-.2.4-.3.6.1 1.2.2 1.8.2 5.5 0 10-3.9 10-8.1S17.5 2 12 2zm4.9 11.2c0 .5-.4.9-.9.9h-.5v.4c0 .5-.4.9-.9.9h-.3c-.5 0-.9-.4-.9-.9v-.4H9.6c-.5 0-.9-.4-.9-.9v-.4c0-.5.4-.9.9-.9h3.2l-3.9-4.4c-.3-.4-.3-1 .1-1.3.4-.3.9-.3 1.2 0l2.6 2.9V7.4c0-.5.4-.9.9-.9h.3c.5 0 .9.4.9.9v2.7l2.6-2.9c.3-.4.9-.4 1.2 0 .4.3.4.9.1 1.3l-2.4 2.8h1c.5 0 .9.4.9.9v1z"/></svg>
      </a>
    @endif
    @if ($whatsapp)
      <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}" target="_blank" rel="noopener"
         aria-label="WhatsApp" title="WhatsApp · {{ $whatsapp }}"
         class="grid h-12 w-12 place-items-center rounded-full bg-[#25d366] text-white shadow-lg shadow-forest-900/20 transition hover:scale-110">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2zm5.5 14.1c-.2.6-1.2 1.2-1.7 1.2-.4.1-1 .1-1.6-.1-.4-.1-.9-.3-1.5-.5-2.6-1.1-4.3-3.7-4.4-3.9-.1-.2-1.1-1.4-1.1-2.7 0-1.3.7-1.9.9-2.2.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.4.2.5.7 1.8.8 1.9.1.1.1.3 0 .5-.1.2-.2.4-.3.5l-.4.5c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.7-.1.2-.2.7-.8.9-1.1.2-.3.4-.2.7-.1.2.1 1.6.8 1.8.9.3.1.5.2.5.3.1.2.1.7-.1 1.2z"/></svg>
      </a>
    @endif
    @if ($phone)
      <a href="tel:{{ str_replace(' ', '', $phone) }}" aria-label="{{ $locale === 'vi' ? 'Gọi điện' : 'Call us' }}"
         title="Tel · {{ $phone }}"
         class="grid h-12 w-12 place-items-center rounded-full bg-forest-700 text-cream shadow-lg shadow-forest-900/20 transition hover:scale-110">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h3a2 2 0 012 1.7c.1 1 .4 2 .7 2.9a2 2 0 01-.5 2.1L8 10a16 16 0 006 6l1.3-1.3a2 2 0 012.1-.5c.9.3 1.9.6 2.9.7a2 2 0 011.7 2z"/></svg>
      </a>
    @endif
    @if ($email)
      <a href="mailto:{{ $email }}" aria-label="Email" title="{{ $email }}"
         class="grid h-12 w-12 place-items-center rounded-full bg-earth-700 text-cream shadow-lg shadow-forest-900/20 transition hover:scale-110">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 6 10-6"/></svg>
      </a>
    @endif
  </nav>
@endif
