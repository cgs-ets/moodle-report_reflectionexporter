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

trait get_pdfbase64 {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     *
     */
    public static function get_pdfbase64_parameters() {
        return new external_function_parameters(
            array(
                'recorid' => new external_value(PARAM_RAW, 'JSON with updated pdf encoded in base64'),
            )
        );
    }

    public static function get_pdfbase64($recorid) {
        global $COURSE;

        $context = \context_user::instance($COURSE->id);

        self::validate_context($context);

        // Parameters validation.
        self::validate_parameters(
            self::get_pdfbase64_parameters(),
            array(
                'recorid' => $recorid,
            )
        );

        $result = reflectionexportermanager::get_pdfbase64($recorid);
        return array(
            'pdfbase64' => $result,
        );
    }

    /**
     * Describes the structure of the function return value.
     * Returns the URL of the file for the student
     * @return external_single_structure
     *
     */
    public static function get_pdfbase64_returns() {
        return new external_single_structure(
            array(
                'pdfbase64' => new external_value(PARAM_TEXT, 'PDF in base64'),
            )
        );
    }
}
