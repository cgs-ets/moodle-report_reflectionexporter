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
 *  External Web Service Template
 *
 * @package   report_reflectionexporter
 * @category
 * @copyright 2022 Veronica Bermegui
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_reflectionexporter\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use report_reflectionexporter\reflectionexportermanager;

require_once($CFG->libdir . '/externallib.php');

trait get_ommited {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     *
     */
    public static function get_ommited_parameters() {
        return new external_function_parameters(
            array(
                'recordid' => new external_value(PARAM_TEXT, 'Id of the record to check not processed'),
            )
        );
    }

    public static function get_ommited($recordid) {
        global $COURSE;

        $context = \context_user::instance($COURSE->id);

        self::validate_context($context);

        // Parameters validation.
        self::validate_parameters(
            self::get_ommited_parameters(),
            array(
                'recordid' => $recordid,
            )
        );

        $notprocessed = json_decode(reflectionexportermanager::get_no_reflections_json($recordid));
        $context = [];

        foreach ($notprocessed as $np) {
            $data = new \stdClass();
            $data->student = "$np->firstname $np->lastname";
            $data->ibcode = $np->id;
            $data->reason = $np->missing;
            $context[] = $data;
        }

        // Generate the context the template will need to display the table.
        $ctx = json_encode($context);
        return array(
            'context' => $ctx,

        );
    }

    /**
     * Describes the structure of the function return value.
     * Returns the URL of the file for the student
     * @return external_single_structure
     *
     */
    public static function get_ommited_returns() {
        return new external_single_structure(
            array(
                'context' => new external_value(PARAM_TEXT, 'Template context'),
            )
        );
    }
}
