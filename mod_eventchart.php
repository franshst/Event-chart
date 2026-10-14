<?php
/**
 * Main driver of Event Chart module
 *
 * @package    Event Chart
 *
 * @author     Frans Stuurman
 * @copyright  Frans Stuurman
 * @license    MIT see LICENSE
 *
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use EventChartNamespace\Module\EventChart\Site\Helper\EventChartHelper;

Factory::getApplication()->getLanguage()->load('mod_eventchart', JPATH_SITE);
$eventData = EventChartHelper::getEventData();
$locationData = EventChartHelper::getLocationData();
$categoryData = EventChartHelper::getCategoryData();

require ModuleHelper::getLayoutPath('mod_eventchart', $params->get('layout', 'default'));
