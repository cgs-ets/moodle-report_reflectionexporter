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

$id                      = optional_param('cid', 0, PARAM_INT); // Course ID.
$cmid                    = optional_param('cmid', 0, PARAM_INT); // Course module ID.

require_login();
admin_externalpage_setup('report_reflectionexporter', '', null, '', array('pagelayout' => 'report'));

$PAGE->add_body_class('report_reflectionexporter');
$PAGE->set_title(get_string('heading', 'report_reflectionexporter'));

//$manager = new report_reflectionexporter\reflectionexportermanager();
$courseurl = new moodle_url('/course/view.php', array('id' => $id));
$aids = reflectionexportermanager::get_submitted_assessments($id);
$mform = new reflectionexporter_form(null, ['id' => $id, 'cmid' => $cmid, 'aids' => $aids]);;

if ($mform->is_cancelled()) {
    redirect($courseurl);
} else if ($fromform = $mform->get_data()) {
    $rid = reflectionexportermanager::collect_and_save_reflections($fromform);
    $fromform->rid = $rid;
    report_reflectionexporter_filemanager_postupdate($fromform);
    $params = array('cid' => $id, 'cmid' => $cmid, 'rid' => $rid);
    redirect(new moodle_url('/report/reflectionexporter/reflectionexporter_process.php', $params));
} else {
    $context = context_course::instance($id);
    $entry = report_reflectionexporter_filemanager_prep($context);
    $mform->set_data($entry);
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
