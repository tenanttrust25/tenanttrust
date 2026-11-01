<?php

namespace App\Support;

/**
 * Best-effort parser for the way Tenant Trust stores a full address in one
 * string, e.g. "473 Montague Ave, Unit 2, Sudbury, P3C 4G6".
 */
class AddressParser
{
    /**
     * @return array{0: ?string, 1: ?string, 2: ?string, 3: ?string, 4: ?string}
     *         [street, unit, city, province, postal]
     */
    public static function parse(?string $raw): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [null, null, null, 'ON', null];
        }

        $seg = array_values(array_filter(
            array_map('trim', explode(',', $raw)),
            fn ($s) => $s !== ''
        ));

        $street = $seg[0] ?? null;

        // Second segment may be a unit ("Unit 2", "#2", "Apt 4"...) or the city.
        $unit = null;
        if (isset($seg[1]) && preg_match('/^(unit|apt|apartment|suite|ste|#)\b/i', $seg[1])) {
            $unit = $seg[1];
            array_splice($seg, 1, 1);
        }

        // Last segment is usually the postal code.
        $postal = null;
        if (count($seg) >= 2 && preg_match('/^[A-Za-z]\d[A-Za-z]\s?\d[A-Za-z]\d$/', end($seg))) {
            $postal = array_pop($seg);
        }

        $city = $seg[1] ?? null;
        $prov = 'ON';
        if (isset($seg[2]) && preg_match('/^(ON|Ontario|On)$/i', $seg[2])) {
            $prov = 'ON';
        }

        return [$street, $unit, $city, $prov, $postal];
    }
}
