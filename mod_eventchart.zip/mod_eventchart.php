<?php

// Copyright Frans Stuurman 2024, MIT licence

/* task of this file
    Main driver of Event Chart module
*/

// No direct access
defined('_JEXEC') or die;
// Include the syndicate functions only once
require_once dirname(__FILE__) . '/helper.php';

$eventData = modEventChartHelper::getEventData($params);
require JModuleHelper::getLayoutPath('mod_eventchart');