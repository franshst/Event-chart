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
    Display a chart of sales
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
// Multi-lingual?



echo '</script>';
?>

<div id="fsECFilters">
    <h3>Filters</h3>
    <!--label for="fsECtitle">Event title</label -->
    <input type="text" id="fsECtitle" placeholder="Filter event title" oninput="changeTitle(this.value)" style="width: 15em !important; min-width: 15em; max-width: 15em;"></input>
    <!-- label for="fsECloc">Location</label -->
    <select id="fsECloc" oninput="changeLocation(this.value)" style="width: 15em !important; min-width: 15em; max-width: 15em;"></select>
    <!-- label for="fsECcat">Category</label -->
    <select id="fsECcat" oninput="changeCategory(this.value)" style="width: 15em !important; min-width: 15em; max-width: 15em;"></select>
    <!-- label for="fsECpast">Include past events</label -->
    <select id="fsECpast" oninput="changePast(this.value)" style="width: 15em !important; min-width: 15em; max-width: 15em;"></select>
    <!-- label for="fsECrange">Horizontal range</label -->
    <select id="fsECrange" oninput="changeRange(this.value)" style="width: 15em !important; min-width: 15em; max-width: 15em;"></select>
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
            event.event_date >= filter.from &&
            event.last_sale <= filter.range &&
            (filter.title === '' || event.title.toUpperCase().includes(filter.title.toUpperCase())) &&
            ((filter.locationID == -1) || (event.location_id == filter.locationID)) &&
            (filter.categoryIdList.length == 0 || anyMatch(event.categoryIdList, filter.categoryIdList))
        );
    }

    // returns how many days ago the first sale was of every filtered event (relative to event date)
    // used to calculate the range of the chart.
    function firstSale(chartData, filter) {
        let first = 7; // minimum range is 7 days
        for (var event_id in chartData) {
            if (filterEvent(chartData[event_id],filter)) {
                chartData[event_id].sales.forEach((sale) => {
                    if (sale.days_before_event > first) first = sale.days_before_event;
                });
            }
        }
        return first;
    }

    // populate chartData with last_sale of each event.
    // hmm, this should be moved to php while processing the data from the db
    function insertLastSaleByEvent(chartData) {
        for (var event_id in chartData){
            var lastSale = 99999; //infinitely in the past
            var lastSaleDate = new Date(-8640000000000000); //on the minimum date
            var totalTicketsSold = 0; //a sale of 0
            chartData[event_id].sales.forEach((sale) => {
                if (sale.days_before_event < lastSale) {
                    lastSale = sale.days_before_event;
                    lastSaleDate = sale.sale_date;
                    totalTicketsSold = sale.cum_tickets_sold;
                }
            });
            chartData[event_id].last_sale = lastSale;

            if ((lastSale != 99999) && (lastSaleDate < chartData[event_id].event_date)) { // if any sales and if last sale is before the event date
                // place an extra datapoint
                let today = new Date();
                if (today > chartData[event_id].event_date) { // event has passed, place it on event_date
                    chartData[event_id].sales.push({days_before_event:0,tickets_sold:0,cum_tickets_sold:totalTicketsSold,pointStyle: 'crossRot'});
                } else { // event in future, place it on today
                    const diffDates = (chartData[event_id].event_date - today)/(1000 * 60 * 60 * 24);
                    chartData[event_id].sales.push({days_before_event:diffDates,tickets_sold:0,cum_tickets_sold:totalTicketsSold,pointStyle: 'crossRot'});
                }
            }
        }
    }

    // populate the chartjs datasets, from chartData, with filters
    function loadData(chartData, datasets, filter) {
        for (var event_id in chartData) {
            if (filterEvent(chartData[event_id], filter)) {
                datasets.push({
                    label: chartData[event_id].title,
                    order: 0-chartData[event_id].event_date,
                    data: chartData[event_id].sales.map((row) => (
                        {
                            x: row.days_before_event,
                            y: row.cum_tickets_sold,
                            pointStyle: row.pointStyle || 'circle'
                        }
                        )),
                    borderColor: '#' + Math.floor(Math.random()*16777215).toString(16), // Random color
                    fill: false,
                    showLine: true, // Ensures lines are drawn between points
                    pointStyle: 'circle' // Default point style for the dataset
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
    convertDates(chartData,['event_date','sale_date']);

    // add the last sale
    insertLastSaleByEvent(chartData);

    //  Calculate filters
    var filter = {};
    filter.locationAll = "-- Filter location --";
    filter.categoryAll = "-- Filter category --";
    filter.rangeAll = "-- Display horizontal range --";
    filter.histAll = "-- Include past events --";

    filter.past = 365;
    filter.range = 31;
    // include events in this date range
    filter.from = new Date(); filter.from.setDate(filter.from.getDate() - filter.past);
    filter.to = new Date(); filter.to.setDate(filter.to.getDate() + filter.range);
    // document.write(fromDate);
    filter.title = '';
    filter.location = 'Mezrab';
    filter.locationID = "-1"; // will be replaced, see below
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
            filter.locationID = locationData[id].id;
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
    addOption(histDropdown,"6 months",183,false);
    addOption(histDropdown,"1 year",365,true); // default
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
        filter.locationID = newFilter;
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
