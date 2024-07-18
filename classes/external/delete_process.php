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

trait delete_process {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     *
     */
    public static function delete_process_parameters() {
        return new external_function_parameters(
            array(
                'rid' => new external_value(PARAM_TEXT, 'Id of the record to delete'),
            )
        );
    }

    public static function delete_process($rid) {
        global $COURSE;

        $context = \context_user::instance($COURSE->id);

        self::validate_context($context);

        // Parameters validation.
        self::validate_parameters(
            self::delete_process_parameters(),
            array(
                'rid' => $rid,
            )
        );

        $r = reflectionexportermanager::delete_process($rid);
        $results = $r == true ? 'OK' : "NOT OK";
        return array(
            'result' => $results,

        );
    }

    /**
     * Describes the structure of the function return value.
     * Returns the URL of the file for the student
     * @return external_single_structure
     *
     */
    public static function delete_process_returns() {
        return new external_single_structure(
            array(
                'result' => new external_value(PARAM_TEXT, 'Save in DB status'),
            )
        );
    }
}
