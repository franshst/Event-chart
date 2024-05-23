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
    <label for="fsECname">Event name</label>
    <input type="text" id="fsECname" oninput="changeTitle(this.value)">
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

    // populate the chartjs datasets, from chartData, with filters
    function loadData(chartData, datasets, filter) {
        for (var event_id in chartData) {

            if (chartData[event_id].event_date >= filter.from && 
                chartData[event_id].last_sale <= filter.range &&
                (filter.title === '' || chartData[event_id].title.toUpperCase().includes(filter.title.toUpperCase()))
//                (filter.category.length == 0 || anyMatch(chartData[event_id].category, filter.category))
                ){

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

    // convert PHP data to javascript data
    convertDates(chartData,['event_date','sale_date']);

    // get last sale (in days before event)

    for (var event_id in chartData){
        var last_sale = 99999;
        chartData[event_id].sales.forEach((sale) => {
            if (sale.days_before_event < last_sale) last_sale = sale.days_before_event;
        });
        chartData[event_id].last_sale = last_sale;

    }

    //  Calculate filters
    var filter = {};
    filter.past = 365;
    filter.range = 30;
    // include events in this date range
    filter.from = new Date(); filter.from.setDate(filter.from.getDate() - filter.past);
    filter.to = new Date(); filter.to.setDate(filter.to.getDate() + filter.range);
    // document.write(fromDate);
    filter.title = 'Mezrab';
    filter.category = ['Bal'];
    console.log(JSON.stringify(filter));


    // load data into chart
    var datasets = [];
    loadData(chartData, datasets, filter);

    // create chart
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

    // callback functions
    // titlefilter changed
    function changeTitle(newTitleFilter) {
        console.log("New title filter: " + newTitleFilter);
        filter.title = newTitleFilter
        datasets = [];
        loadData(chartData, datasets, filter);
        console.log("datasets: " + datasets.length);
        chart.data.datasets = datasets;
        chart.update();
    }
</script>
