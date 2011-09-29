<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Recommender block libaries
 *
 * @package    blocks
 * @subpackage recommender
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/recommender/services/lib.php');

/**
 * Get all services, create instances of each
 *  and check if they have extra controls
 *
 * @param  object $course   The course object
 * @return mixed  $services Array of objects containing information on services
 */
function get_recommender_services($course) {
    global $CFG;

    static $services = array();

    // we can't go any further with no $course
    if (!is_object($course)) {
        throw new block_recommender_exception('course object required');
    }

    // see if we have run it already
    if (!empty($services)) {
        return $services;
    }

    $pluginconfig = get_config('block_recommender');

    foreach (get_recommender_services_list() as $servicename) {
        $service = get_service($servicename, $course, $pluginconfig);
        // check if we have something to display.
        if ($service) {
            $services[$servicename] = $service;
        }
    }
    return $services;
}

/**
 *  Get service object
 *
 * @param  string $servicename   The name of the service
 * @param  object $course   The course object
 * @param  object $config   Optional config object passed in to reduce get_config calls
 * @return object $service  Objects containing information on service or false if service
 *                           is not permitted to view or no content is expectd.
 */
function get_service($servicename, $course, $pluginconfig = null) {

    // populate service configuration
    if ($pluginconfig === null) {
        $pluginconfig = get_config('block_recommender');
    }

    if (!$pluginconfig->{$servicename.'_enabled'}) {
        return false;
    }
    $service = new stdClass();
    $service->name = $servicename;
    $serviceclass = 'block_recommender_service_' . $servicename;
    $sclass = new $serviceclass($course, $pluginconfig);
    if (!$sclass->has_service_capability('view') || !($sclass->has_content()
        || ($sclass->displayaddlink && $sclass->has_service_capability('add')))) {
        return false;
    }
    $service->object = $sclass;

    return $service;
}
/**
 * Get the simple list of all services
 * This also validates class existance
 *
 * @return array  $services Array services names
 */
function get_recommender_services_list() {
    global $CFG;

    static $services = array();

    // see if we have run it already
    if (!empty($services)) {
        return $services;
    }

    $servicesdir = $CFG->dirroot.'/blocks/recommender/services/';

    $plugins = get_list_of_plugins('', '', $servicesdir);

    foreach ($plugins as $plugin) {
        $servicefile = $servicesdir . $plugin. '/lib.php';
        if (is_readable($servicefile)) {
            include_once($servicefile);
            $serviceclass = 'block_recommender_service_' . $plugin;
            if (class_exists($serviceclass)) {
                $services[] = $plugin;
            } else {
                throw new block_recommender_exception(get_string('errorcallingservice',
                            'block_recommender', $plugin));
            }
        } else {
            throw new block_recommender_exception(get_string('errornosuchservice',
                        'block_recommender', $plugin));
        }
    }

    return $services;
}

/*
 * Exceptions
 */

/**
 * Base Block Recommender exception
 */
class block_recommender_exception extends moodle_exception {

}

/**
 * AJAX exception
 *
 * This exception is typically thrown when an AJAX script attempts to use an invalid command
 */
class block_recommender_ajax_exception extends block_recommender_exception {

}
