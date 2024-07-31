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
 * A report to collect students reflections and export them into PDFs.
 *
 * @package    report
 * @subpackage reflectionexporter
 * @copyright  2021 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use report_reflectionexporter\reflectionexportermanager;

require_once('../../config.php');
require_once('lib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('reflectionexporter_form.php');
require_once('reflectionexporter_tok_form.php');

$id                      = optional_param('cid', 0, PARAM_INT); // Course ID.
$cmid                    = optional_param('cmid', 0, PARAM_INT); // Course module ID.
$ibform                  = required_param('ibform', PARAM_RAW); // Course module ID.

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourse');
}

require_login($course);

$context = context_course::instance($course->id);

require_capability('report/reflectionexporter:grade', $context);

$PAGE->set_context($context);
$PAGE->set_url('/report/reflectionexporter/reflectionexporter_new.php', ['cid' => $id, 'cmid' => $cmid]);
$PAGE->add_body_classes(['report_reflectionexporter','limitedwidth']);
$PAGE->set_title(get_string('heading', 'report_reflectionexporter'));
$PAGE->set_heading(format_string($course->fullname, true, array('context' => $context)));

$courseurl = new moodle_url('/course/view.php', array('id' => $id));
$aids = reflectionexportermanager::get_submitted_assessments($id);

switch ($ibform) {
    case 'EE_RPPF':
        $mform = new reflectionexporter_form(null, ['id' => $id,
                                                    'cmid' => $cmid,
                                                    'aids' => $aids,
                                                    'ibform' => $ibform]);
        break;
    case 'TK_PPF':
        $choices = reflectionexportermanager::get_tok_prescribed_title_choice_activity($id);
        $mform = new reflectionexporter_tok_form(null, ['id' => $id,
                                                        'cmid' => $cmid,
                                                        'aids' => $aids,
                                                        'choices' => $choices,
                                                        'ibform' => $ibform,
                                                        'userid' => $USER->id]);
        break;
}


if ($mform->is_cancelled()) {
    redirect(new moodle_url('/report/reflectionexporter/index.php', ['cid' => $id, 'cmid' => $cmid]));
} else if ($fromform = $mform->get_data()) {
    $fromform->courseid = $id;

    switch ($fromform->ibform) {
        case 'EE_RPPF':
            reflectionexportermanager::process_ee_form($fromform, $id, $cmid);
            break;
        case 'TK_PPF':
            reflectionexportermanager::process_tok_form($fromform, $id, $cmid);
            break;
    }

} else {
    $context = context_course::instance($id);
    $entry = report_reflectionexporter_filemanager_prep($context, $ibform);
    $mform->set_data($entry);
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
