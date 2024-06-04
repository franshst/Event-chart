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

echo '<script>';
// all dates are unix timestamp in seconds
//echo 'document.write(\'tot hier\');';
echo 'var chartData = ' . json_encode($eventData) . ';';
echo 'var locationData = ' . json_encode($locationData) . ';';
echo 'var categoryData = ' . json_encode($categoryData) . ';';
// default filter items, from future module options
// echo 'var past = 365;'
// echo 'var range = 30;'
// echo 'var titleFilter = \'Mezrab\';'
// echo 'var category = [\'Bal\'];'

// TODO
// Defaults for filters as component fields
// Multi-lingual
// Separate js file
// Minify js file



echo '</script>';
?>

<div id="fsECFilters">
    <div class="form-group form-row">
        <div class="col-auto">
            <input type="text" id="fsECtitle" placeholder="Filter event title" class="form-control form-control-sm col-sm-2" oninput="changeTitle(this.value)" style="width: 15em !important; min-width: 15em; max-width: 15em;"></input>
        </div>
    </div>
    <div class="form-group row">
        <div class="col-auto">
            <select id="fsECloc" class="form-select form-select-sm col-sm-2" oninput="changeLocation(this.value)" style="width: 15em !important; min-width: 15em; max-width: 15em;"></select>
        </div>
        <div class="col-auto">
            <select id="fsECcat" class="form-select form-select-sm col-sm-2" oninput="changeCategory(this.value)" style="width: 15em !important; min-width: 15em; max-width: 15em;"></select>
        </div>
        <div class="col-auto">
            <select id="fsECpast" class="form-select form-select-sm mcol-sm-2" oninput="changePast(this.value)" style="width: 15em !important; min-width: 15em; max-width: 15em;"></select>
        </div>
        <div class="col-auto">
            <select id="fsECrange" class="form-select form-select-sm col-sm-2" oninput="changeRange(this.value)" style="width: 15em !important; min-width: 15em; max-width: 15em;"></select>
        </div>
    </div>
