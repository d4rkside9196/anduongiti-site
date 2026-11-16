<?php

namespace App\Http\Controllers;

use App\Mail\NewLeadMail;
use App\Models\CoffeeProduct;
use App\Models\Lead;
use App\Models\Setting;
use App\Support\ContactProductOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function store(Request $request)
    {
        // Legacy labels and visible dynamic categories share one allowlist.
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email:strict,dns,spoof', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'company' => ['nullable', 'string', 'max:200'],
            'country' => ['nullable', 'string', 'max:100'],
            'product' => ['nullable', 'string', 'max:264', Rule::in(array_keys(ContactProductOptions::all(app()->getLocale())))],
            'source_slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::exists('coffee_products', 'slug')->where(fn ($query) => $query
                    ->where('locale', app()->getLocale())
                    ->where('visible', true)),
            ],
        ]);

        $sourceProduct = null;
        $productKey = $data['product'] ?? null;
        if (filled($data['source_slug'] ?? null)) {
            // Validation above constrains this lookup to the same active
            // locale and visibility rules. A valid SKU owns the broad routing
            // category; the client-supplied select value is only a fallback
            // for direct contact submissions without a source SKU.
            $sourceProduct = CoffeeProduct::visible()
                ->forLocale(app()->getLocale())
                ->where('slug', $data['source_slug'])
                ->firstOrFail();
            $productKey = $sourceProduct->contactProductKey();
        }

        $lead = Lead::create([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'message' => $data['message'],
            'company' => $data['company'] ?? null,
            'country' => $data['country'] ?? null,
            'product' => $productKey,
            'source_slug' => $sourceProduct?->slug,
            'coffee_product_id' => $sourceProduct?->getKey(),
            'source_locale' => $sourceProduct?->locale,
            'source_product_title' => $sourceProduct?->title,
            'source_category_slug' => $sourceProduct?->category?->slug,
            'last_activity_at' => now(),
        ]);

        // Notify the sales inbox. A mail outage must never eat a lead —
        // log and continue, the row above is the source of truth.
        try {
            $recipient = Setting::read('contact')['email'] ?? config('mail.from.address');
            Mail::to($recipient)->send(new NewLeadMail($lead));
        } catch (\Throwable $e) {
            Log::error('Failed to send NewLeadMail for lead '.$lead->getKey().': '.$e->getMessage());
        }

        // Always redirect to a known route (the contact page in the active
        // locale) so a malicious page that hosts a copy of our form can't
        // chain a redirect to an arbitrary URL through us.
        return redirect()->route('contact', ['sent' => 1]);
    }
}
