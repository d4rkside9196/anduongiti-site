@props([
  'name',
  'label',
  'options' => [],
  'selected' => null,
  'required' => false,
  'placeholder' => null,
])
@php
  $inputId = $attributes->get('id', str_replace(['.', '[', ']'], ['-', '-', ''], $name));
  $errorId = $inputId.'-error';
  $hasError = $errors->has($name);
  $currentValue = old($name, $selected);
@endphp
<div {{ $attributes->only('class')->merge(['class' => 'site-form-field']) }}>
  <label for="{{ $inputId }}" class="site-label">
    {{ $label }}
    @if ($required)<span aria-hidden="true">*</span>@endif
  </label>
  <select
    {{ $attributes->except('class')->merge([
      'id' => $inputId,
      'name' => $name,
      'required' => $required,
      'aria-describedby' => $hasError ? $errorId : null,
      'aria-invalid' => $hasError ? 'true' : 'false',
      'class' => 'site-field'.($hasError ? ' has-error' : ''),
    ]) }}
  >
    @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
    @foreach ($options as $optionValue => $optionLabel)
      <option value="{{ $optionValue }}" @selected($currentValue !== null && (string) $currentValue === (string) $optionValue)>{{ $optionLabel }}</option>
    @endforeach
  </select>
  @error($name)<p id="{{ $errorId }}" class="field-error">{{ $message }}</p>@enderror
</div>
