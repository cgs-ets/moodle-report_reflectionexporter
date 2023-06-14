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
 *
 * @package    report_reflectionexporter
 * @copyright  2022 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_reflectionexporter;

use context_course;
use moodle_url;
use stdClass;
use ZipArchive;

class reflectionexporterviewmanager {

    private $id; // Course id.
    private $cmid;


    public function __construct($id, $cmid) {
        $this->id = $id;
        $this->cmid = $cmid;
    }

    private function get_renderer() {
        global $PAGE;

        return $PAGE->get_renderer('report_reflectionexporter');
    }

    private function get_course_id() {
        return $this->id;
    }

    private function get_course_module_id() {
        return $this->cmid;
    }

    public function display_extended_essay_view($ibform) {
        $id   = $this->get_course_id();
        $cmid = $this->get_course_module_id();

        $existingprocurl = new moodle_url('/report/reflectionexporter/reflectionexporter_process.php',
                                        [
                                            'cid' => $id,
                                            'cmid' => $cmid,
                                            'n' => 0,
                                            'ibform' => $ibform
                                        ]);
        $newproc = new moodle_url('/report/reflectionexporter/reflectionexporter_new.php',
                                  [
                                    'cid' => $id,
                                    'cmid' => $cmid,
                                    'n' => 1,
                                    'ibform' => $ibform
                                ]);
        $dataobject = new stdClass();
        $dataobject->existingproc = $existingprocurl;
        $dataobject->newproc = $newproc;
        $dataobject->cid = $id;
        $dataobject->cmid = $cmid;
        $dataobject->ibform = $ibform;
        $dataobject->reporturl = new moodle_url('/report/reflectionexporter/index.php',
                                                [
                                                    'cid' => $id,
                                                    'cmid' => $cmid
                                                ]);
        $dataobject->courseurl = new moodle_url('/course/view.php', ['id' => $id]);
        $dataobject->reindexpage = new moodle_url('/report/reflectionexporter/index.php',
                                                 [
                                                    'cid' => $id,
                                                    'cmid' => $cmid
                                                ]);

        $this->get_renderer()->pick_action_icon($dataobject);
    }

    public function display_theory_of_knowledge_view($ibform) {

        $id   = $this->get_course_id();
        $cmid = $this->get_course_module_id();
        $existingprocurl = new moodle_url('/report/reflectionexporter/reflectionexporter_process.php',
                                        [
                                            'cid' => $id,
                                            'cmid' => $cmid,
                                            'n' => 0
                                        ]);
        $newproc = new moodle_url('/report/reflectionexporter/reflectionexporter_new.php',
                                 [
                                    'cid' => $id,
                                    'cmid' => $cmid,
                                    'n' => 1,
                                    'ibform' => $ibform
                                ]);
        $dataobject = new stdClass();
        $dataobject->existingproc = $existingprocurl;
        $dataobject->newproc = $newproc;
        $dataobject->cid = $id;
        $dataobject->cmid = $cmid;
        $dataobject->ibform = $ibform;
        $dataobject->reporturl = new moodle_url('/report/reflectionexporter/index.php',
                                                [
                                                    'cid' => $id,
                                                    'cmid' => $cmid
                                                ]);
        $dataobject->courseurl = new moodle_url('/course/view.php', ['id' => $id]);
        $dataobject->reindexpage = new moodle_url('/report/reflectionexporter/index.php',
                                                [
                                                    'cid' => $id,
                                                    'cmid' => $cmid
                                                ]);

        $this->get_renderer()->pick_action_icon($dataobject);
    }
}
