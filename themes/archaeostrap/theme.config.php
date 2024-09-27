<?php
return [
    'extends' => 'bootstrap3',
    'css' => [
    	'idai-components.min.css',
    	'custom.css',
    ],
    'js' => [
        ['file' => 'vendor/bootstrap-slider.min.js', 'priority' => 140]
    ],
    'favicon' => 'favicon.ico',
    'helpers' => [
        'factories' => [
            'Zenon\View\Helper\Root\Citation' => 'VuFind\View\Helper\Root\CitationFactory',
            'Zenon\View\Helper\Root\DateTime' => 'VuFind\View\Helper\Root\DateTimeFactory',
            'Zenon\View\Helper\Root\Record' => 'VuFind\View\Helper\Root\RecordFactory',
            'Zenon\View\Helper\Root\RecordLinker' => 'Zenon\View\Helper\Root\RecordLinkerFactory',
            'Zenon\View\Helper\Root\ResultFeed' => 'VuFind\View\Helper\Root\ResultFeedFactory',
        ],
        'aliases' => [
            'citation' => 'Zenon\View\Helper\Root\Citation',
            'dateTime' => 'Zenon\View\Helper\Root\DateTime',
            'record' => 'Zenon\View\Helper\Root\Record',
            'recordLinker' => 'Zenon\View\Helper\Root\RecordLinker',
            'resultfeed' => 'Zenon\View\Helper\Root\ResultFeed',
        ],
    ]
];
