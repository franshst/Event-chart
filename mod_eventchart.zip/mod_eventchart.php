<?php

// Copyright Frans Stuurman 2024, MIT licence

/* task of this file
    Main driver of Event Chart module
*/

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
use EventChartNamespace\Module\EventChart\Site\Helper\EventChartHelper;

$eventData = EventChartHelper::getEventData();
$locationData = EventChartHelper::getLocationData();
$categoryData = EventChartHelper::getCategoryData();

require ModuleHelper::getLayoutPath('mod_eventchart', $params->get('layout', 'default'));
