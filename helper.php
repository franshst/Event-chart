<?php

// copyright Frans Stuurman 2024

/* task of this file
    retrieve data
*/

//TODO rewrite SQL to use Joomla SQL

class ModEventChartHelper
{
    /**
     * Retrieves the data from the database
     *
     * @param   array  $params An object containing the module parameters
     *
     * @access public
     */    
    //public static function getHello($params)
    //{
    //    return 'Hello, World!';
    //}
    public static function getEventData()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);



        // Step 1: Connect to MySQL database and fetch data
        $servername = "sql418.your-server.de";
        $username = "balfolb_1_r";
        $password = "eguZFzUmjH1ejvX1";
        $dbname = "balfolb_db1";

        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $category = 'Bal';
        $location = 'Mezrab';

        //$limit = date_format(date_sub(date_create(),date_interval_create_from_date_string("1 years")),"Ymd"); // filter function moved to javascript

        $sql = "SELECT DISTINCT jml_eb_events.id AS event_id, title, " .
        "jml_eb_locations.name AS location, " .
        "event_date, event_capacity, " .
        "jml_eb_registrants.id as registrant_id, " .
        "jml_eb_registrants.register_date AS sale_date, jml_eb_registrants.number_registrants AS tickets_sold " .
        "FROM jml_eb_events " .
        "INNER JOIN jml_eb_event_categories ON jml_eb_events.id = jml_eb_event_categories.event_id " .
        "INNER JOIN jml_eb_categories ON jml_eb_categories.id = jml_eb_event_categories.category_id " .
        "INNER JOIN jml_eb_locations ON jml_eb_events.location_id = jml_eb_locations.id " .
        "INNER JOIN jml_eb_registrants ON jml_eb_events.id = jml_eb_registrants.event_id " .
        "WHERE jml_eb_registrants.published = 1 AND jml_eb_registrants.payment_status = 1 " .
        "AND jml_eb_categories.name = '" . $category . "' " .
        "AND jml_eb_locations.name = '" . $location . "' " .
        //"AND title like '%BMB%' OR title like '%Androne%' " .
        //"AND jml_eb_events.id = 57 " .
        //"AND jml_eb_events.id = 31 " .
        //"AND title like '%Herre%' " .
        //"AND event_date > " . $limit . " " .
        "ORDER BY event_date, sale_date";

        $result = $conn->query($sql);

        // Step 2: Calculate cumulative tickets sold for each event
        $cumulativeData = array();
        while ($row = $result->fetch_assoc()) {
        //  dump($row);
            $event_id = $row['event_id'];
        //dump ($event_id);
            $tickets_sold = $row['tickets_sold'];
            $event_date = strtotime($row['event_date']);
            if (!isset($cumulativeData[$event_id])) {
                $cum_tickets_sold = 0;
                $cumulativeData[$event_id] = array(
                'event_id' => $row['event_id'],
                'event_date' => $event_date,
                'title' => iso8859_1_to_utf8($row['title']),
                'location' => $row['location'],
                'event_capacity' => $row['event_capacity'],
                'sales' => array());
            }
        //dump(json_encode($cumulativeData[$event_id]));

            $cum_tickets_sold = $cum_tickets_sold + $row['tickets_sold'];
            $sale_date = strtotime($row['sale_date']);
            $days_before_event = ($event_date - $sale_date) / (60 * 60 * 24);
            $sales = array(
                'sale_date' => $sale_date,
                'days_before_event' => $days_before_event,
                'tickets_sold' => $row['tickets_sold'],
                'cum_tickets_sold' => $cum_tickets_sold);
        //dump(json_encode($cumulativeData[$event_id]));
        //dump ($cumulativeData[$event_id]['title']);
        //dump(json_encode($sales));
            array_push($cumulativeData[$event_id]['sales'], $sales);
        }

        // helper function, convert to utf8, as there is no direct support in PHP8.2
        function iso8859_1_to_utf8(string $s): string {
            $s .= $s;
            $len = \strlen($s);

            for ($i = $len >> 1, $j = 0; $i < $len; ++$i, ++$j) {
                switch (true) {
                    case $s[$i] < "\x80": $s[$j] = $s[$i]; break;
                    case $s[$i] < "\xC0": $s[$j] = "\xC2"; $s[++$j] = $s[$i]; break;
                    default: $s[$j] = "\xC3"; $s[++$j] = \chr(\ord($s[$i]) - 64); break;
                }
            }

            return substr($s, 0, $j);
        }

        // dump vars on html
        function dump($data) {
            $out = $data;
            if (is_array($out))
            $out = implode(',', $out);

            echo $out. "<br>";
        }

        return($cumulativeData);
    }
}

