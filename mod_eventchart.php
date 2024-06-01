<?php

/**
 * @package    Event Chart
 *
 * @author     Frans Stuurman
 * @copyright  Frans Stuurman
 * @license    MIT see LICENSE
 * @link
 */

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
