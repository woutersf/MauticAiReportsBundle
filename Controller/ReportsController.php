<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAiReportsBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\MauticAiReportsBundle\Entity\AiReportsLog;
use MauticPlugin\MauticAIconnectionBundle\Service\LiteLLMService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportsController extends CommonController
{
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'mautic.ai_connection.service.litellm' => LiteLLMService::class,
        ]);
    }
    /**
     * Display the AI Reports form and handle submission.
     */
    public function indexAction(Request $request): Response
    {
        // Check if user has access to reports
        if (!$this->security->isGranted(['report:reports:viewown', 'report:reports:viewother'], 'MATCH_ONE')) {
            return $this->accessDenied();
        }

        $results = null;
        $query = '';
        $sqlQuery = null;
        $queryResults = null;
        $error = null;

        // Handle form submission
        if ($request->isMethod('POST')) {
            $query = $request->request->get('query', '');

            if (!empty($query)) {
                try {
                    // Get database schema information
                    $databaseSchema = $this->getDatabaseSchema();

                    // Get AI model from settings
                    $coreParametersHelper = $this->factory->getHelper('core_parameters');
                    $aiModel = $coreParametersHelper->get('ai_reports_model', 'gpt-3.5-turbo');

                    // Generate SQL query using AI (returns array with query and prompt)
                    $aiResponse = $this->generateSqlQuery($query, $databaseSchema);


                    // Always show the generated query, even if execution fails
                    $results = [
                        'sql_query' => $aiResponse['sql_query'],
                        'prompt' => $aiResponse['prompt'],
                        'data' => null,
                    ];


                    if ($aiResponse['sql_query']) {
                        try {
                            // Execute the SQL query
                            $queryResults = $this->executeSqlQuery($aiResponse['sql_query']);
                            $results['data'] = $queryResults;
                        } catch (\Exception $e) {
                            // Show execution error but keep the generated query visible
                            $error = $e->getMessage();
                        }
                    }

                    // Log the AI report submission
                    $this->logAiReport($aiResponse['prompt'], $aiModel, $aiResponse['sql_query']);

                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'page'    => 'ai_reports',
                'query'   => $query,
                'results' => $results,
                'error'   => $error,
            ],
            'contentTemplate' => '@MauticAiReports/Reports/index.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_report_index',
                'mauticContent' => 'aiReports',
                'route'         => $this->generateUrl('mautic_ai_reports_index'),
            ],
        ]);
    }

    /**
     * Get database schema with tables and columns.
     */
    private function getDatabaseSchema(): string
    {
        $schemaText = '';

        try {
            // Get entity manager from container
            $em = $this->getDoctrine()->getManager();
            $connection = $em->getConnection();
            $schemaManager = $connection->createSchemaManager();

            // Get all tables
            $tables = $schemaManager->listTables();

            foreach ($tables as $table) {
                $tableName = $table->getName();
                $tableDescription = $this->getTableDescription($tableName);

                $schemaText .= "\nTable: {$tableName}\n";
                $schemaText .= "Description: {$tableDescription}\n";
                $schemaText .= "Columns:\n";

                foreach ($table->getColumns() as $column) {
                    $columnName = $column->getName();
                    $columnType = $column->getType()->getName();
                    $columnDesc = $this->getColumnDescription($tableName, $columnName);
                    $schemaText .= "  - {$tableName}.{$columnName} ({$columnType})\n";//: {$columnDesc}
                }
            }
        } catch (\Exception $e) {
            $schemaText = 'Failed to retrieve database schema: ' . $e->getMessage();
        }

        return $schemaText;
    }

    /**
     * Get a human-readable description for a table.
     */
    private function getTableDescription(string $tableName): string
    {
        $descriptions = [
            'leads'                    => 'Contact/Lead records with personal information',
            'companies'                => 'Company records linked to contacts',
            'emails'                   => 'Email templates and configurations',
            'email_stats'              => 'Email statistics (opens, clicks, sends)',
            'campaigns'                => 'Marketing campaigns and workflow configurations',
            'campaign_leads'           => 'Contact participation in campaigns',
            'campaign_lead_event_log'  => 'Log of campaign events triggered for contacts',
            'forms'                    => 'Form definitions and configurations',
            'form_submissions'         => 'Submitted form data from contacts',
            'pages'                    => 'Landing page definitions',
            'page_hits'                => 'Landing page visit tracking',
            'assets'                   => 'Downloadable assets/files',
            'asset_downloads'          => 'Asset download tracking',
            'categories'               => 'Category classifications for content',
            'stages'                   => 'Lead lifecycle stages',
            'points'                   => 'Point/score actions and triggers',
            'lead_points_change_log'   => 'History of contact point changes',
            'segments'                 => 'Dynamic contact segments/lists',
            'lead_lists_leads'         => 'Contact membership in segments',
            'users'                    => 'Mautic user accounts',
            'reports'                  => 'Custom report definitions',
            'webhooks'                 => 'Webhook configurations',
            'focus'                    => 'Focus items (popups, bars)',
            'messages'                 => 'Marketing messages',
            'dynamic_content'          => 'Dynamic web content rules',
            'sms_messages'             => 'SMS message templates',
            'sms_message_stats'        => 'SMS delivery statistics',
            'notifications'            => 'Mobile push notifications',
            'push_notifications'       => 'Browser push notification templates',
        ];

        // Try to match full table name or without prefix
        foreach ($descriptions as $key => $description) {
            if (str_contains($tableName, $key)) {
                return $description;
            }
        }

        return 'Database table for ' . str_replace('_', ' ', $tableName);
    }

    /**
     * Get a description for common column names.
     */
    private function getColumnDescription(string $tableName, string $columnName): string
    {
        $commonColumns = [
            'id'              => 'Unique identifier',
            'name'            => 'Name/title',
            'email'           => 'Email address',
            'firstname'       => 'First name',
            'lastname'        => 'Last name',
            'company'         => 'Company name',
            'phone'           => 'Phone number',
            'mobile'          => 'Mobile number',
            'address1'        => 'Street address line 1',
            'address2'        => 'Street address line 2',
            'city'            => 'City',
            'state'           => 'State/Province',
            'zipcode'         => 'Postal/ZIP code',
            'country'         => 'Country',
            'points'          => 'Lead score points',
            'date_added'      => 'Creation date/time',
            'date_modified'   => 'Last modification date/time',
            'created_by'      => 'User who created the record',
            'modified_by'     => 'User who last modified',
            'is_published'    => 'Published status flag',
            'publish_up'      => 'Publish start date',
            'publish_down'    => 'Publish end date',
            'subject'         => 'Email/message subject',
            'content'         => 'Main content/body',
            'description'     => 'Description text',
            'category_id'     => 'Associated category',
            'date_sent'       => 'Send date/time',
            'date_read'       => 'Read/open date/time',
            'is_read'         => 'Read status flag',
            'date_clicked'    => 'Click date/time',
            'lead_id'         => 'Associated contact/lead ID',
            'email_id'        => 'Associated email ID',
            'campaign_id'     => 'Associated campaign ID',
            'form_id'         => 'Associated form ID',
        ];

        if (isset($commonColumns[$columnName])) {
            return $commonColumns[$columnName];
        }

        // Try partial matches
        foreach ($commonColumns as $key => $description) {
            if (str_contains($columnName, $key)) {
                return $description;
            }
        }

        return ucfirst(str_replace('_', ' ', $columnName));
    }

    /**
     * Generate SQL query using AI based on user question and database schema.
     * Returns array with 'sql_query' and 'prompt'.
     */
    private function generateSqlQuery(string $userQuestion, string $databaseSchema): array
    {
        // Get LiteLLM service
        $liteLLMService = $this->container->get('mautic.ai_connection.service.litellm');

        // Get AI configuration from parameters
        $coreParametersHelper = $this->factory->getHelper('core_parameters');
        $aiModel = $coreParametersHelper->get('ai_reports_model', 'gpt-3.5-turbo');
        $promptTemplate = $coreParametersHelper->get('ai_report_prompt', '');
        $allowGraphCreation = $coreParametersHelper->get('allow_graph_creation', false);

        // Modify prompt based on graph creation setting
        if (!$allowGraphCreation) {
            // Remove graph-related instructions from the prompt
            $promptTemplate = preg_replace(
                '/- A list \(sql query\) formatted as a HTML table \+ in combination with a graph \(made with js\)\./i',
                '',
                $promptTemplate
            );
            // Add explicit instruction to not create graphs
            $promptTemplate .= "\n\nIMPORTANT: Graph creation is DISABLED. Only output the SQL query. Do NOT include any JavaScript, HTML for graphs, or graph-related code.";
        }

        // Replace tokens in prompt
        $prompt = str_replace(
            ['[actual_user_question]', '[database_structure]'],
            [$userQuestion, $databaseSchema],
            $promptTemplate
        );

        // Call LiteLLM service
        try {
            $messages = [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ];

            $options = [
                'model' => $aiModel,
                'temperature' => 0.3,
                'max_tokens' => 2000,
            ];

            $responseData = $liteLLMService->getChatCompletion($messages, $options);

            if (!isset($responseData['choices'][0]['message']['content'])) {
                throw new \Exception('Invalid response from AI service');
            }

            $sqlQuery = trim($responseData['choices'][0]['message']['content']);

            // Clean up the SQL query (remove markdown code blocks and XML tags if present)
            $sqlQuery = preg_replace('/^```sql\s*/i', '', $sqlQuery);
            $sqlQuery = preg_replace('/\s*```$/', '', $sqlQuery);
            $sqlQuery = preg_replace('/<sql>\s*/i', '', $sqlQuery);
            $sqlQuery = preg_replace('/\s*<\/sql>/i', '', $sqlQuery);
            $sqlQuery = trim($sqlQuery);

            return [
                'sql_query' => $sqlQuery,
                'prompt' => $prompt,
            ];

        } catch (\Exception $e) {
            throw new \Exception('Failed to generate SQL query: ' . $e->getMessage());
        }
    }

    /**
     * Execute SQL query in the database.
     */
    private function executeSqlQuery(string $sqlQuery): array
    {
        try {
            // Get entity manager from container
            $em = $this->getDoctrine()->getManager();
            $connection = $em->getConnection();

            // Only allow SELECT queries for security
            $trimmedQuery = trim(strtoupper($sqlQuery));
            if (!str_starts_with($trimmedQuery, 'SELECT')) {
                throw new \Exception('Only SELECT queries are allowed for security reasons');
            }

            // Execute the query
            $statement = $connection->prepare($sqlQuery);
            $result = $statement->executeQuery();

            // Fetch all results
            $rows = $result->fetchAllAssociative();

            return [
                'rows' => $rows,
                'count' => count($rows),
            ];

        } catch (\Exception $e) {
            throw new \Exception('Failed to execute SQL query: ' . $e->getMessage());
        }
    }

    /**
     * Log AI report submission to database.
     */
    private function logAiReport(string $prompt, string $model, string $output): void
    {
        try {
            $em = $this->getDoctrine()->getManager();

            // Get current user
            $user = $this->getUser();
            $userId = $user ? $user->getId() : null;

            // Create log entry
            $log = new AiReportsLog();
            $log->setUserId($userId);
            $log->setTimestamp(new \DateTime());
            $log->setPrompt($prompt);
            $log->setModel($model);
            $log->setOutput($output);

            // Persist and flush
            $em->persist($log);
            $em->flush();

        } catch (\Exception $e) {
            // Log error but don't throw exception to avoid breaking the user experience
            error_log('Failed to log AI report: ' . $e->getMessage());
        }
    }
}
