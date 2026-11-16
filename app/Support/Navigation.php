<?php

namespace App\Support;

use App\Models\Setting;

final class Navigation
{
    private const ALLOWED_KEYS = [
        'about', 'products', 'coffee', 'sustainability', 'partners', 'news', 'support', 'contact',
    ];

    public static function allowedKeys(): array
    {
        return self::ALLOWED_KEYS;
    }

    public static function items(): array
    {
        $configured = collect(config('site.nav', []))->keyBy('key');
        $keys = Setting::read('navigation');

        if ($keys === null) {
            $keys = self::allowedKeys();
        } elseif (! self::validKeys($keys)) {
            $keys = self::allowedKeys();
        }

        $items = [];
        foreach ($keys as $key) {
            if (! isset($configured[$key])) {
                continue;
            }

            $items[] = [
                'key' => $key,
                'href' => $configured[$key]['href'],
                'label' => __("site.nav.{$key}"),
            ];
        }

        return $items;
    }

    private static function validKeys(mixed $keys): bool
    {
        if (! is_array($keys) || ! array_is_list($keys)) {
            return false;
        }

        foreach ($keys as $key) {
            if (! is_string($key) || ! in_array($key, self::ALLOWED_KEYS, true)) {
                return false;
            }
        }

        return count($keys) === count(array_unique($keys));
    }
}
