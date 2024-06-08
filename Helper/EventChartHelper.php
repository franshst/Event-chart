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
use Joomla\CMS\Language\Text;
class EventChartHelper
{
    /**
     * Retrieves eventdata from the database
     *
     *
     * @access public
     */
 
    // Enrich the event list with calculated data and a list of categories
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
            ->select($db->quoteName(['id','name','name'],[null,null,'sort']))
            ->from($db->quoteName('#__eb_locations',''));
        $allrow = $db->getQuery(true)
            ->select([0, "'".text::_('MOD_EVENTCHART_ALL')."'", "' '"]);
        $query = $query->union($allrow)->order($db->quoteName('sort'));
        $db->setQuery($query);
        $results = $db->loadObjectList();
        foreach ($results as $loc) {
            $loc->name = html_entity_decode($loc->name, ENT_NOQUOTES|ENT_SUBSTITUTE);
        }
        return $results;
    }

    // Get categories, construct the fullname by prepending parent name, construct name by prepending dashes according to depth in the hiearchy
    public static function getCategoryData() {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $allText = Text::_('MOD_EVENTCHART_ALL');
        $query = "
            WITH RECURSIVE `categories` (`prefix`, `name`, `fullname`, `id`, `parent`) AS (
                SELECT CAST('' AS CHAR(20)) AS `prefix`, `name`, `name` AS `fullname`, `id`, `parent` FROM `#__eb_categories`
                    WHERE `parent` = 0
                UNION
                SELECT CONCAT(`categories`.`prefix`,' - '), `cat_children`.`name`, CONCAT(`categories`.`fullname`, ' - ', `cat_children`.`name`), `cat_children`.`id`, `cat_children`.`parent`
                    FROM `categories`
                JOIN `#__eb_categories` AS `cat_children`
                    ON `categories`.`id` = `cat_children`.`parent`
            )
            SELECT CONCAT(`prefix`,`name`) AS `name`, `id`, `fullname`, `parent` FROM `categories`
            UNION
            SELECT '" . $allText . "','0',' ', '0'
            ORDER BY `fullname`;
        ";
 
        $db->setQuery($query);
        // as a numbered array, because the order is important.
        return $db->loadObjectList();
    }

    // Helper functions

    // testing only, dump vars on html
    private function dump($data) {
        $out = $data;
        if (is_array($out))
            $out = implode(',', $out);
        echo $out. "<br>";
    // ec ho '<pre>' . print_r($out, true) . '</pre>';
    }
    
}
