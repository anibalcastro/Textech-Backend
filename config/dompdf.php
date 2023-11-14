<?php

return [
    'fontDir' => storage_path('fonts/'),
    'fontCache' => storage_path('fonts/'),
    'imgDir' => storage_path('images/'),

    'autoLoad' => true,
    'setOptions' => [
        'isHtml5ParserEnabled' => true,
        'isPhpEnabled' => true,
        'isPhp7' => true,
        'isHtml4' => false,
    ],

    'defaultMediaType' => 'screen',
    'defaultPaperSize' => 'letter',
    'defaultFont' => 'Arial',

    'dpi' => 96,
    'isPhpEnabled' => false,
    'isHtml5ParserEnabled' => false,
    'isPhp7' => false,
    'fontHeightRatio' => 1.0,

    'maintenance' => false,
];
