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
 * Services
 *
 * @package    report
 * @subpackage reflectionexporter
 * @copyright  2022 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'report_reflectionexporter_get_reflections' => [
        'classname' => 'report_reflectionexporter\external\api', // Class containing a reference to the external function.
        'methodname' => 'get_reflections', // External function name.
        'description' => 'Get record with information needed to fill a PDF', // Human readable description of the WS function.
        'type' => 'read', // DB rights of the WS function.
        'loginrequired' => true,
        'ajax' => true    // Is this service available to 'internal' ajax calls.
    ],
    'report_reflectionexporter_save_pdfbase64' => [
        'classname' => 'report_reflectionexporter\external\api', // Class containing a reference to the external function.
        'methodname' => 'save_pdfbase64', // External function name.
        'description' => 'Save PDF', // Human readable description of the WS function.
        'type' => 'write', // DB rights of the WS function.
        'loginrequired' => true,
        'ajax' => true    // Is this service available to 'internal' ajax calls.
    ],
    'report_reflectionexporter_get_pdfbase64' => [
        'classname' => 'report_reflectionexporter\external\api', // Class containing a reference to the external function.
        'methodname' => 'get_pdfbase64', // External function name.
        'description' => 'Get PDF', // Human readable description of the WS function.
        'type' => 'write', // DB rights of the WS function.
        'loginrequired' => true,
        'ajax' => true    // Is this service available to 'internal' ajax calls.
    ],
    'report_reflectionexporter_update_pdfbase64' => [
        'classname' => 'report_reflectionexporter\external\api', // Class containing a reference to the external function.
        'methodname' => 'update_pdfbase64', // External function name.
        'description' => 'Update PDF with supervisor comment', // Human readable description of the WS function.
        'type' => 'write', // DB rights of the WS function.
        'loginrequired' => true,
        'ajax' => true    // Is this service available to 'internal' ajax calls.
    ],
    'report_reflectionexporter_list_participants' => [
        'classname' => 'report_reflectionexporter\external\api', // Class containing a reference to the external function.
        'methodname' => 'list_participants', // External function name.
        'description' => 'Returns the students to display in the reflection viewer selector', // Human readable description of the WS function.
        'type' => 'read', // DB rights of the WS function.
        'loginrequired' => true,
        'ajax' => true    // Is this service available to 'internal' ajax calls.
    ],
    'report_reflectionexporter_get_participant' => [
        'classname' => 'report_reflectionexporter\external\api', // Class containing a reference to the external function.
        'methodname' => 'get_participant', // External function name.
        'description' => 'Returns the student to display in the viewer', // Human readable description of the WS function.
        'type' => 'read', // DB rights of the WS function.
        'loginrequired' => true,
        'ajax' => true    // Is this service available to 'internal' ajax calls.
    ],

];
