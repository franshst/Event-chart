<?php
// Copyright Frans Stuurman 2024, MIT licence

/* task of this file
    include the helper.php file which contains the class to be used to collect the necessary data
    invoke the appropriate helper class method to retrieve the data
    include the template to display the output
*/

// No direct access
defined('_JEXEC') or die;
// Include the syndicate functions only once
require_once dirname(__FILE__) . '/helper.php';

$eventData = ModEventChartHelper::getEventData();
require JModuleHelper::getLayoutPath('mod_eventchart');