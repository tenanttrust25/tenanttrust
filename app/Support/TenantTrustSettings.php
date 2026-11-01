<?php

namespace App\Support;

/**
 * Small file-backed settings store for Tenant Trust.
 *
 * Runtime-editable values (e.g. GST/HST) are kept in
 * storage/app/tenanttrust-settings.json, falling back to config/tenanttrust.php.
 */
class TenantTrustSettings
{
    protected static ?array $cache = null;

    public static function path(): string
    {
        return storage_path('app/tenanttrust-settings.json');
    }

    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $settings = [
            'hst_enabled' => (bool) config('tenanttrust.hst.enabled', false),
            'hst_rate'    => (float) config('tenanttrust.hst.rate', 13.00),
            'postgrid_mode' => (string) config('tenanttrust.postgrid.mode', 'test'),
            'postgrid_test_key' => (string) config('tenanttrust.postgrid.test_key', ''),
            'postgrid_live_key' => (string) config('tenanttrust.postgrid.live_key', ''),
        ];

        $path = self::path();
        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded)) {
                $settings = array_merge($settings, $decoded);
            }
        }

        return self::$cache = $settings;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    public static function put(array $values): void
    {
        $settings = array_merge(self::all(), $values);
        self::$cache = $settings;

        $path = self::path();
        $dir = dirname($path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        file_put_contents($path, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public static function flush(): void
    {
        self::$cache = null;
    }

    public static function hstEnabled(): bool
    {
        return (bool) self::get('hst_enabled', false);
    }

    public static function hstRate(): float
    {
        return (float) self::get('hst_rate', 13.00);
    }

    public static function postgridMode(): string
    {
        $mode = (string) self::get('postgrid_mode', 'test');

        return $mode === 'live' ? 'live' : 'test';
    }

    public static function postgridTestKey(): string
    {
        return trim((string) self::get('postgrid_test_key', ''));
    }

    public static function postgridLiveKey(): string
    {
        return trim((string) self::get('postgrid_live_key', ''));
    }
}
