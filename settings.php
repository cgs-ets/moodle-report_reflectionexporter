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
 * Settings for the reflectionexporter report
 *
 * @package    report
 * @subpackage reflectionexporter
 * @copyright  2021 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading('report_reflectionexporter', '', ''));
    $settings->add(new admin_setting_configtextarea('report_reflectionexporter_forms',
                    get_string('ibform', 'report_reflectionexporter'),
                    get_string('ibform_desc', 'report_reflectionexporter'),
                    'EE/RPPF'));
    $settings->add(new admin_setting_configtext('report_reflectionexporter_school_name',
                    get_string('schoolname', 'report_reflectionexporter'),
                    get_string('ibform_desc', 'report_reflectionexporter'),
                    ''));
    $settings->add(new admin_setting_configtext('report_reflectionexporter_school_number',
                    get_string('schoolnumber', 'report_reflectionexporter'),
                    get_string('ibform_desc', 'report_reflectionexporter'),
                    ''));
}
