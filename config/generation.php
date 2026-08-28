<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Upload limits
    |--------------------------------------------------------------------------
    |
    | Documents above the page limit are rejected after analysis, since the page
    | count is not known at upload time. The size limit is in kilobytes, matching
    | Laravel's validation rules.
    |
    */

    'max_pages' => 25,
    'max_upload_kilobytes' => 20480,

    'accepted_media_types' => [
        'application/pdf',
        'text/plain',
        'text/markdown',
    ],

    'accepted_extensions' => ['pdf', 'txt', 'md'],

    /*
    |--------------------------------------------------------------------------
    | Generation shape
    |--------------------------------------------------------------------------
    |
    | How many candidate outlines a source is analysed into, and how many
    | questions each outline section is expanded to by default.
    |
    */

    'proposal_count' => 3,
    'questions_per_section' => 8,

];
