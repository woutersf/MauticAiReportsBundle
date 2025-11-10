<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAiReportsBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;

class AiReportsIntegration extends AbstractIntegration
{
    /**
     * Returns the name of the integration.
     */
    public function getName(): string
    {
        return 'AiReports';
    }

    /**
     * Returns the display name shown in the plugin list.
     */
    public function getDisplayName(): string
    {
        return 'AI Reports';
    }

    /**
     * Returns the description with configuration instructions.
     */
    public function getDescription(): string
    {
        return 'AI-powered report generation interface. Configuration for this plugin is available in Settings → Configuration (or /s/config/edit).';
    }

    /**
     * Returns the authentication type.
     * Using 'none' means no OAuth or API key authentication is required.
     */
    public function getAuthenticationType(): string
    {
        return 'none';
    }

    /**
     * Returns an array of key => label for configuration fields.
     * These fields will appear in the plugin configuration form.
     */
    public function getRequiredKeyFields(): array
    {
        return [];
    }

    /**
     * Get the path to the integration icon
     */
    public function getIcon(): string
    {
        return 'plugins/MauticAiReportsBundle/Assets/mauticai.png';
    }
}
