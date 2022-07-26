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
 * Display PDFs with reflections inserted in the right section.
 *
 * @package    report
 * @subpackage reflectionexporter
 * @copyright  2022 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use report_reflectionexporter\reflectionexportermanager;

require_once('../../config.php');
require_once('lib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('reflectionexporter_form.php');

$id                      = optional_param('cid', 0, PARAM_INT); // Course ID.
$cmid                    = optional_param('cmid', 0, PARAM_INT); // Course module ID.
$rid                     = required_param('rid', PARAM_INT); // id from mdl_report_reflectionexporter.

require_login();
admin_externalpage_setup('report_reflectionexporter', '', null, '', array('pagelayout' => 'report'));

$PAGE->set_title(get_string('heading', 'report_reflectionexporter'));
$PAGE->add_body_class('report_reflectionexporter');
$PAGE->set_pagelayout('embedded');

echo $OUTPUT->header();
$context = context_course::instance($id);

reflectionexportermanager::get_file_url($context, $rid);

$data = new stdClass();
$data->fileurl = reflectionexportermanager::get_file_url($context, $rid);;
$data->rid = $rid;
$data->cid = $id;

$PAGE->requires->js_call_amd('report_reflectionexporter/reflectionexporter', 'init', array($data));

$renderer = $PAGE->get_renderer('report_reflectionexporter');

$renderer->render_viewer($id);

echo $OUTPUT->footer();
