<?php
namespace Zenon\Module\Config;

$config = [
    'controllers' => [
        'factories' => [
            'Zenon\Controller\CartController' => 'VuFind\Controller\CartControllerFactory',
            'Zenon\Controller\RecordsController' => 'VuFind\Controller\AbstractBaseFactory',
            'Zenon\Controller\GeneralAuthoritySearchController' => 'VuFind\Controller\AbstractBaseFactory',
            'Zenon\Controller\GazetteerSearchController' => 'VuFind\Controller\AbstractBaseFactory',
            'Zenon\Controller\ORCSearchController' => 'VuFind\Controller\AbstractBaseFactory',
            'Zenon\Controller\AuthoritySearchController' =>  'VuFind\Controller\AbstractBaseFactory',
            'Zenon\Controller\ThesauriSearchController' => 'VuFind\Controller\AbstractBaseFactory',
            'Zenon\Controller\ThesaurusController' =>  'Zenon\Controller\ThesaurusControllerFactory'
        ],
        'aliases' => [
            'Cart' => 'Zenon\Controller\CartController',
            'cart' => 'Zenon\Controller\CartController',
            'Records' => 'Zenon\Controller\RecordsController',
            'records' => 'Zenon\Controller\RecordsController',
            'Authorities' => 'Zenon\Controller\AuthoritySearchController',
            'authorities' => 'Zenon\Controller\AuthoritySearchController',
            'Gazetteer' => 'Zenon\Controller\GazetteerSearchController',
            'gazetteer' => 'Zenon\Controller\GazetteerSearchController',
            'orc' => 'Zenon\Controller\ORCSearchController',
            'Thesauri' => 'Zenon\Controller\ThesauriSearchController',
            'thesauri' => 'Zenon\Controller\ThesauriSearchController',
            'thesaurus' => 'Zenon\Controller\ThesaurusController',
            'Thesaurus' => 'Zenon\Controller\ThesaurusController',
        ]
    ],
    '_service_manager' => [
        'allow_override' => true,
        'factories' => [
            'Zenon\RecordDriver\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'Zenon\Recommend\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory'
            ],
        'aliases' => [
            'Zenon\RecordDriverPluginManager' => 'Zenon\RecordDriver\PluginManager',
            'VuFind\RecommendPluginManager' => 'VuFind\Recommend\PluginManager'
            ]
        ],

    'vufind' => [
        'plugin_managers' => [
            'recorddriver' => [
                'factories' => [
                    'Zenon\RecordDriver\SolrMarc' => 'Zenon\RecordDriver\SolrDefaultFactory',
                    'Zenon\RecordDriver\SolrAuthMarc' => 'VuFind\RecordDriver\SolrDefaultWithoutSearchServiceFactory',
                ],
                'delegators' => [
                    'Zenon\RecordDriver\SolrMarc' => ['VuFind\RecordDriver\IlsAwareDelegatorFactory'],
                ],
                'aliases' => [
                    'solrmarc' => 'Zenon\RecordDriver\SolrMarc',
                    'solrauthmarc' => 'Zenon\RecordDriver\SolrAuthMarc',
                    'solrauth' => 'Zenon\RecordDriver\SolrAuthMarc', // legacy name
                ]
            ],
            'recordtab' => [
                'factories' => [
                    'Zenon\RecordTab\Access' => 'Zenon\RecordTab\AccessFactory',
                ],
                'aliases' => [
                    'Access' => 'Zenon\RecordTab\Access',
                ]
            ],
            'recommend' => [ 
                'factories' => [
                    'Zenon\Recommend\AuthorityRecommend' => 'Zenon\Recommend\Factory::getAuthorityRecommend'
                ],
                'aliases' => [
                    "authorityrecommend" => 'Zenon\Recommend\AuthorityRecommend'
                ]
                /* See VuFind\Recommend\PluginManager for defaults */ ],
        ],

        // This section controls which tabs are used for which record driver classes.
        // Each sub-array is a map from a tab name (as used in a record URL) to a tab
        // service (found in recordtab_plugin_manager, below).  If a particular record
        // driver is not defined here, it will inherit configuration from a configured
        // parent class.  The defaultTab setting may be used to specify the default
        // active tab; if null, the value from the relevant .ini file will be used.
        '_recorddriver_tabs' => [
            'Zenon\RecordDriver\SolrMarc' => [
                'tabs' => [
                    'Holdings' => 'HoldingsILS', 'Description' => 'Description',
                    'TOC' => 'TOC', 'UserComments' => 'UserComments',
                    'Reviews' => 'Reviews', 'Excerpt' => 'Excerpt',
                    'Preview' => 'preview',
                    'HierarchyTree' => 'HierarchyTree', 'Map' => 'Map',
                    'Similar' => null,
                    'Details' => 'StaffViewMARC',
                    'Access' => 'Access',
                ],
                'defaultTab' => null,
            ],
        ],
    ],

    // Define static routes -- Controller/Action strings
    $staticRoutes = [
        'Records/Cite'
    ]
];

$routeGenerator = new \VuFind\Route\RouteGenerator();
$routeGenerator->addStaticRoutes($config, $staticRoutes);

return $config;
