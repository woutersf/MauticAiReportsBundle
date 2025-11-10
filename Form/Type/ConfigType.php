<?php

namespace MauticPlugin\MauticAiReportsBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpClient\HttpClient;

class ConfigType extends AbstractType
{
    public function __construct(
        private CoreParametersHelper $coreParametersHelper
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Fetch available models from LLM endpoint
        $modelChoices = $this->getModelChoices($options['data'] ?? []);

        $builder->add(
            'ai_reports_model',
            ChoiceType::class,
            [
                'choices'     => $modelChoices,
                'label'       => 'AI Model',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => [
                    'class'   => 'form-control',
                    'tooltip' => 'Select the AI model to use for generating reports',
                ],
                'required'    => false,
                'placeholder' => 'Select an AI model',
            ]
        );

        $builder->add(
            'ai_report_prompt',
            TextareaType::class,
            [
                'label'      => 'AI Report Prompt',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'tooltip'     => 'System instructions for the AI when generating reports',
                    'rows'        => 6,
                    'placeholder' => 'Enter system instructions for AI report generation...',
                ],
                'required'   => false,
                'data'       => $options['data']['ai_report_prompt'] ?? 'INSTRUCTION:
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
                'help'       => 'This prompt will be used to instruct the AI how to analyze data and generate reports.',
            ]
        );

        $builder->add(
            'allow_graph_creation',
            YesNoButtonGroupType::class,
            [
                'label'      => 'Allow Graph Creation',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'tooltip' => 'Enable the AI to create visual graphs and charts from the data',
                ],
                'data'       => $options['data']['allow_graph_creation'] ?? false,
                'required'   => false,
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'aireportsconfig';
    }

    private function getModelChoices(array $formData): array
    {
        // Default model choices as fallback
        $defaultChoices = [
            'GPT-4' => 'gpt-4',
            'GPT-3.5 Turbo' => 'gpt-3.5-turbo',
            'Claude 3 Haiku' => 'claude-3-haiku-20240307',
            'Claude 3 Sonnet' => 'claude-3-sonnet-20240229',
            'Claude 3 Opus' => 'claude-3-opus-20240229',
            'Llama 2 70B' => 'llama-2-70b-chat',
        ];

        try {
            // Get LiteLLM configuration from AI Console settings
            $endpoint = $this->coreParametersHelper->get('litellm_endpoint');
            $secretKey = $this->coreParametersHelper->get('litellm_secret_key');

            // If no endpoint configured, return default choices
            if (empty($endpoint)) {
                return $defaultChoices;
            }

            // Attempt to fetch models from the endpoint
            $modelsEndpoint = rtrim($endpoint, '/') . '/models';
            $httpClient = HttpClient::create();

            $headers = [
                'Accept' => 'application/json',
            ];

            // Add bearer token if secret key is provided
            if (!empty($secretKey)) {
                $headers['Authorization'] = 'Bearer ' . $secretKey;
            }

            $response = $httpClient->request('GET', $modelsEndpoint, [
                'headers' => $headers,
                'timeout' => 10,
            ]);

            if (200 === $response->getStatusCode()) {
                $data = $response->toArray();

                if (isset($data['data']) && is_array($data['data'])) {
                    $choices = [];
                    foreach ($data['data'] as $model) {
                        if (isset($model['id'])) {
                            $modelId = $model['id'];
                            $modelName = $model['name'] ?? $modelId;
                            $choices[$modelName] = $modelId;
                        }
                    }

                    if (!empty($choices)) {
                        return $choices;
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail and use default choices
        }

        return $defaultChoices;
    }
}
