<?php
/**
 * Defines the HTML to display the chart
 *
 * @package    Event Chart
 *
 * @author     Frans Stuurman
 * @copyright  Frans Stuurman
 * @license    MIT see LICENSE
 *
 */

defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;

?>

<style>
    .fsec-field-format {
        width: 10rem !important;
        font-size: 0.75rem;
        padding-right: 0.25rem !important;
        padding-left: 0.25rem;
    }
    .fsec-gutter {
        padding-right: 0.25rem !important;
        padding-left: 0.25rem;
    }
    .fsec-chart-container {
        position: relative;
        width: 100%;
        padding-top: 100%;
    }

    .fsec-chart {
        position: relative;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
</style>

<div id="fsECFilters" class="container">
    <div class="form-group row">
        <div class="col-auto fsec-gutter">
            <label for="fsECloc" class="form-label fsec-field-format"><?php echo text::_('MOD_EVENTCHART_FILTER_TITLE') ?></label>
            <input type="text" id="fsECtitle" class="form-control form-control-sm fsec-field-format" oninput="changeTitle(this.value)"></input>
        </div>
        <div class="col-auto fsec-gutter">
            <label for="fsECloc" class="form-label fsec-field-format"><?php echo text::_('MOD_EVENTCHART_FILTER_LOCATION') ?></label>
            <select id="fsECloc" class="form-select form-select-sm -2 fsec-field-format" oninput="changeLocation(this.value)"></select>
        </div>
        <div class="col-auto fsec-gutter">
            <label for="fsECcat" class="form-label fsec-field-format"><?php echo text::_('MOD_EVENTCHART_FILTER_CATEGORY') ?></label>
            <select id="fsECcat" class="form-select form-select-sm fsec-field-format" oninput="changeCategory(this.value)"></select>
        </div>
        <div class="col-auto fsec-gutter">
            <label for="fsECpast" class="form-label fsec-field-format"><?php echo text::_('MOD_EVENTCHART_FILTER_PAST') ?></label>
            <input id="fsECpast" type="number" min="0" class="form-control form-control-sm fsec-field-format" oninput="changePast(this.value)"></input>
        </div>
        <div class="col-auto fsec-gutter">
            <label for="fsECrange" class="form-label fsec-field-format"><?php echo text::_('MOD_EVENTCHART_FILTER_RANGE') ?></label>
            <input id="fsECrange" type="number" min="0" class="form-control form-control-sm fsec-field-format" oninput="changeRange(this.value)"></input>
        </div>
    </div>
</div>
<div class="fsec-chart">
    <canvas id="fsECchart" class="fsec-chart"></canvas>
</div>
