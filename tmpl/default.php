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
// Tooltip, display eventname, date of sale, day of week, cum tickets
// Multi-lingual
// Separate js file
// Minify js file
// checksum of zip file



echo '</script>';
?>

<div id="fsECFilters" class="container form-control-sm">
    <div class="form-group form-row">
        <div class="col-auto">
            <input type="text" id="fsECtitle" placeholder="Filter event title" class="form-control form-control-sm col-sm-2" oninput="changeTitle(this.value)" style="width: 15em !important; min-width: 15em; max-width: 15em;"></input>
        </div>
    </div>
    <div class="form-group row">
        <div class="col-auto">
            <label for="fsECloc" class="form-label">Location</label>
            <select id="fsECloc" class="form-select form-select-sm col-sm-2" oninput="changeLocation(this.value)" style="width: 15em !important; min-width: 15em; max-width: 15em;"></select>
        </div>
        <div class="col-auto">
            <label for="fsECcat" class="form-label">Category</label>
            <select id="fsECcat" class="form-select form-select-sm col-sm-2" oninput="changeCategory(this.value)" style="width: 15em !important; min-width: 15em; max-width: 15em;"></select>
        </div>
        <div class="col-auto">
            <label for="fsECpast" class="form-label">Include past events (months)</label>
            <input id="fsECpast" type="number" min="0" class="form-control form-control-sm mcol-sm-2" oninput="changePast(this.value)" style="width: 15em !important; min-width: 15em; max-width: 15em;"></input>
        </div>
        <div class="col-auto">
            <label for="fsECrange" class="form-label">Horizontal range (weeks)</label>
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
            (filter.range == 0 || (event.lastRegistration/weeks) <= filter.range)
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
    // display the chart
    // uses global eventData
    function loadData(chart, datasets, filter) {
        datasets = []; // new datasets
        //load data
        for (let eventId in eventData) {
            if (filterEvent(eventData[eventId], filter)) {
                datasets.push({
                    label: eventData[eventId].title,
                    order: 0 - eventData[eventId].eventDate,
                    data: eventData[eventId].registrations.map((row) => (
                        {
                            x: row.daysBeforeEvent / weeks,
                            y: row.cumRegistrants,
                            date: row.registerDate
                        }
                        )),
                    borderColor: '#' + Math.floor(Math.random()*16777215).toString(16), // Random color
                    fill: false,
                    showLine: true, // Ensures lines are drawn between points
                });
            }
        }
        //glue to chart
        chart.data.datasets = datasets;

        // some amendments of the display, according to current filter
        let xMax = (filter.range == 0 ? firstSale(filter) : Math.min(filter.range, firstSale(filter)));
        chart.options.scales.x.max = xMax;
        // subdivide by days when zoomed in
        if (xMax <= 6) {
            chart.options.scales.x.ticks.stepSize = 1 / 7; // Daily ticks (1 week / 7 days)
        } else {
            chart.options.scales.x.ticks.stepSize = 1; // Weekly ticks
        }
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

    //  Calculate initial filters
    var filter = {
        title: params.title ?? '',
        location: params.location ?? 0,
        category: params.category ?? 0,
        range: params.range ?? 6,
        past: params.past ?? 6
    };

    // populate filter fields in html
    {
        document.getElementById("fsECtitle").value = filter.title;

        var locationDropdown = document.getElementById("fsECloc");
        for (var id in locationData){
            addOption(locationDropdown,locationData[id].name,locationData[id].id,locationData[id].id == filter.location)
        }

        var categoryDropdown = document.getElementById("fsECcat");
        for (var c of categoryData) {
            addOption(categoryDropdown,c.name,c.id,c.id == filter.category)
        }

        document.getElementById("fsECrange").value = filter.range;
        document.getElementById("fsECpast").value = filter.past;
    }
    // create empty dataset
    var datasets = [];

    // create chart
    let ctx = document.getElementById('fsECchart').getContext('2d');
    var chart = new Chart(ctx, {
        type: 'line',
        data: {
            datasets: datasets
        },
        options: {
            datasets: {
                line: {
                    borderWidth: 1
                },
                point: {
                    radius: 2
                }
            },
            scales: {
                x: {
                    type: 'linear',
                    reverse: true,
                    max: filter.range == 0 ? firstsale(filter) : Math.min(filter.range, firstSale(filter)),
                    position: 'bottom',
                    ticks: {
                        callback: function(value, index, values) {
                            if (Math.round(value) == value) {
                                return '' + Math.round(value); // tick marks only on integer values (weeks), not on days.
                            } else {
                                return '';
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Weeks before event'
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
                    position: 'top',
                    align: 'start',
                    onClick: function(e, legendItem) {
                        var index = legendItem.datasetIndex;
                        var ci = this.chart;

                        // Check if the dataset is already highlighted
                        var currentWidth = ci.data.datasets[index].borderWidth;
                        var newWidth = currentWidth === 3 ? 1 : 3; // Toggle between 1 and 3

                        // Reset all dataset line widths to default
                        ci.data.datasets.forEach(function(dataset, i) {
                            dataset.borderWidth = 1;
                        });

                        // Set the clicked dataset to the new width
                        ci.data.datasets[index].borderWidth = newWidth;

                        ci.update();
                    }
                },
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            let con = context[0];
                            const dataPoint = con.raw;
                            const label = con.dataset.label;
                            const xValue = dataPoint.x;
                            const yValue = dataPoint.y;
                            const date = dataPoint.date;

                            return label;
                        },
                        label: function(context) {
                            let con = context;
                            const dataPoint = con.raw;
                            const label = con.dataset.label;
                            const xValue = dataPoint.x;
                            const yValue = dataPoint.y;
                            const date = dataPoint.date;

                            const options = { weekday: 'long'};
                            const userLocale =
                                navigator.languages && navigator.languages.length
                                    ? navigator.languages[0]
                                    : navigator.language;
                            let dateDisplay = date.toLocaleDateString(userLocale,options) + ' ' + date.toLocaleDateString(userLocale);
                            let xDisplay = Math.floor(xValue * 7) + ' days before event';
                            let yDisplay = yValue + ' registrations';


                            return [dateDisplay,xDisplay,yDisplay];
                        }
                    }
                }
            }
        }
    });

    // fill dataset and display
    loadData(chart,datasets,filter);

    // callback functions from the HTML filter fields/dropdowns
    window.changeTitle = function(newFilter) {
        filter.title = newFilter;
        loadData(chart, datasets, filter);
    }

    window.changeLocation = function(newFilter) {
        filter.location = newFilter;
        loadData(chart, datasets, filter);
    }

    window.changeCategory = function(newFilter) {
        filter.category = newFilter;
        loadData(chart, datasets, filter);
    }

    window.changePast = function(newFilter) {
        filter.past = newFilter;
        loadData(chart, datasets, filter);
    }

    window.changeRange = function(newFilter) {
        filter.range = newFilter;
        loadData(chart, datasets, filter);
    }
</script>
