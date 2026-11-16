<x-mail::message>
# New enquiry — {{ config('site.name') }}

**{{ $lead->name }}** @if($lead->company) ({{ $lead->company }}) @endif sent an enquiry via the website contact form.

<x-mail::panel>
| | |
|---|---|
| **Name** | {{ $lead->name }} |
| **Company** | {{ $lead->company ?? '—' }} |
| **Email** | [{{ $lead->email }}](mailto:{{ $lead->email }}) |
| **Country** | {{ $lead->country ?? '—' }} |
| **Product of interest** | {{ $lead->product ? __('site.contact.products.'.$lead->product) : '—' }} |
| **Received** | {{ $lead->created_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i') }} (UTC) |
</x-mail::panel>

## Message

{{ $lead->message }}

---

{{ __('site.footer.contact') }}: {{ $contact['email'] }} · {{ $contact['phone'] ?? '' }}

<x-mail::footer>
An Duong International Trade & Investment Co., Ltd — {{ config('site.url') }}
</x-mail::footer>
</x-mail::message>
