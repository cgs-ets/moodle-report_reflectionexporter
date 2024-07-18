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
 *
 * @package    report
 * @subpackage reflectionexporter
 * @copyright  2022 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_reflectionexporter\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_function_parameters;
use core_external\external_value;
use core_user_external;
use core_external\external_multiple_structure;
use report_reflectionexporter\reflectionexportermanager;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . "/user/lib.php");
require_once("$CFG->dirroot/user/externallib.php");

/**
 * Trait implementing the external function report_reflectionexporter_list_participants
 */
trait list_participants {

    /**
     * Returns description of method parameters
     *
     */
    public static function list_participants_parameters() {
        return new external_function_parameters(
            array('rid' => new external_value(PARAM_RAW, 'Row id of mdl_report_reflectionexporter'))
        );
    }

    public static function list_participants($rid) {
        global $COURSE;

        $context = \context_course::instance($COURSE->id);
        self::validate_context($context);

        // Parameters validation.
        self::validate_parameters(
            self::list_participants_parameters(),
            array(
                'rid' => $rid,
            )
        );

        $participants = reflectionexportermanager::get_students_from_export($rid);
        $userfields = user_get_default_fields();
        $details = [];
        foreach ($participants as $participant) {
            $details[] = reflectionexportermanager::get_user_details($participant, $userfields);
        }

        return $details;
    }

    /**
     * Describes the structure of the function return value.
     * @return external_multiple_structure
     */
    public static function list_participants_returns() {
        $userdescription = core_user_external::user_description();
        // List unneeded properties.
        $unneededproperties = [
            'auth', 'confirmed', 'lang', 'calendartype', 'theme', 'timezone', 'mailformat',
            'skype', 'yahoo', 'aim', 'msn', 'address', 'email', 'phone1', 'phone2', 'department', 'institution',
            'interests', 'firstaccess', 'lastaccess', 'suspended', 'customfields', 'preferences', 'recordid'
        ];
        // Remove unneeded properties for consistency with the previous version.
        foreach ($unneededproperties as $prop) {
            unset($userdescription->keys[$prop]);
        }

        $userdescription->keys['fullname']->type = PARAM_NOTAGS;
        $userdescription->keys['profileimageurlsmall']->required = VALUE_OPTIONAL;
        $userdescription->keys['profileimageurl']->required = VALUE_OPTIONAL;

        return new external_multiple_structure($userdescription);
    }
}
