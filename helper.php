<?php
// Copyright Frans Stuurman 2024, MIT licence

/* task of this file
    Fetch event data from database
*/

use Joomla\CMS\Factory;
class ModEventChartHelper
{
    /**
     * Retrieves eventdata from the database
     *
     *
     * @access public
     */
 
    public static function getEventData($params)
    {
        // Step 1: Connect to MySQL database and fetch data
        // Get a db connection.
        $db = Factory::getContainer()->get('DatabaseDriver');
        
        //construct field list
        $fieldlist = $db->quoteName(['#__eb_events.id', '#__eb_events.title', 'event_date', 'event_capacity', '#__eb_locations.name', '#__eb_categories.name', '#__eb_registrants.id', '#__eb_registrants.register_date', '#__eb_registrants.number_registrants'],
                                    ['event_id'       , null                , null        , null            , 'location'            , 'category'             , 'registrant_id'       , 'sale_date'                      , 'tickets_sold']);
        //$fieldlist[0] = 'distinct ' . $fieldlist[0];
        
        // Retrieve the data

        $query = $db->getQuery(true)
                    ->select($fieldlist)
                    ->from($db->quoteName('#__eb_events'))
                    ->innerJoin($db->quoteName('#__eb_event_categories'), $db->quoteName('#__eb_events.id')          . '=' . $db->quoteName('#__eb_event_categories.event_id'))
                    ->innerJoin($db->quoteName('#__eb_categories')      , $db->quoteName('#__eb_categories.id')      . '=' . $db->quoteName('#__eb_event_categories.category_id'))
                    ->innerJoin($db->quoteName('#__eb_locations')       , $db->quoteName('#__eb_events.location_id') . '=' . $db->quoteName('#__eb_locations.id'))
                    ->innerJoin($db->quoteName('#__eb_registrants')     , $db->quoteName('#__eb_events.id')          . '=' . $db->quoteName('#__eb_registrants.event_id'))
                    ->where($db->quoteName('#__eb_registrants.published')      . '= 1')
                    ->where($db->quoteName('#__eb_registrants.payment_status') . '= 1')
                    //->where($db->quoteName('#__eb_events.id')                  . '= 14')
                    ->where($db->quotename('#__eb_categories.name')            . '= :category'); //todo: proper filter on category
        
        $categoryFilter = 'Bal';        
        $query->bind(':category', $categoryFilter);
        //dump(str_replace('#__','jml_',$query->__toString()));
        //return($query->__toString());
        // Prepare the query
        $db->setQuery($query);
        // get data
        $result = $db->loadAssocList();

        // Step 2: Calculate cumulative tickets sold for each event
        $cumulativeData = array();
        foreach ($result as $row) {
            //dump($row);
            $event_id = $row['event_id'];
        //dump ($event_id);
            $tickets_sold = $row['tickets_sold'];
            $event_date = strtotime($row['event_date']);
            if (!isset($cumulativeData[$event_id])) {
                $cum_tickets_sold = 0;
                $cumulativeData[$event_id] = array(
                'event_id' => $row['event_id'],
                'event_date' => $event_date,
                //'title' => iso8859_1_to_utf8($row['title']),
                'title'=> $row['title'],
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
        //dump($cumulativeData);
        return($cumulativeData);

    }

    // Helper functions
     /**
     * Convert to utf8, as there is no direct support in PHP8.2
     *
     *
     * @access private
     */

    private function iso8859_1_to_utf8(string $s): string {
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

    // testing only, dump vars on html
    private function dump($data) {
        $out = $data;
        if (is_array($out))
        $out = implode(',', $out);

        echo $out. "<br>";
    }
    
}