@props([
  'name',
  'label',
  'type' => 'text',
  'required' => false,
  'autocomplete' => 'off',
  'value' => null,
])
@php
  $inputId = $attributes->get('id', str_replace(['.', '[', ']'], ['-', '-', ''], $name));
  $errorId = $inputId.'-error';
  $hasError = $errors->has($name);
@endphp
<div {{ $attributes->only('class')->merge(['class' => 'site-form-field']) }}>
  <label for="{{ $inputId }}" class="site-label">
    {{ $label }}
    @if ($required)<span aria-hidden="true">*</span>@endif
  </label>
  <input
    {{ $attributes->except('class')->merge([
      'id' => $inputId,
      'name' => $name,
      'type' => $type,
      'required' => $required,
      'autocomplete' => $autocomplete,
      'aria-describedby' => $hasError ? $errorId : null,
      'aria-invalid' => $hasError ? 'true' : 'false',
      'class' => 'site-field'.($hasError ? ' has-error' : ''),
    ]) }}
    value="{{ old($name, $value) }}"
  />
  @error($name)<p id="{{ $errorId }}" class="field-error">{{ $message }}</p>@enderror
</div>
