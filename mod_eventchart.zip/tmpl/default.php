<?php
// Copyright Frans Stuurman 2024, MIT licence

/* task of this file
    Display data
*/

// No direct access
defined('_JEXEC') or die;

echo '<script>';
// all dates are unix timestamp in seconds
echo 'var chartData = ' . json_encode($eventData) . ';';
// default filter items, from future module options 
// echo 'var past = 365;'
// echo 'var range = 30;'
// echo 'var titleFilter = \'Mezrab\';'
// echo 'var category = [\'Bal\'];'


echo '</script>';
?>
