<?php

namespace MauticPlugin\MauticAiReportsBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use MauticPlugin\MauticAiReportsBundle\Form\Type\ConfigType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event): void
    {
        $event->addForm([
            'bundle'     => 'MauticAiReportsBundle',
            'formType'   => ConfigType::class,
            'formAlias'  => 'aireportsconfig',
            'formTheme'  => '@MauticAiReports/FormTheme/Config/_config_aireportsconfig_widget.html.twig',
            'parameters' => $event->getParametersFromConfig('MauticAiReportsBundle'),
        ]);
    }
}
