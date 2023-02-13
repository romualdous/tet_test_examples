<?php

return [
    /**
     * Overall settings used with administrators and dashboard
     */
    'settings' => [
        'passwords' => [
            /**
             * Minimum allowed length for passwords
             */
            'min_length' => 12,
        ],
        'cache' => [
            'prefixes' => [
                /**
                 * Prefix to define Redis record storing online user
                 */
                'user-online' => 'user-online-'
            ],
            'ttl' => [
                /**
                 * Time interval for online statuses (in seconds)
                 */
                'online-status' => 5 * 60
            ]
        ],
        'pagination' => [
            /**
             * Default per page record count
             */
            'default' => 15
        ]
    ],
];
