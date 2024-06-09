
<?php
// mod_example/tmpl/default.html.php
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;

?>

<div id="fsECFilters" class="container form-control-sm">
    <div class="form-group form-row">
        <div class="col-auto">
            <input type="text" id="fsECtitle" placeholder="<?php echo text::_('MOD_EVENTCHART_FILTER_TITLE') ?>" class="form-control form-control-sm col-sm-2" oninput="changeTitle(this.value)" style="width: 15em !important; min-width: 15em; max-width: 15em;"></input>
        </div>
    </div>
    <div class="form-group row">
        <div class="col-auto">
            <label for="fsECloc" class="form-label"><?php echo text::_('MOD_EVENTCHART_FILTER_LOCATION') ?></label>
            <select id="fsECloc" class="form-select form-select-sm col-sm-2" oninput="changeLocation(this.value)" style="width: 15em !important; min-width: 15em; max-width: 15em;"></select>
        </div>
        <div class="col-auto">
            <label for="fsECcat" class="form-label"><?php echo text::_('MOD_EVENTCHART_FILTER_CATEGORY') ?></label>
            <select id="fsECcat" class="form-select form-select-sm col-sm-2" oninput="changeCategory(this.value)" style="width: 15em !important; min-width: 15em; max-width: 15em;"></select>
        </div>
        <div class="col-auto">
            <label for="fsECpast" class="form-label"><?php echo text::_('MOD_EVENTCHART_FILTER_PAST') ?></label>
            <input id="fsECpast" type="number" min="0" class="form-control form-control-sm mcol-sm-2" oninput="changePast(this.value)" style="width: 15em !important; min-width: 15em; max-width: 15em;"></input>
        </div>
        <div class="col-auto">
            <label for="fsECrange" class="form-label"><?php echo text::_('MOD_EVENTCHART_FILTER_RANGE') ?></label>
            <input id="fsECrange" type="number" min="0" class="form-control form-control-sm col-sm-2" oninput="changeRange(this.value)" style="width: 15em !important; min-width: 15em; max-width: 15em;"></input>
        </div>
    </div>
</div>
<canvas id="fsECchart" width="800" height="600"></canvas>
