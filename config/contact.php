<?php

return [
    /*
    | Domains that may never submit the public website forms.
    |
    | The site's own domain (from APP_URL) is ALWAYS blocked automatically —
    | real visitors never email Tenant Trust from @tenanttrust.ca, so any
    | submission claiming that domain is spoofed/spam and gets silently
    | dropped. Add extra spam domains here if you ever need to.
    */
    'banned_domains' => [
        // Tenant Trust's own domains — real visitors/customers never email
        // us from these; only spammers spoof them. (Also auto-blocked via
        // APP_URL, listed explicitly for belt-and-suspenders.)
        'tenanttrust.ca',
        'tenantrust.ca',
    ],

    /*
    | Individual addresses that may never submit the public website forms.
    |
    | Use this when a spammer keeps using a legitimate domain (gmail, outlook,
    | etc.) that you cannot ban wholesale. Matched case-insensitively against
    | the full address on /contact and /apply.
    */
    'blocked_emails' => [
        'jellybeanmhurtado559@gmail.com', // blocked 2026-10-22 (Steve)
    ],
];
