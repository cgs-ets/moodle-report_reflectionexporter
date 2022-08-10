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
 * @package    report
 * @subpackage reflectionexporter
 * @copyright  2022 Veronica Bermegui
 */

namespace report_reflectionexporter\external;

defined('MOODLE_INTERNAL') || die();

use external_function_parameters;
use external_value;
use external_single_structure;
use report_reflectionexporter\reflectionexportermanager;

require_once($CFG->libdir . '/externallib.php');

trait get_reflections {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     *
     */
    public static function get_reflections_parameters() {
        return new external_function_parameters(
            array(
                'rid' => new external_value(PARAM_RAW, 'record id from mdl_report_reflectionexporter'),

            )
        );
    }

    public static function get_reflections($rid) {
        global $COURSE;

        $context = \context_user::instance($COURSE->id);

        self::validate_context($context);

        // Parameters validation.
        self::validate_parameters(
            self::get_reflections_parameters(),
            array(
                'rid' => $rid,
            )
        );
        $manager = new reflectionexportermanager();
        $result = $manager->get_reflections_json($rid);

        return array(
            'reflecjson' => $result->reflections_json,
            
        );
    }

    /**
     * Describes the structure of the function return value.
     * Returns the URL of the file for the student
     * @return external_single_structure
     *
     */
    public static function get_reflections_returns() {
        return new external_single_structure(
            array(
                'reflecjson' => new external_value(PARAM_TEXT, 'Reflections JSON'),
            )
        );
    }
}
