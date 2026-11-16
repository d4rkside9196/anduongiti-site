@props([
  'type' => 'success',
  'message',
])
@php
  $isError = $type === 'error';
@endphp
<div
  role="{{ $isError ? 'alert' : 'status' }}"
  {{ $attributes->merge(['class' => 'alert '.($isError ? 'alert-error' : 'alert-success')]) }}
>
  <p class="alert-message">{{ $message }}</p>
  {{ $slot }}
</div>
