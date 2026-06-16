<?php

return [
    /*
     * The queue connection and name to be used for dispatching announcement jobs.
     */
    'queue' => env('TBE_ANNOUNCEMENTS_QUEUE', 'announcements'),

    /*
     * The number of users to process in a single job batch.
     */
    'batch_size' => (int) env('TBE_ANNOUNCEMENTS_BATCH_SIZE', 100),
];
