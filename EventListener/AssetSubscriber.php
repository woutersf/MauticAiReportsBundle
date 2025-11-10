<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAiReportsBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomAssetsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AssetSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_ASSETS => ['injectAssets', 0],
        ];
    }

    public function injectAssets(CustomAssetsEvent $event): void
    {
        // Add CSS to head
        $event->addStylesheet('plugins/MauticAiReportsBundle/Assets/css/ai-reports.css');

        // Add JavaScript to body (before closing body tag)
        $event->addScript('plugins/MauticAiReportsBundle/Assets/js/ai-reports.js', 'bodyClose');
    }
}
