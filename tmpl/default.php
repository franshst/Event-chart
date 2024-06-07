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
// export php data to javascript
// note: php dates are unix timestamp in seconds
echo 'var eventData = ' . json_encode($eventData) . ';';
echo 'var locationData = ' . json_encode($locationData) . ';';
echo 'var categoryData = ' . json_encode($categoryData) . ';';
echo 'var params = ' . json_encode($params->toArray()) . ';';

// TODO
// refactor category filter
// Use defaults
// Multi-lingual
// Separate js file
// Minify js file
// checksum of zip file



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
            <input id="fsECpast" type="number" min="0" class="form-control form-control-sm mcol-sm-2" oninput="changePast(this.value)" style="width: 15em !important; min-width: 15em; max-width: 15em;"></input>
        </div>
        <div class="col-auto">
            <input id="fsECrange" type="number" min="0" class="form-control form-control-sm col-sm-2" oninput="changeRange(this.value)" style="width: 15em !important; min-width: 15em; max-width: 15em;"></input>
        </div>
    </div>
</div>
<canvas id="fsECchart" width="800" height="600"></canvas>
<script type = "module">
    import {Chart, LineController, LineElement, PointElement, LinearScale, Title, CategoryScale, Legend, Tooltip} from 'https://cdn.skypack.dev/chart.js';
    Chart.register(LineController, LineElement, PointElement, LinearScale, Title, CategoryScale, Legend, Tooltip);

    const weeks = 7;
    const months = 365/12;
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

    // test if one of the categories of an event, or one of the parents of the category is matched by the filter
    // eventCategoryIdList: list of category id's
    // categoryIdFilter: the id to match
    // categoryData (global structure): list of 'id', 'name', 'parent'
    function matchIdListCategory(eventCategoryIdList, categoryIdFilter) {
        if (categoryIdFilter == 0) return true;
        for (let id of eventCategoryIdList) {
            // if any event category matched the filter, return true
            if (matchIdCategory(id, categoryIdFilter)) return true;
        }
        return false;
    }
    // as above, test parents
    function matchIdCategory(eventCategoryId, categoryIdFilter) {
        let category = categoryData.find(cat => cat.id == eventCategoryId);
        if (category) { // should always be a valid category, because there should never be an event with an invalid category id.
            if (category.id == categoryIdFilter) return true; // we have a match
            if (category.parent == 0) return false; // no match, no parent
            return matchIdCategory(category.parent, categoryIdFilter); // test parent
        }
        return false; // if category is not found, return false. Should never happen.
    }


    // returns true if event is included in the chart
    function filterEvent(event, filter){
        // include events in this date range
        let fromDate = new Date(); fromDate.setDate(fromDate.getDate() - filter.past*months);
        //toDate = new Date(); toDate.setDate(toDate.getDate() + filter.range);
        return(
            (filter.title === '' || event.title.toUpperCase().includes(filter.title.toUpperCase())) &&
            ((filter.location == 0) || (event.locationId == filter.location)) &&
            matchIdListCategory(event.categoryIdList, filter.category) &&
            (filter.past == 0 || event.eventDate >= fromDate) &&
            (filter.range == 0 || event.lastRegistration <= filter.range*weeks)
        );
    }

    // returns how many days ago the first sale was of filtered events (relative to event date)
    // used to calculate the range of the chart.
    // uses global eventData
    function firstSale(filter) {
        let first = 7; // minimum range is 7 days
        for (let eventId in eventData) {
            if (filterEvent (eventData[eventId], filter)) {
                eventData[eventId].registrations.forEach((sale) => {
                    if (sale.daysBeforeEvent > first) first = sale.daysBeforeEvent;
                });
            }
        }
        return first;
    }

    // insert extra datapoint into eventData to extend the line to today or event date
    // uses global eventData
    function insertExtraDatapoint() {
        for (let eventId in eventData) {
            let today = new Date();
            if (eventData[eventId].eventDate < today) { // event in the past, insert the final number of registrants
                if (eventData[eventId].eventDate > eventData[eventId].lastRegistrationDate) {
                    eventData[eventId].registrations.push({registerDate: eventData[eventId].eventDate, daysBeforeEvent: 0 ,numberRegistrants: 0, cumRegistrants: eventData[eventId].cumRegistrants});
                    // eventData[eventId].lastRegistration = 0; //do we need this? Will place a horizontal line if last sale was before the horizontal range for a past event.
                }
            } else { // event in the future, extend the line to today, to show the number of registrants as of today
                const diffDates = (eventData[eventId].eventDate - today)/(1000 * 60 * 60 * 24);
                eventData[eventId].registrations.push({registerDate: today, daysBeforeEvent: diffDates, numberRegistrants: 0, cumRegistrants: eventData[eventId].cumRegistrants});
                eventData[eventId].lastRegistration = diffDates;
            }
        }
    }

    // populate the chartjs datasets, from eventData, with filters
    // uses global eventData
    function loadData(datasets, filter) {
        console.log('loadData');
        console.log(filter);
        for (let eventId in eventData) {
            if (filterEvent(eventData[eventId], filter)) {
                datasets.push({
                    label: eventData[eventId].title,
                    order: 0 - eventData[eventId].eventDate,
                    data: eventData[eventId].registrations.map((row) => (
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

    // update the chart
    // uses global eventData
    function updateChart(chart, datasets, filter) {
        datasets = []; // new datasets
        loadData(datasets, filter);
        chart.data.datasets = datasets;
        chart.options.scales.x.max = (filter.range == 0 ? firstSale(filter) : Math.min(filter.range*weeks, firstSale(filter)));
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
    convertDates(eventData,['eventDate','registerDate','firstRegistrationDate','lastRegistrationDate']);

    // extend graph to event date or today
    insertExtraDatapoint();

    // set defaults if not set on module parameter page.(!is_set(params.title) params.title = ''; // all titles
    params.title = params.title ?? '';
    params.location = params.location ?? 0;
    params.category = params.category ?? 0;
    params.range = params.range ?? 6;
    params.past = params.past ?? 6;

    // populate filter fields in html
    document.getElementById("fsECtitle").value = params.title;

    var locationDropdown = document.getElementById("fsECloc");
    for (var id in locationData){
        addOption(locationDropdown,locationData[id].name,locationData[id].id,locationData[id].id == params.location)
    }

    var categoryDropdown = document.getElementById("fsECcat");
    for (var c of categoryData) {
        addOption(categoryDropdown,c.name,c.id,c.id == params.category)
    }

    document.getElementById("fsECrange").value = params.range;
    document.getElementById("fsECpast").value = params.past;

    //  Calculate initial filters
    var filter = {};

    filter.title = params.title;
    filter.location = params.location;
    filter.category = params.category;
    filter.range = params.range;
    filter.past = params.past;

    // load data into chart
    var datasets = [];
    loadData(datasets, filter);

    // create chart
    // TODO x axis labels in weeks, days minor ticks
    let ctx = document.getElementById('fsECchart').getContext('2d');
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
                    max: filter.range == 0 ? firstsale(filter) : Math.min(filter.range*weeks, firstSale(filter)),
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
        updateChart(chart, datasets, filter);
    }

    window.changeLocation = function(newFilter) {
        filter.location = newFilter;
        updateChart(chart, datasets, filter);
    }

    window.changeCategory = function(newFilter) {
        filter.category = newFilter;
        updateChart(chart, datasets, filter);
    }

    window.changePast = function(newFilter) {
        filter.past = newFilter;
        updateChart(chart, datasets, filter);
    }

    window.changeRange = function(newFilter) {
        filter.range = newFilter;
        updateChart(chart, datasets, filter);
    }
</script>
