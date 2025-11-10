<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAiReportsBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomButtonEvent;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Twig\Helper\ButtonHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;

class ButtonSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RouterInterface $router,
        private CorePermissions $security
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_BUTTONS => ['injectAiReportsButton', 0],
        ];
    }

    public function injectAiReportsButton(CustomButtonEvent $event): void
    {
        // Only add button on the reports index page
        if (!str_contains($event->getRoute(), 'mautic_report_index')) {
            return;
        }

        // Only show to users who can view reports
        if (!$this->security->isGranted(['report:reports:viewown', 'report:reports:viewother'], 'MATCH_ONE')) {
            return;
        }

        // Only add to page actions (toolbar buttons, next to "New")
        if (ButtonHelper::LOCATION_PAGE_ACTIONS !== $event->getLocation()) {
            return;
        }

        $aiReportsRoute = $this->router->generate('mautic_ai_reports_index');

        $event->addButton([
            'attr' => [
                'href' => $aiReportsRoute,
                'class' => 'btn btn-primary',
            ],
            'btnText' => 'Report with AI ✨',
            'iconClass' => 'ri-sparkling-line',
            'priority' => 255,
        ]);
    }
}
