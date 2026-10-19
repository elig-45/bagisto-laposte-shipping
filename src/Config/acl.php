<?php

return [
    [
        'key' => 'sales.laposte',
        'name' => 'laposte::app.admin_section',
        'route' => ['admin.laposte.index', 'admin.laposte.preview', 'admin.laposte.rates.export'],
        'sort' => 20,
    ],
    [
        'key' => 'sales.laposte.manage',
        'name' => 'laposte::app.manage',
        'route' => [
            'admin.laposte.rates.store', 'admin.laposte.rates.update', 'admin.laposte.rates.destroy',
            'admin.laposte.rates.import', 'admin.laposte.profiles.store', 'admin.laposte.profiles.destroy',
        ],
        'sort' => 1,
    ],
];