</div>
<canvas id="fsECchart" width="800" height="600"></canvas>
<script type = "module">
    import {Chart, LineController, LineElement, PointElement, LinearScale, Title, CategoryScale, Legend, Tooltip} from 'https://cdn.skypack.dev/chart.js';
    Chart.register(LineController, LineElement, PointElement, LinearScale, Title, CategoryScale, Legend, Tooltip);

    // function convertDates written by chatgpt
    // conversion of PHP datetime values to javascript datetime values
    function convertDates(obj, dateFields) {
        if (Array.isArray(obj)) {
            obj.forEach(item => {
                convertDates(item, dateFields);
            });
        } else if (typeof obj === 'object' && obj !== null) {
            for (const key in obj) {
                if (dateFields.includes(key)) {
                    obj[key] = new Date(obj[key] * 1000); // Assuming timestamps are in seconds
                } else {
                    convertDates(obj[key], dateFields);
                }
            }
        }
    }

    // matches true, if any member in an array is equal to any member in other array
    function anyMatch(array1,array2) {
        var match = false;
        main: for(var element1 of array1) {
            for (var element2 of array2) {
                if (element1 == element2) {
                    match = true; break main;
                }
            }
        }
        return match;
    }

    // returns true if event is included in the chart
    function filterEvent(event, filter){
        return(
            event.eventDate >= filter.from &&
            event.lastRegistration <= filter.range &&
            (filter.title === '' || event.title.toUpperCase().includes(filter.title.toUpperCase())) &&
            ((filter.locationId == -1) || (event.locationId == filter.locationId)) &&
            (filter.categoryIdList.length == 0 || anyMatch(event.categoryIdList, filter.categoryIdList))
        );
    }

    // returns how many days ago the first sale was of every filtered event (relative to event date)
    // used to calculate the range of the chart.
    function firstSale(chartData, filter) {
        let first = 7; // minimum range is 7 days
        for (var eventId in chartData) {
            if (filterEvent(chartData[eventId],filter)) {
                chartData[eventId].registrations.forEach((sale) => {
                    if (sale.daysBeforeEvent > first) first = sale.daysBeforeEvent;
                });
            }
        }
        return first;
    }

    // insert extra datapoint to extend the line to today or event date
    function insertExtraDatapoint(chartData) {
        for (var eventId in chartData) {
            let today = new Date();
            if (chartData[eventId].eventDate < today) { // event in the past, insert the final number of registrants
                if (chartData[eventId].eventDate > chartData[eventId].lastRegistrationDate) {
                    chartData[eventId].registrations.push({registerDate: chartData[eventId].eventDate, daysBeforeEvent: 0 ,numberRegistrants: 0, cumRegistrants: chartData[eventId].cumRegistrants});
                    //chartData[eventId].lastRegistration = 0; //do we need this? Will place a horizontal line if last sale was before the horizontal range for a past event.
                }
            } else { // event in the future, extend the line to today, to show the number of registrants as of today
                const diffDates = (chartData[eventId].eventDate - today)/(1000 * 60 * 60 * 24);
                chartData[eventId].registrations.push({registerDate: today, daysBeforeEvent: diffDates, numberRegistrants: 0, cumRegistrants: chartData[eventId].cumRegistrants});
                chartData[eventId].lastRegistration = diffDates;
            }
        }
    }

    // populate the chartjs datasets, from chartData, with filters
    function loadData(chartData, datasets, filter) {
        for (var eventId in chartData) {
            if (filterEvent(chartData[eventId], filter)) {
                datasets.push({
                    label: chartData[eventId].title,
                    order: 0-chartData[eventId].eventDate,
                    data: chartData[eventId].registrations.map((row) => (
                        {
                            x: row.daysBeforeEvent,
                            y: row.cumRegistrants
                        }
                        )),
                    borderColor: '#' + Math.floor(Math.random()*16777215).toString(16), // Random color
                    fill: false,
                    showLine: true, // Ensures lines are drawn between points
                });
            }
        }
    }

    function updateChart(chart, chartData, datasets, filter) {
        datasets = [];
        loadData(chartData, datasets, filter);
        chart.data.datasets = datasets;
        filter.first = firstSale(chartData, filter);
        chart.options.scales.x.max = Math.min(filter.range, filter.first);
        chart.update();
    }

    // populate a dropdown
    function addOption(selectbox, text, value, selected) {
        var optn = document.createElement("option");
        optn.text = text;
        optn.value = value;
        optn.selected = selected;
        selectbox.options.add(optn);
    }

    // convert PHP data to javascript data
    convertDates(chartData,['eventDate','registerDate','firstRegistrationDate','lastRegistrationDate']);
    console.log(chartData);

    // extend graph to event date or today
    insertExtraDatapoint(chartData);

    //  Calculate filters
    var filter = {};
    filter.locationAll = "-- Filter location --";
    filter.categoryAll = "-- Filter category --";
    filter.rangeAll = "-- Display horizontal range --";
    filter.histAll = "-- Include past events --";

    filter.past = 183;
    filter.range = 31;
    // include events in this date range
    filter.from = new Date(); filter.from.setDate(filter.from.getDate() - filter.past);
    filter.to = new Date(); filter.to.setDate(filter.to.getDate() + filter.range);
    // document.write(fromDate);
    filter.title = '';
    filter.location = 'Mezrab';
    filter.locationId = "-1"; // will be replaced, see below
    filter.category = 'Hoofdpagina - Bal';
    filter.categoryIdList = []; // will be replaced
    filter.first = firstSale(chartData, filter); // to calculate the maximum x range of the chart

    // populate filter fields in html
    document.getElementById("fsECtitle").value = filter.title;
    var rangeDropdown = document.getElementById("fsECrange");
    addOption(rangeDropdown,filter.rangeAll,99999,false);
    addOption(rangeDropdown,"1 week",7,false);
    addOption(rangeDropdown,"2 weeks",14,false);
    addOption(rangeDropdown,"1 month",31,true);  // default
    addOption(rangeDropdown,"2 months",62,false);
    addOption(rangeDropdown,"3 months",92,false);
    addOption(rangeDropdown,"4 months",123,false);
    addOption(rangeDropdown,"6 months",183,false);

    
    var locationDropdown = document.getElementById("fsECloc");
    addOption(locationDropdown,filter.locationAll,"-1",false);
    for (var id in locationData){
        if (filter.location == locationData[id].name)
            filter.locationId = locationData[id].id;
        addOption(locationDropdown,locationData[id].name,locationData[id].id,locationData[id].name == filter.location);
    }

    var categoryDropdown = document.getElementById("fsECcat");
    addOption(categoryDropdown,filter.categoryAll,"-1",false);
    for (var id in categoryData) {
        addOption(categoryDropdown,categoryData[id].abbrName,categoryData[id].id,categoryData[id].fullName == filter.category)
        if (categoryData[id].fullName == filter.category)
            filter.categoryIdList = categoryData[id].idList;
    }

    var histDropdown = document.getElementById("fsECpast");
    addOption(histDropdown,filter.histAll,99999,false);
    addOption(histDropdown,"3 months",92,false);
    addOption(histDropdown,"6 months",183,true); // default
    addOption(histDropdown,"1 year",365,false);
    addOption(histDropdown,"2 years",730,false);


    // load data into chart
    var datasets = [];
    loadData(chartData, datasets, filter);

    // create chart
    // todo x axis labels to round to integer https://www.chartjs.org/docs/latest/axes/labelling.html
    var ctx = document.getElementById('fsECchart').getContext('2d');
    var chart = new Chart(ctx, {
        type: 'line',
        data: {
            datasets: datasets
        },
        options: {
            scales: {
                x: {
                    type: 'linear',
                    reverse: true,
                    max: filter.range,
                    position: 'bottom',
                    title: {
                        display: true,
                        text: 'Days before event'
                    }
                },
                y: {
                    type: 'linear',
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Tickets sold'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            }
        }
    });

    // callback functions from the HTML filter fields/dropdowns
    window.changeTitle = function(newFilter) {
        filter.title = newFilter;
        updateChart(chart, chartData, datasets, filter);
    }

    window.changeLocation = function(newFilter) {
        filter.locationId = newFilter;
/*        if (newFilter == -1) {
            filter.location = filter.locationAll;
        } else {
            filter.location = locationData.find((data) => data.id == newFilter).name;
        }
*/
        updateChart(chart, chartData, datasets, filter);
    }

    window.changeCategory = function(newFilter) {
        filter.categoryIdList = newFilter;
        if (newFilter == -1) {
/*            filter.category = filter.categoryAll;*/
            filter.categoryIdList = [];
        } else {
 /*           filter.category = categoryData.find((data) => data.id == newFilter).fullName; */
            filter.categoryIdList = categoryData.find((data) => data.id == newFilter).idList;
        }
        updateChart(chart, chartData, datasets, filter);
    }

    window.changePast = function(newFilter) {
/*        filter.past = newFilter;*/
        filter.from = new Date(); filter.from.setDate(filter.from.getDate() - newFilter);
        updateChart(chart, chartData, datasets, filter);
    }

    window.changeRange = function(newFilter) {
        filter.range = newFilter;
        updateChart(chart, chartData, datasets, filter);
    }
</script>
