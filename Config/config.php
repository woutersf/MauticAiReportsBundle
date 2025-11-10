<?php

declare(strict_types=1);

return [
    'name'        => 'AI Reports',
    'description' => 'AI-powered report generation interface for Mautic',
    'version'     => '1.0.0',
    'author'      => 'Mautic Community',

    'routes' => [
        'main' => [
            'mautic_ai_reports_index' => [
                'path'       => '/ai/reports',
                'controller' => 'MauticPlugin\MauticAiReportsBundle\Controller\ReportsController::indexAction',
            ],
        ],
        'public' => [],
        'api'    => [],
    ],

    'services' => [
        'events' => [
            'mautic.ai_reports.button.subscriber' => [
                'class' => MauticPlugin\MauticAiReportsBundle\EventListener\ButtonSubscriber::class,
                'arguments' => [
                    'router',
                    'mautic.security',
                ],
            ],
            'mautic.ai_reports.asset.subscriber' => [
                'class' => MauticPlugin\MauticAiReportsBundle\EventListener\AssetSubscriber::class,
            ],
            'mautic.ai_reports.config.subscriber' => [
                'class' => MauticPlugin\MauticAiReportsBundle\EventListener\ConfigSubscriber::class,
            ],
        ],
        'forms' => [
            'mautic.ai_reports.form.type.config' => [
                'class' => MauticPlugin\MauticAiReportsBundle\Form\Type\ConfigType::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.ai_reports' => [
                'class' => MauticPlugin\MauticAiReportsBundle\Integration\AiReportsIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'monolog.logger.mautic',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
        ],
    ],

    'parameters' => [
        'ai_reports_enabled' => true,
        'ai_reports_model' => 'gpt-3.5-turbo',
        'ai_report_prompt' => 'INSTRUCTION:
------------
You are a AI assistant for analyzing user\'s questions. You answer with
- A list (sql query) formatted as a HTML table
- A list (sql query) formatted as a HTML table + in combination with a graph (made with js).
you take into account the Database structure (see STRUCTURE)
you try to answer the question of the user (see USER_QUESTION)
You only output the SQL query needed to find the relevant data. nothing else, no answers, no pleasantries, nothing else.

USER_QUESTION
------------
[actual_user_question]

STRUCTURE
---------
[database_structure]',
        'allow_graph_creation' => false,
    ],
];
