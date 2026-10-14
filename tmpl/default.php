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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$app = Factory::getApplication();
$wa = $app->getDocument()->getWebAssetManager();

// Load the core Joomla behaviors
$wa->useScript('core');

// Load language file
$app->getLanguage()->load('mod_eventchart', __DIR__);

// Add JavaScript language strings
Text::script('MOD_EVENTCHART_X_LABEL');
Text::script('MOD_EVENTCHART_Y_LABEL');
Text::script('MOD_EVENTCHART_TOOLTIP_REGISTRATIONS');
Text::script('MOD_EVENTCHART_TOOLTIP_DAYS_BEFORE');

//add javascript (use '' instead of '.min' when debugging)
$scriptUrl = 'modules/mod_eventchart/js/mod_eventchart.min.js?v=' . filemtime(JPATH_BASE . '/modules/mod_eventchart/js/mod_eventchart.min.js');
$wa->registerAndUseScript('mod_eventchartscript', $scriptUrl, [], ['defer' => true,'type' => 'module'], ['core']);

echo '<script>';
// export php data to javascript
$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
echo 'var eventData = ' . json_encode($eventData, $jsonFlags) . ';';
echo 'var locationData = ' . json_encode($locationData, $jsonFlags) . ';';
echo 'var categoryData = ' . json_encode($categoryData, $jsonFlags) . ';';
echo 'var params = ' . json_encode(array_intersect_key($params->toArray(), array_flip(['title', 'location', 'category', 'range', 'past'])), $jsonFlags) . ';';
echo '</script>';

// Load the HTML file
require_once __DIR__ . '/default.html.php';
