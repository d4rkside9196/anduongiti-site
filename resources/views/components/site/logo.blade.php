@props(['mark' => 'dark', 'class' => 'h-8 w-auto'])
<img
  src="{{ $mark === 'dark' ? '/anduong-logo-dark.png' : '/anduong-logo-light.png' }}"
  alt="An Duong ITI"
  class="{{ $class }}"
  width="495" height="200" loading="eager" decoding="async"
/>
