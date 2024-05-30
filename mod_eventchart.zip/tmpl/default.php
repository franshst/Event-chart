<?php
// Copyright Frans Stuurman 2024, MIT licence

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
//echo 'console.log(categoryData);';
//echo 'document.write(categoryData);';
// default filter items, from future module options
// echo 'var past = 365;'
// echo 'var range = 30;'
// echo 'var titleFilter = \'Mezrab\';'
// echo 'var category = [\'Bal\'];'

// TODO
// Laatste sale doortrekken naar huidige datum, als evenement nog niet is geweest
// Defaults for filters as component fields
// Multi-lingual?



echo '</script>';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js">
</script>

<div id="fsECFilters">
    <h3>Filters</h3>
    <label for="fsECtitle">Event title</label>
    <input type="text" id="fsECtitle" oninput="changeTitle(this.value)">
    <label for="fsECloc">Location</label>
    <select id="fsECloc" oninput="changeLocation(this.value)" maxwidth: 20em></select>
    <label for="fsECcat">Category</label>
    <select id="fsECcat" oninput="changeCategory(this.value)" maxwidth: 20em></select>
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
        console.log('match ' + JSON.stringify(array1) + ' ' + JSON.stringify(array2));
        var match = false;
        main: for(var element1 of array1) {
            for (var element2 of array2) {
                if (element1 == element2) {
                    match = true; break main;
                }
            }
        }
        console.log('match result: ' + match);
        return match;
    }

    // returns true if event is included in the chart
    function filterEvent(event, filter){
        //console.log("filter locationID: " + filter.locationID);
        return(
            event.event_date >= filter.from &&
            event.last_sale <= filter.range &&
            (filter.title === '' || event.title.toUpperCase().includes(filter.title.toUpperCase())) &&
            ((filter.locationID == -1) || (event.location_id == filter.locationID)) &&
//            ((filter.categoryIdList == []) || filter.categoryIdList.includes(event.category_id))
            (filter.categoryIdList.length == 0 || anyMatch(event.categoryIdList, filter.categoryIdList))
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
        //console.log("datasets: " + datasets.length);
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
    addOption(rangeDropdown,"1 week",7,false);
    addOption(rangeDropdown,"2 weeks",14,false);
    addOption(rangeDropdown,"1 month",31,true);  // default
    addOption(rangeDropdown,"2 months",62,false);
    addOption(rangeDropdown,"3 months",92,false);
    addOption(rangeDropdown,"4 months",123,false);
    addOption(rangeDropdown,"6 months",183,false);
    addOption(rangeDropdown,"all",99999,false); //Todo: bereken actuele maximum
    
    var locationDropdown = document.getElementById("fsECloc");
    addOption(locationDropdown,"All","-1",false);
    for (var id in locationData){
        //console.log("addOption: " + locationData[id].id + ' ' + locationData[id].name);
        if (filter.location == locationData[id].name)
            filter.locationID = locationData[id].id;
        addOption(locationDropdown,locationData[id].name,locationData[id].id,locationData[id].name == filter.location);
    }

    var categoryDropdown = document.getElementById("fsECcat");
    addOption(categoryDropdown,"All","-1",false);
    for (var id in categoryData) {
        console.log("addOption: " + categoryData[id].id + ' ' + categoryData[id].fullName);
        addOption(categoryDropdown,categoryData[id].abbrName,categoryData[id].id,categoryData[id].fullName == filter.category)
        if (categoryData[id].fullName == filter.category)
            filter.categoryIdList = categoryData[id].idList;
    }

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
    function changeTitle(newFilter) {
    //    console.log("New title filter: " + newFilter);
        filter.title = newFilter;
        updateChart(chart, chartData, datasets, filter);
    }

    function changeLocation(newFilter) {
        //console.log("New location filter: " + newFilter);
        filter.locationID = newFilter;
        if (newFilter == -1) {
            filter.location = "All";
        } else {
            filter.location = locationData.find((data) => data.id == newFilter).name;
        }
        //console.log("Location: " + filter.location);
        updateChart(chart, chartData, datasets, filter);
    }

    function changeCategory(newFilter) {
        console.log("New category filter: " + newFilter);

        filter.categoryIdList = newFilter;
        if (newFilter == -1) {
            filter.category = "All";
            filter.categoryIdList = [];
        } else {
            filter.category = categoryData.find((data) => data.id == newFilter).fullName;
            filter.categoryIdList = categoryData.find((data) => data.id == newFilter).idList;
        }
        console.log("Category: " + filter.category + " " + JSON.stringify(filter.categoryIdList));
        updateChart(chart, chartData, datasets, filter);
    }

    function changePast(newFilter) {
    //    console.log("New past filter: " + newFilter);
        filter.past = newFilter;
        filter.from = new Date(); filter.from.setDate(filter.from.getDate() - filter.past);
        updateChart(chart, chartData, datasets, filter);
    }

    function changeRange(newFilter) {
    //    console.log("New range filter: " + newFilter);
        filter.range = newFilter;
        updateChart(chart, chartData, datasets, filter);
    }
</script>
