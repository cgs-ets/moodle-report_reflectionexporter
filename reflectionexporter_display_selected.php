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
 * Display the form to fill based on the the selection done in the index.php
 *
 * @package    report
 * @subpackage reflectionexporter
 * @copyright  2023 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use report_reflectionexporter\reflectionexporterviewmanager;

require_once('../../config.php');
require_once('lib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('reflectionexporter_form.php');

$id                      = optional_param('id', 0, PARAM_INT); // Course ID.
$cmid                    = optional_param('cmid', 0, PARAM_INT); // Course module ID.
$form                    = required_param('ibform', PARAM_RAW); // IB form value from the selector.


if (!$course = $DB->get_record('course', array('id' => $id))) {
    throw new moodle_exception('invalidcourse');
}

require_login($course);

$context = context_course::instance($course->id);

require_capability('report/reflectionexporter:grade', $context);

$context = context_course::instance($course->id);
$PAGE->set_context($context);
$PAGE->set_url('/report/reflectionexporter/reflectionexporter_display_selected.php', ['id' => $id, 'cmid' => $cmid]);
$PAGE->add_body_classes(['report_reflectionexporter', 'limitedwidth']);
$PAGE->set_title(get_string('heading', 'report_reflectionexporter'));
$PAGE->set_heading(format_string($course->fullname, true, array('context' => $context)));

echo $OUTPUT->header();

$viewmanager = new reflectionexporterviewmanager($id, $cmid);

$form = explode('_', $form);
$form = $form[count($form) - 1];
$form = IB_FORM_NAME[$form];
switch ($form) {
    case 'EE_RPPF':
        $viewmanager->display_extended_essay_view($form);
        break;
    case 'TK_PPF' :
        $viewmanager->display_theory_of_knowledge_view($form);
        break;
    default:
        break;
}

echo $OUTPUT->footer();
