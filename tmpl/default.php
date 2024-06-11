<?php
/**
 * Shows HTML, registers javascript and passes parameters to javascript
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

// Load the core Joomla behaviors
JHtml::_('behavior.core');

// Load language file
$lang = JFactory::getLanguage();
$lang->load('mod_eventchart', __DIR__);

// Add JavaScript language strings
JText::script('MOD_EVENTCHART_X_LABEL');
JText::script('MOD_EVENTCHART_Y_LABEL');
JText::script('MOD_EVENTCHART_TOOLTIP_REGISTRATIONS');
JText::script('MOD_EVENTCHART_TOOLTIP_DAYS_BEFORE');

//add javascript
$wa = $app->getDocument()->getWebAssetManager();
$minified = true; //set to false when debugging
if ($minified) { $infix = '.min';} else {$infix = '';}
$scriptUrl = 'modules/mod_eventchart/js/mod_eventchart' . $infix . '.js?v=' . filemtime(JPATH_BASE . '/modules/mod_eventchart/js/mod_eventchart' . $infix . '.js');
$wa->registerAndUseScript('mod_eventchart' . $infix . 'script',$scriptUrl, [], ['defer' => true,'type' => 'module'], ['core']);

echo '<script>';
// export php data to javascript
echo 'var eventData = ' . json_encode($eventData) . ';';
echo 'var locationData = ' . json_encode($locationData) . ';';
echo 'var categoryData = ' . json_encode($categoryData) . ';';
echo 'var params = ' . json_encode($params->toArray()) . ';';
echo '</script>';

// Load the HTML file
require_once __DIR__ . '/default.html.php';
