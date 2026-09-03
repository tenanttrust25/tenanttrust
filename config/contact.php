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
];
