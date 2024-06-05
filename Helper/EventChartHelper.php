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
    Fetch event data from database
*/

namespace EventChartNamespace\Module\EventChart\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
class EventChartHelper
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
            $eventId = $row['eventId'];
            $eventDate = strtotime($row['eventDate']);

            // create new event item in the array
            if (!isset($eventData[$eventId])) {
                $cumRegistrants = 0;
                $eventData[$eventId] = array(
                'eventId' => $row['eventId'],
                'eventDate' => $eventDate,
                'title'=> $row['title'],
                'locationId' => $row['locationId'],
                'categoryIdList' => $categories[$eventId], // here insert the list of categories
                'eventCapacity' => $row['eventCapacity'],
                'cumRegistrants' => 0,
                'registrations' => array());
            }

            // Calculate cumulative tickets sold for each event
            $cumRegistrants = $cumRegistrants + $row['numberRegistrants'];
            $registerDate = strtotime($row['registerDate']);
            $daysBeforeEvent = ($eventDate - $registerDate) / (60 * 60 * 24); // note this is relative to event date, so higher numbers mean earlier registration

            // calculate first and last registration
            if (array_key_exists('firstRegistration',$eventData[$eventId])) {
                $eventData[$eventId]['firstRegistration'] = max($eventData[$eventId]['firstRegistration'],$daysBeforeEvent);
                $eventData[$eventId]['lastRegistration'] = min($eventData[$eventId]['lastRegistration'],$daysBeforeEvent);
                $eventData[$eventId]['firstRegistrationDate'] = min($eventData[$eventId]['firstRegistrationDate'],$registerDate);
                $eventData[$eventId]['lastRegistrationDate'] = max($eventData[$eventId]['lastRegistrationDate'],$registerDate);
            } else {
                $eventData[$eventId]['firstRegistration'] = $daysBeforeEvent;
                $eventData[$eventId]['lastRegistration'] = $daysBeforeEvent;
                $eventData[$eventId]['firstRegistrationDate'] = $registerDate;
                $eventData[$eventId]['lastRegistrationDate'] = $registerDate;
            }

            // update total number of registrations
            $eventData[$eventId]['cumRegistrants'] = max($eventData[$eventId]['cumRegistrants'],$cumRegistrants);

            // add registration
            $registrations = array(
                'registerDate' => $registerDate,
                'daysBeforeEvent' => $daysBeforeEvent,
                'numberRegistrants' => $row['numberRegistrants'],
                'cumRegistrants' => $cumRegistrants);

            array_push($eventData[$eventId]['registrations'], $registrations); // here push the sale
        }
        return $eventData;
    }

    // get events from database including registrations
    private static function getEvents() {
        $db = Factory::getContainer()->get('DatabaseDriver');

        //construct field list
        $fieldlist = $db->quoteName(['#__eb_events.id', 'title', 'event_date', 'event_capacity', 'location_id', '#__eb_registrants.id', 'register_date', 'number_registrants'],
                                    ['eventId'       , null   , 'eventDate'  , 'eventCapacity' , 'locationId' , 'registrantId'        , 'registerDate' , 'numberRegistrants']);

        $query = $db->getQuery(true)
                    ->select($fieldlist)
                    ->from($db->quoteName('#__eb_events'))
                    ->innerJoin($db->quoteName('#__eb_registrants'), $db->quoteName('#__eb_events.id') . '=' . $db->quoteName('#__eb_registrants.event_id'))
                    ->where($db->quoteName('#__eb_events.published')           . '= 1')
                    ->where($db->quoteName('#__eb_registrants.published')      . '= 1')
                    ->where($db->quoteName('#__eb_registrants.payment_status') . '= 1')
                    ->order($db->quoteName(['eventId','RegisterDate']));


        // Prepare the query
        $db->setQuery($query);
        // get data
        return $db->loadAssocList();
    }

    // get category list from each event from database
    private static function getEventCategories() {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getquery(true)
                    ->select($db->quoteName(['event_id','category_id'],['eventId','categoryId']))
                    ->from($db->quoteName('#__eb_event_categories'));
        $db->setQuery($query);
        $results = $db->loadAssocList();
        // Initialize an empty array to store the categories
        $categories = [];

        // Process the results to structure the array as desired
        foreach ($results as $row) {
            $eventId = $row['eventId'];
            $categoryId = $row['categoryId'];
            
            // Check if the eventId key already exists in the categories array
            if (!isset($categories[$eventId])) {
                $categories[$eventId] = [];
            }
            
            // Add the categoryId to the event's category list
            $categories[$eventId][] = $categoryId;
        }
        return $categories;
    }

    // get locations from database
    public static function getLocationData() {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id','name']))
            ->from($db->quoteName('#__eb_locations',''));

        $db->setQuery($query);
        return $db->loadAssocList();
    }

    /*
    Better query using recursive with

WITH RECURSIVE `categories` (`prefix`, `name`, `fullname`, `id`) AS (
                        SELECT CAST('' AS VARCHAR(20)) AS `prefix`, `name`, `name` AS `fullname`, `id` FROM `#__eb_categories`
                            WHERE `parent` = 0
                    UNION
                        SELECT CONCAT(`categories`.`prefix`,' - '), `cat_children`.`name`, CONCAT(`categories`.`fullname`, ' - ', `cat_children`.`name`), `cat_children`.`id` 
                            FROM `categories`
                        JOIN `#__eb_categories` AS `cat_children`
                            ON `categories`.`id` = `cat_children`.`parent`
                    )
                    SELECT CONCAT(`prefix`,`name`) AS `name`, `id`, `fullname` FROM `categories`
                    UNION 
                    SELECT 'MOD_EVENTCHART_ALL','0','AAAAAA'
                    ORDER BY `fullname`;

    
    */


    // get the category names from the database, and process the hierarchical data, so that a certain category has a list of category ids of all ancestor and child categories
    // this is used to create a filter on categories, where an event category will match a category with its children. (ancesters are included in case an event has an ancestor category id.)
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

    // testing only, dump vars on html
    private function dump($data) {
        $out = $data;

        echo $out. "<br>";
    }
    
}
