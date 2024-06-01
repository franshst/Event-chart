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
 
    public static function getEventData() {

        $events = self::getEvents();
        $categories = self::getEventCategories();

        // Process events

        $eventData = array();
        foreach ($events as $row) {
            $event_id = $row['event_id'];
            $event_date = strtotime($row['event_date']);
            if (!isset($eventData[$event_id])) {
                $cum_tickets_sold = 0;
                $eventData[$event_id] = array(
                'event_id' => $row['event_id'],
                'event_date' => $event_date,
                //'title' => iso8859_1_to_utf8($row['title']),
                'title'=> $row['title'],
                'location_id' => $row['location_id'],
                'categoryIdList' => $categories[$event_id], // here insert the list of categories
                'event_capacity' => $row['event_capacity'],
                'sales' => array());
            }

            // Calculate cumulative tickets sold for each event
            $cum_tickets_sold = $cum_tickets_sold + $row['tickets_sold'];
            $sale_date = strtotime($row['sale_date']);
            $days_before_event = ($event_date - $sale_date) / (60 * 60 * 24);
            $sales = array(
                'sale_date' => $sale_date,
                'days_before_event' => $days_before_event,
                'tickets_sold' => $row['tickets_sold'],
                'cum_tickets_sold' => $cum_tickets_sold);

            array_push($eventData[$event_id]['sales'], $sales); // here push the sale
        }

        return $eventData;

    }

    // get events including registrations
    private static function getEvents() {
        $db = Factory::getContainer()->get('DatabaseDriver');

        //construct field list
        $fieldlist = $db->quoteName(['#__eb_events.id', 'title', 'event_date', 'event_capacity', 'location_id', '#__eb_registrants.id', 'register_date', 'number_registrants'],
                                    ['event_id'       , null   , null        , null            , null         , 'registrant_id'       , 'sale_date'    , 'tickets_sold']);

        $query = $db->getQuery(true)
                    ->select($fieldlist)
                    ->from($db->quoteName('#__eb_events'))
                    ->innerJoin($db->quoteName('#__eb_registrants'), $db->quoteName('#__eb_events.id') . '=' . $db->quoteName('#__eb_registrants.event_id'))
                    ->where($db->quoteName('#__eb_events.published')           . '= 1')
                    ->where($db->quoteName('#__eb_registrants.published')      . '= 1')
                    ->where($db->quoteName('#__eb_registrants.payment_status') . '= 1')
                    ->order($db->quoteName(['event_id','#__eb_registrants.register_date']));


        // Prepare the query
        $db->setQuery($query);
        // get data
        return $db->loadAssocList();
    }

    private static function getEventCategories() {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getquery(true)
                    ->select($db->quoteName(['event_id','category_id']))
                    ->from($db->quoteName('#__eb_event_categories'));
        $db->setQuery($query);
        $results = $db->loadAssocList();
        // Initialize an empty array to store the categories
        $categories = [];

        // Process the results to structure the array as desired
        foreach ($results as $row) {
            $event_id = $row['event_id'];
            $category_id = $row['category_id'];
            
            // Check if the event_id key already exists in the categories array
            if (!isset($categories[$event_id])) {
                $categories[$event_id] = [];
            }
            
            // Add the category_id to the event's category list
            $categories[$event_id][] = $category_id;
        }
        return $categories;
    }

    public static function getLocationData() {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id','name']))
            ->from($db->quoteName('#__eb_locations',''));

        $db->setQuery($query);
        return $db->loadAssocList();
    }

    public static function getCategoryData() {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id','name','parent']))
            ->from($db->quoteName('#__eb_categories'));

        $db->setQuery($query);
        $categoryData = $db->loadAssocList();

        // initialize intermediates
        foreach($categoryData as &$cat){
            $cat['fullName'] = $cat['name'];
            $cat['prefixAbbr'] = '';
            $cat['pIdList'] = [$cat['id']];
            $cat['cIdList'] = [$cat['id']];
            $cat['parentsDone'] = false;
            $cat['childrenDone'] = false;
        }
        // process ancesters and children
        // note, abbrname will be used for dropdown choices, idList to match events
        foreach($categoryData as &$cat){
            // fill intermediates
            self::addCategoryParent($cat,$categoryData);
            self::addCategoryChildren($cat, $categoryData);
            // finally, construct dropdown data
            $cat['idList'] = array_unique(array_merge($cat['pIdList'], $cat['cIdList']));
            $cat['abbrName'] = $cat['prefixAbbr'] . $cat['name'];
        }
        // cleanup intermediates
        foreach($categoryData as &$cat){
            unset($cat['prefixAbbr']);
            unset($cat['pIdList']);
            unset($cat['cIdList']);
            unset($cat['parentsDone']);
            unset($cat['childrenDone']);
        }
        unset($cat);
        usort($categoryData, function ($item1, $item2) {
            return $item1['fullName'] <=> $item2['fullName'];
        });

        return $categoryData ;
    }

    // enrich category entry with data from parent. Parameters: category entry to be enriched, where to start searching for parent, list of categories
    private static function addCategoryParent(&$pData,$categoryData){
        foreach ($categoryData as $cat){
            if ($cat['id'] == $pData['parent']) {
                $parent = $cat;
                if (!$parent['parentsDone']){
                    self::addCategoryParent($parent,$categoryData); // add the other ancestors recursively first
                }
                $pData['fullName'] = $parent['fullName'] . ' - ' . $pData['name'];  // insert parent name before current
                $pData['prefixAbbr'] = ' - ' . $parent['prefixAbbr'];
                $pData['pIdList'] = array_unique(array_merge($parent['pIdList'], $pData['pIdList'])); // add id of parent to the list
                break; // parent found, data added. no further search needed.
            }
        }
        $pData['parentsDone'] = true;
    }

    // enrich category with data from children.
    private static function addCategoryChildren(&$pData, $categoryData){
        foreach ($categoryData as $cat){
            if ($cat['parent'] == $pData['id']) {
                $child = $cat;
                if (!$child['childrenDone']){
                    self::addCategoryChildren($child,$categoryData); // add the other ancestors recursively first
                }
                $pData['cIdList'] = array_unique(array_merge($child['cIdList'], $pData['cIdList'])); // add idlist of children to the list
                // child found, but keep searching for other siblings
            }
        }
        $pData['childrenDone'] = true;
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

        echo $out. "<br>";
    }
    
}
