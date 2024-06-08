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
    Display a chart of registrations
*/

// No direct access
defined('_JEXEC') or die;

//add javascript, does not work. Gives dependency error.
//$wa = $app->getDocument()->getWebAssetManager();
//$wa->registerScript('mod_eventchart.script', 'modules/mod_eventchart/js/mod_eventchart.js', [], [], ['type' => 'module']);
//$wa->useScript('mod_eventchart.script');

echo '<script>';
// export php data to javascript
echo 'var eventData = ' . json_encode($eventData) . ';';
echo 'var locationData = ' . json_encode($locationData) . ';';
echo 'var categoryData = ' . json_encode($categoryData) . ';';
echo 'var params = ' . json_encode($params->toArray()) . ';';
echo '</script>';

// Load the HTML file
require_once __DIR__ . '/default.html.php';

//add javascript old fashioned way
echo '<script type="module" src="' . JURI::base() . 'modules/mod_eventchart/js/mod_eventchart.js"></script>';

// Add CSS (if any)
// $wa->registerAndUseStyle('mod_example.style', 'media/mod_example/css/style.css');

// TODO
// Multi-lingual
// js file with registerScript ??
// Minify js file
// checksum of zip file
