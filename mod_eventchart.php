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

JFactory::getLanguage()->load('mod_eventchart', JPATH_SITE);
$eventData = EventChartHelper::getEventData();
$locationData = EventChartHelper::getLocationData();
$categoryData = EventChartHelper::getCategoryData();
$categoryData2 = EventChartHelper::getCategoryData2();
$categoryDropDowndata = EventChartHelper::getEventCategoriesDropdownData();

//echo ($params->get('range','dumdumdum') . '<br/><br/>');
//echo 'All params: <pre>' . print_r($params->toArray(), true) . '</pre>';

require ModuleHelper::getLayoutPath('mod_eventchart', $params->get('layout', 'default'));
