<?php

return [
    // Download milestones
    'downloads' => [
        ['id' => 'dl_100',  'threshold' => 100,  'title' => '100 Downloads',   'desc' => 'First hundred!',        'icon' => 'bi-trophy'],
        ['id' => 'dl_1000', 'threshold' => 1000, 'title' => '1,000 Downloads', 'desc' => 'You’re rolling.',       'icon' => 'bi-trophy'],
        ['id' => 'dl_2000', 'threshold' => 2000, 'title' => '2,000 Downloads', 'desc' => 'Nice milestone.',       'icon' => 'bi-trophy'],
        ['id' => 'dl_5000', 'threshold' => 5000, 'title' => '5,000 Downloads', 'desc' => 'Half to 10k.',          'icon' => 'bi-trophy'],
        ['id' => 'dl_10000','threshold' => 10000,'title' => '10,000 Downloads','desc' => 'Double digits. 🔥',     'icon' => 'bi-trophy'],
    ],

    // Episode count milestones
    'episodes' => [
        ['id' => 'ep_1',  'threshold' => 1,  'title' => 'First Episode', 'desc' => 'You shipped the first one!', 'icon' => 'bi-mic'],
        ['id' => 'ep_5',  'threshold' => 5,  'title' => '5 Episodes',    'desc' => 'Consistency pays off.',      'icon' => 'bi-award'],
        ['id' => 'ep_10', 'threshold' => 10, 'title' => '10 Episodes',   'desc' => 'Keep the momentum.',         'icon' => 'bi-award'],
        ['id' => 'ep_25', 'threshold' => 25, 'title' => '25 Episodes',   'desc' => 'Quarter century club.',      'icon' => 'bi-award'],
    ],
];
