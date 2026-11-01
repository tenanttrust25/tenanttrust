<?php

namespace App\Services;

class EmailDomainGuard
{
    /**
     * Whether an email address comes from a banned domain or is an
     * individually blocked address.
     *
     * Always blocks the site's own domain (from APP_URL) plus anything in
     * config('contact.banned_domains'), plus any exact address listed in
     * config('contact.blocked_emails'). Real visitors never email Tenant
     * Trust from @tenanttrust.ca — only spammers spoof it — so those
     * submissions are silently dropped before any processing.
     */
    public static function isBlocked(?string $email): bool
    {
        if ($email === null || !str_contains($email, '@')) {
            return false;
        }

        $email = strtolower(trim($email));

        if ($email === '') {
            return false;
        }

        // Exact-address blocks — for spammers reusing a mainstream domain
        // (gmail, outlook, …) that cannot be banned wholesale.
        foreach (config('contact.blocked_emails', []) as $blockedEmail) {
            $blockedEmail = strtolower(trim((string) $blockedEmail));
            if ($blockedEmail !== '' && $blockedEmail === $email) {
                return true;
            }
        }

        $domain = strtolower(trim((string) substr(strrchr($email, '@'), 1)));
        if ($domain === '') {
            return false;
        }

        $ownHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        $blocked = array_values(array_filter(array_merge(
            config('contact.banned_domains', []),
            $ownHost !== '' ? [$ownHost] : [],
        )));

        foreach ($blocked as $banned) {
            $banned = strtolower(trim((string) $banned, " \t\n\r\0\x0B."));
            if ($banned === '') {
                continue;
            }
            // Exact match, or any subdomain of the banned domain.
            if ($domain === $banned || str_ends_with($domain, '.' . $banned)) {
                return true;
            }
        }

        return false;
    }
}
