<?php
// Copyright Frans Stuurman 2024, MIT licence

/* task of this file
    Display data
*/

// No direct access
defined('_JEXEC') or die;

echo '<script>';
// all dates are unix timestamp in seconds
//echo 'document.write(\'tot hier\');';
echo 'var chartData = ' . json_encode($eventData) . ';';
//echo 'document.write("hier<br>");';
// default filter items, from future module options 
// echo 'var past = 365;'
// echo 'var range = 30;'
// echo 'var titleFilter = \'Mezrab\';'
// echo 'var category = [\'Bal\'];'


echo '</script>';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js">
</script>

<div id="fsECFilters">
    <h3>Filters</h3>
    <label for="fsECtitle">Event title</label>
    <input type="text" id="fsECtitle" oninput="changeTitle(this.value)">
    <label for="fsECpast">Include past events</label>
    <select id="fsECpast" oninput="changePast(this.value)"></select>
    <label for="fsECrange">Horizontal range</label>
    <select id="fsECrange" oninput="changeRange(this.value)"></select>
</div>
<canvas id="fsECchart" width="800" height="600"></canvas>
<script>

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
        main: for(var element1 in array1) {
            for (var element2 in array2) {
                if (element1 === element2) {
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
            (filter.title === '' || event.title.toUpperCase().includes(filter.title.toUpperCase()))
//            (filter.category.length == 0 || anyMatch(chartData[event_id].category, filter.category))
        );
    }

    // returns how many days ago the first sale was of every filtered event (relative to event date)
    // used to calculate the range of the chart.
    function firstSale(chartData, filter) {
        first = 7; // minimum range is 7 days
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
    function insertLastSaleByEvent(chartData) {
        for (var event_id in chartData){
            var last_sale = 99999;
            chartData[event_id].sales.forEach((sale) => {
                if (sale.days_before_event < last_sale) last_sale = sale.days_before_event;
            });
            chartData[event_id].last_sale = last_sale;
        }
    }

    // populate the chartjs datasets, from chartData, with filters
    function loadData(chartData, datasets, filter) {
        for (var event_id in chartData) {
            if (filterEvent(chartData[event_id], filter)) {
                datasets.push({
                    label: chartData[event_id].title,
                    order: 0-chartData[event_id].event_date,
                    data: chartData[event_id].sales.map((row) => ({
                        x: row.days_before_event,
                        y: row.cum_tickets_sold
                    })),
                    borderColor: '#' + Math.floor(Math.random()*16777215).toString(16), // Random color
                    fill: false
                });
            }
        }
    }

    function updateChart(chart, chartData, datasets, filter) {
        datasets = [];
        loadData(chartData, datasets, filter);
        console.log("datasets: " + datasets.length);
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
/*
    for (var event_id in chartData){
        var last_sale = 99999;
        chartData[event_id].sales.forEach((sale) => {
            if (sale.days_before_event < last_sale) last_sale = sale.days_before_event;
        });
        chartData[event_id].last_sale = last_sale;
    }
*/
    //  Calculate filters
    var filter = {};
    filter.past = 365;
    filter.range = 31;
    // include events in this date range
    filter.from = new Date(); filter.from.setDate(filter.from.getDate() - filter.past);
    filter.to = new Date(); filter.to.setDate(filter.to.getDate() + filter.range);
    // document.write(fromDate);
    filter.title = 'Mezrab';
    filter.category = ['Bal'];
    filter.first = firstSale(chartData, filter); // to calculate the maximum x range of the chart

    // populate filter fields in html
    document.getElementById("fsECtitle").value = filter.title;
    var rangeDropdown = document.getElementById("fsECrange");
    addOption(rangeDropdown,"1 week",7,false);
    addOption(rangeDropdown,"2 weeks",14,false);
    addOption(rangeDropdown,"1 month",31,true);  // default
    addOption(rangeDropdown,"2 months",62,false);
    addOption(rangeDropdown,"3 months",92,false);
    addOption(rangeDropdown,"4 months",123,false);
    addOption(rangeDropdown,"6 months",183,false);
    addOption(rangeDropdown,"all",99999,false); //Todo: bereken actuele maximum
    var histDropdown = document.getElementById("fsECpast");
    addOption(histDropdown,"3 months",92,false);
    addOption(histDropdown,"6 months",183,false);
    addOption(histDropdown,"1 year",365,true); // default
    addOption(histDropdown,"2 years",730,false);
    addOption(histDropdown,"all",99999,false);


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
            }
        }
    });

    // callback functions from the HTML filter fields/dropdowns
    function changeTitle(newTitleFilter) {
        console.log("New title filter: " + newTitleFilter);
        filter.title = newTitleFilter;
        updateChart(chart, chartData, datasets, filter);
    }

    function changePast(newPastFilter) {
        console.log("New past filter: " + newPastFilter);
        filter.past = newPastFilter;
        filter.from = new Date(); filter.from.setDate(filter.from.getDate() - filter.past);
        updateChart(chart, chartData, datasets, filter);
    }

    function changeRange(newRangeFilter) {
        console.log("New range filter: " + newRangeFilter);
        filter.range = newRangeFilter;
        updateChart(chart, chartData, datasets, filter);
    }
</script>
