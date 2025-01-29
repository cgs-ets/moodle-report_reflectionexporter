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

    public function display_table_summary($recordid, $ibform) {

        switch ($ibform) {
        case 'EE_RPPF':
            $this->display_ee_table_summary($recordid, $ibform);
            break;
        case 'TK_PPF':
            $this->display_tok_table_summary($recordid, $ibform);
            break;

        }
    }

    private function display_tok_table_summary($recordid, $ibform) {
        global $DB, $OUTPUT, $CFG, $PAGE;

        require_once($CFG->libdir . '/tablelib.php');

        $table = new \flexible_table('reflectionexporter-processes');
        $table->initialbars(true);
        $table->define_columns(['picture', 'fullname', 'chosentopic', 'firstinteraction', 'secondinteraction', 'thirdinteraction', 'wordcountandessay', 'teachercomment', 'Teacherppf']);
        $table->define_headers(['User picture', 'First name/Last name', 'Chosen topic','First Interaction', 'Second interaction', 'Third interaction', 'Word Count and Essay', 'Teacher comment', 'Teacher/PPF']);
        $table->define_baseurl(new moodle_url('/report/reflectionexporter/reflectionexporter_summary.php', ['id' => $this->id, 'cmid' => $this->cmid, 'rid' => $recordid, 'ibform' => $ibform, 'fs' => 1]));
        $table->sortable(true, 'lastname');
        $table->collapsible(true);

        $table->setup();


        if ($sort = $table->get_sql_sort()) {
            $sort = ' ORDER BY ' . $sort;
        }

        $ufields = \core_user\fields::for_name()->with_userpic()->get_required_fields();
        $ufields[0] = 'u.id';
        $ufields = implode(', ', $ufields);
        reflectionexportermanager::get_reflections_json($recordid);

        $ref = (reflectionexportermanager::get_reflections_json($recordid))->reflections_json;

        $ref = json_decode( $ref);

        $select = "SELECT DISTINCT $ufields, repdf.id as pdfid
                    FROM {user} u JOIN {report_reflec_exporter_pdf} repdf ON repdf.userid = u.id
                    WHERE repdf.refexid =:recordid";

        $params = ['recordid' => $recordid];

        $perpage = 15;

        $processes = $DB->get_records_sql($select . $sort, $params);

        $table->pagesize($perpage, count($processes));

        $offset = 1 * 15;
        $endposition = $offset + $perpage;
        $currentposition = 0;

        $renderer = $PAGE->get_renderer('report_reflectionexporter');

        foreach($processes as $process) {

            // if($currentposition == $offset && $offset < $endposition){

                $studentreflections = reflectionexportermanager::get_student_reflection_data($process->id, $ref);
                $refdata =  $studentreflections[$process->id];
                $picture = $renderer->user_picture($process);
                $fullname = $process->firstname . ' ' . $process->lastname;
                $pdfname = $process->lastname .'_' . $process->firstname . '_reflection';

                $context = new \stdClass();
                $context->recordid = $process->pdfid;
                $context->pdfname = $pdfname;
                $context->teachername = $refdata->teachersname;
                $context->actionurl = new moodle_url('/report/reflectionexporter/index.php', ['cid' => $this->id, 'cmid' => $this->cmid, 'pdfid' => $process->pdfid, 'stid' => $process->id, 'sd' => 1, 'ibform' => $ibform]);
                $link = $renderer->render_single_download($context);

                $inter1 = $renderer->style_submission_for_table_summary (reflectionexportermanager::cleanSpecialCharacters(($refdata->interactions[0])->onlinetext));
                $inter2 = $renderer->style_submission_for_table_summary (reflectionexportermanager::cleanSpecialCharacters(($refdata->interactions[1])->onlinetext));
                $inter3 = $renderer->style_submission_for_table_summary (reflectionexportermanager::cleanSpecialCharacters(($refdata->interactions[2])->onlinetext));
                $teachercomment = $renderer->style_submission_for_table_summary (reflectionexportermanager::cleanSpecialCharacters($refdata->teachercomments));

                $table->add_data([$picture,
                                    $fullname,
                                    $refdata->prescribedtitle,
                                    $inter1,
                                    $inter2,
                                    $inter3,
                                    $refdata->wordcount,
                                    $teachercomment,
                                    $link
                                ]);
                $currentposition++;
            // }

        }



        $table->finish_output();

    }

    private function display_ee_table_summary($recordid, $ibform) {
        global $DB, $OUTPUT, $CFG, $PAGE;

        require_once($CFG->libdir . '/tablelib.php');


        $table = new \flexible_table('reflectionexporter-processes');
        $table->initialbars(true);
        $table->define_columns(['picture', 'fullname',  'firstreflection', 'secondreflection', 'thirdreflection', 'teachercomment', 'ppf']);
        $table->define_headers(['User picture', 'First name/Last name', 'First reflection', 'Interim reflection', 'Final reflection', 'Teacher comment', 'PPF']);
        $table->define_baseurl(new moodle_url('/report/reflectionexporter/reflectionexporter_summary.php', ['id' => $this->id, 'cmid' => $this->cmid, 'rid' => $recordid, 'ibform' => $ibform, 'fs' => 1]));
        $table->sortable(true, 'lastname');
        $table->collapsible(true);

        $table->setup();

        if ($sort = $table->get_sql_sort()) {
            $sort = ' ORDER BY ' . $sort;
        }

        $ufields = \core_user\fields::for_name()->with_userpic()->get_required_fields();
        $ufields[0] = 'u.id';
        $ufields = implode(', ', $ufields);
        reflectionexportermanager::get_reflections_json($recordid);

        $ref = (reflectionexportermanager::get_reflections_json($recordid))->reflections_json;

        $ref = json_decode( $ref);

        $select = "SELECT DISTINCT $ufields, repdf.id as pdfid
                    FROM {user} u JOIN {report_reflec_exporter_pdf} repdf ON repdf.userid = u.id
                    WHERE repdf.refexid =:recordid";

        $params = ['recordid' => $recordid];

        $perpage = 15;

        $processes = $DB->get_records_sql($select . $sort, $params);


        $table->pagesize($perpage, count($processes));

        $offset = 1 * 15;
        $endposition = $offset + $perpage;
        $currentposition = 0;

        $renderer = $PAGE->get_renderer('report_reflectionexporter');

        foreach($processes as $process) {

            // if($currentposition == $offset && $offset < $endposition){

                $studentreflections = reflectionexportermanager::get_student_reflection_data($process->id, $ref);
                $refdata =  $studentreflections[$process->id];
                $picture = $renderer->user_picture($process);
                $fullname = $process->firstname . ' ' . $process->lastname;
                $pdfname = $process->lastname .'_' . $process->firstname . '_reflection';

                $context = new \stdClass();
                $context->recordid = $process->pdfid;
                $context->pdfname = $pdfname;
                $context->teachername = $refdata->teachersname;
                $context->actionurl = new moodle_url('/report/reflectionexporter/index.php', ['cid' => $this->id, 'cmid' => $this->cmid, 'pdfid' => $process->pdfid, 'stid' => $process->id, 'sd' => 1, 'ibform' => $ibform]);
                $link = $renderer->render_single_download($context);


                $ref1 = $renderer->style_submission_for_table_summary (reflectionexportermanager::cleanSpecialCharacters(($refdata->reflections[0])->onlinetext));
                $ref2 = $renderer->style_submission_for_table_summary (reflectionexportermanager::cleanSpecialCharacters(($refdata->reflections[1])->onlinetext));
                $ref3 = $renderer->style_submission_for_table_summary (reflectionexportermanager::cleanSpecialCharacters(($refdata->reflections[2])->onlinetext));
                $teachercomment = $renderer->style_submission_for_table_summary (reflectionexportermanager::cleanSpecialCharacters($refdata->teachercomments));


                $table->add_data([$picture,
                                    $fullname,
                                    $ref1,
                                    $ref2,
                                    $ref3,
                                    $refdata->teachercomments,
                                    $link
                                ]);
                $currentposition++;
            // }

        }


        $table->finish_output();
    }
}
