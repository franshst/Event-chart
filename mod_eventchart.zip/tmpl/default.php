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

<canvas id="myChart" width="800" height="600"></canvas>
<script>

    //document.write(JSON.stringify(chartData));
    // function convertDates written by chatgpt
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
    /*
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
    */
//      console.log(JSON.stringify(chartData));
    convertDates(chartData,['event_date','sale_date']);
//      console.log(JSON.stringify(chartData));

//  Calculate filters

    pastFilter = 365;
    rangeFilter = 30;
    // include events in this date range
    var fromDate = new Date(); fromDate.setDate(fromDate.getDate() - pastFilter);
    var toDate = new Date(); toDate.setDate(toDate.getDate() + rangeFilter);
    document.write(fromDate);
//    var titleFilter = 'Mezrab';
//    var categoryFilter = ['Bal'];

//    console.log(fromDate);
//    console.log(toDate);

    var ctx = document.getElementById('myChart').getContext('2d');
    var datasets = [];
    for (var event_id in chartData) {
            console.log (chartData[event_id].title + '<br>');

        if (chartData[event_id].event_date >= fromDate && 
            chartData[event_id].event_date <= toDate
//            (titleFilter === '' || chartData[event_id].title.includes(titleFilter)) &&
//            (categoryFilter.length == 0 || anyMatch(chartData[event_id].category, categoryFilter))
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

//        console.log(JSON.stringify(datasets));


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
                    max: rangeFilter,
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
</script>
