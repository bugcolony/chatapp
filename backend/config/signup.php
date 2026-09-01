<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Blocked Email Domains
    |--------------------------------------------------------------------------
    |
    | Accounts cannot be created with an address on one of these domains.
    | Matching is exact and case-insensitive against the part after the final
    | "@". Existing accounts are never affected, only new sign ups.
    |
    */

    'blocked_email_domains' => [
        'users.noreply.github.com',
    ],

];
