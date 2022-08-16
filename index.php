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

require_once('../../config.php');
require_once('lib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('reflectionexporter_form.php');

$id                      = optional_param('cid', 0, PARAM_INT); // Course ID.
$cmid                    = optional_param('cmid', 0, PARAM_INT); // Course module ID.

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('invalidcourse');
}

require_login($course);
$context = context_course::instance($course->id);

require_capability('report/reflectionexporter:grade', $context);

$context = context_course::instance($course->id);
$PAGE->set_context($context);
$PAGE->set_url('/report/reflectionexporter/index.php', ['cid' => $id, 'cmid' => $cmid]);
$PAGE->add_body_class('report_reflectionexporter');
$PAGE->set_title(get_string('heading', 'report_reflectionexporter'));
$PAGE->set_heading(format_string($course->fullname, true, array('context' => $context)));
echo $OUTPUT->header();


if ($id == 0 || $id == 1) {  // $id = 1 is the main page.
    \core\notification::add(get_string('cantdisplayerror', 'report_reflectionexporter'), core\output\notification::NOTIFY_ERROR);
} else {
    $PAGE->set_title('Reflection exporter');

    $renderer = $PAGE->get_renderer('report_reflectionexporter');
    $existingprocurl = new moodle_url('/report/reflectionexporter/reflectionexporter_process.php', ['cid' => $id, 'cmid' => $cmid, 'n' => 0]);
    $newproc = new moodle_url('/report/reflectionexporter/reflectionexporter_new.php', ['cid' => $id, 'cmid' => $cmid, 'n' => 1]);

    $dataobject = new stdClass();
    $dataobject->existingproc = $existingprocurl;
    $dataobject->newproc = $newproc;
    $dataobject->cid = $id;
    $dataobject->cmid = $cmid;

    $renderer->pick_action_icon($dataobject);
}

echo $OUTPUT->footer();
