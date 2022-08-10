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

use external_function_parameters;
use external_value;
use external_single_structure;
use core_user_external;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . "/user/lib.php");
require_once("$CFG->dirroot/user/externallib.php");

/**
 * Trait implementing the external function mod_googledocs_delete_files
 */
trait get_participant {

    /**
     * Returns description of method parameters
     *
     */
    public static function get_participant_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_RAW, 'user ID'),
                'refid' => new external_value(PARAM_RAW, 'row id from mdl_report_reflectionexporter'),
            )
        );
    }

    public static function get_participant($userid, $refid) {
        global $COURSE, $DB;

        $context = \context_course::instance($COURSE->id);
        self::validate_context($context);

        // Parameters validation.
        self::validate_parameters(
            self::get_participant_parameters(),
            array(
                'userid' => $userid,
                'refid' => $refid
            )
        );
        // Get the File and grading details.
        $sql = "SELECT ref.userid, u.firstname, 
                    u.lastname, ref.pdf, ref.courseid
                FROM mdl_report_reflec_exporter_pdf AS ref
                INNER JOIN mdl_user AS u ON ref.userid = u.id
                WHERE ref.refexid = :refid AND ref.userid = :userid";

        $params = ['refid' => $refid, 'userid' => $userid];;
        $results = $DB->get_records_sql($sql, $params);

        $file = new \stdClass();
        
        foreach ($results as $record) {
            $file->pdf = $record->pdf;
            $file->courseid = $record->courseid;
        }

        $participant = $DB->get_record('user', array('id' => $userid));
        $user = (object) user_get_user_details($participant);

        $result = [
            'id' => $user->id,
            'fullname' => $user->fullname,
            'pdf' => $file->pdf,
            'user' => $user
        ];

        return $result;
    }

    /**
     * Describes the structure of the function return value.
     * @return external_single_structures
     */
    public static function get_participant_returns() {
        $userdescription = core_user_external::user_description();
        $userdescription->default = [];
        $userdescription->required = VALUE_OPTIONAL;

        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'ID of the user'),
            'fullname' => new external_value(PARAM_NOTAGS, 'The fullname of the user'),
            'pdf' => new external_value(PARAM_RAW, 'PDF encoded in base64'),
            'user' => $userdescription
        ));
    }
}
