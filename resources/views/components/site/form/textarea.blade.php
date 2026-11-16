@props([
  'name',
  'label',
  'rows' => 5,
  'required' => false,
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
  <textarea
    {{ $attributes->except('class')->merge([
      'id' => $inputId,
      'name' => $name,
      'rows' => $rows,
      'required' => $required,
      'aria-describedby' => $hasError ? $errorId : null,
      'aria-invalid' => $hasError ? 'true' : 'false',
      'class' => 'site-field'.($hasError ? ' has-error' : ''),
    ]) }}
  >{{ old($name) }}</textarea>
  @error($name)<p id="{{ $errorId }}" class="field-error">{{ $message }}</p>@enderror
</div>
