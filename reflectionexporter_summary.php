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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Reflection Exporter Summary Report
 *
 * @package    report_reflectionexporter
 * @copyright  2025 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use report_reflectionexporter\reflectionexporterviewmanager;

require_once('../../config.php');
require_login();

$id                      = optional_param('id', 0, PARAM_INT); // Course ID.
$cmid                    = optional_param('cmid', 0, PARAM_INT); // Course module ID.
$form                    = required_param('ibform', PARAM_RAW); // IB form value from the selector.
$recorid                 = optional_param('rid', 0, PARAM_INT); // Record ID.

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/report/reflectionexporter/reflectionexporter_summary.php');
$PAGE->set_title(get_string('summary', 'report_reflectionexporter'));
$PAGE->set_heading(get_string('summary', 'report_reflectionexporter'));
$PAGE->add_body_class('report_reflectionexporter');

// Navigation.
$PAGE->navbar->add(get_string('heading', 'report_reflectionexporter') . " $form", new moodle_url('/report/reflectionexporter/reflectionexporter_display_selected.php', ['id' => $id, 'cmid' => $cmid, 'fs' => 1, 'ibform' => $form]));
$PAGE->navbar->add(get_string('summary', 'report_reflectionexporter'));

$viewmanager = new reflectionexporterviewmanager($id, $cmid);

echo $OUTPUT->header();

$viewmanager->display_table_summary($recorid, $form);

$PAGE->requires->js_call_amd('report_reflectionexporter/single_download', 'init');

echo $OUTPUT->footer();