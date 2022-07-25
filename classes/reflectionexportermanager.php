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
use stdClass;

class reflectionexportermanager {


    // Get the course's assessments that have submissions and the submission type is onlinetext.
    public function get_submitted_assessments($courseid) {
        global $DB;

        $sql = "SELECT assign.id, assign.name AS 'assignmentname' FROM {assign} as assign 
                JOIN {assign_submission} as asub 
                ON assign.id = asub.assignment
                JOIN {assignsubmission_onlinetext} as onlinetxt ON assign.id = onlinetxt.assignment
                WHERE assign.course = ? AND asub.status = ?";

        $params = ['course' => $courseid, 'status' => 'submitted'];

        $results = $DB->get_records_sql($sql, $params);

        return $results;
    }

    // Get the reflections the student submitted.
    public function get_user_reflections($courseid, $assessids, $userid) {
        global $DB;

        $sql = "SELECT onlinetxt.*, asub.timemodified AS 'month', asub.userid
                FROM {assign} AS assign 
                JOIN {assign_submission} AS asub 
                ON assign.id = asub.assignment
                JOIN {assignsubmission_onlinetext} AS onlinetxt 
                ON assign.id = onlinetxt.assignment
                WHERE assign.course = ? 
                AND asub.status = ? 
                AND asub.assignment IN ( $assessids)
                AND asub.userid = ?;";

        $params = ['course' => $courseid, 'status' => 'submitted', 'userid' => $userid];
        $results = $DB->get_records_sql($sql, $params);
        $context = $context = context_course::instance($courseid);
        // Format the text to keep new lines. If the student added images, process the URL to avoid warning. 
        // The image wont be seen in the PDF.
        foreach ($results as $r) {
            
            $onlinetext = file_rewrite_pluginfile_urls($r->onlinetext, 'pluginfile.php', $context->id, 'assignsubmission_onlinetext', 'submissions_onlinetext', $r->id);
            $r->onlinetext =  json_encode( strip_tags(format_text($onlinetext, FORMAT_MARKDOWN)), JSON_HEX_QUOT | JSON_HEX_TAG);
            $r->month = date('F', $r->month);
        }

        $results = array_values($results);
        
        return $results;
    }

    // Get the reflections_json column.
    public function get_reflections_json($rid) {
        global $DB;
        $sql = "SELECT reflections_json FROM {report_reflectionexporter} WHERE id = ?";
        $params = ['id' => $rid];

        $result = $DB->get_record_sql($sql, $params);

        return $result;
    }

    // Get the details of students selected in the form.
    private function get_selected_students($studentids) {
        global $DB;

        $ids = implode(',', $studentids);

        $sql = "SELECT * FROM {user} WHERE id in ($ids)";

        $results = $DB->get_records_sql($sql);

        return $results;
    }

    
    public function save_pdfbase64($pdfs) {
        global $DB;
        $pdfs = json_decode($pdfs);
        $dataobjects = [];
        foreach($pdfs as $pdf) {
            $data = new stdClass();
            $data->userid = $pdf->uid;
            $data->pdf = $pdf->pdf;
            $dataobjects[] = $data;
        }
        
        error_log(print_r($dataobjects, true));

        error_log(print_r($DB->insert_records('mdl_report_reflec_exporter_pdf', $dataobjects), true));
    }

    // Call the proper function after the user submits the form.
    public function proccess_submission($fromform, $pdfcontent) {

        $reflections = $this->collect_reflections($fromform);

        // Call WS to put the text in the PDFS.
        // THE WS needs User, the 3 reflections, iniciales del profesor.
        //   $pdftemplates = $this->export_reflection_to_pdf($reflections, $pdfcontent);

    }

    /**
     * 1. Get the students in the course --> ✔
     * 2. Collect the student assessment submission  onlinetext sort by name TODO: this could change. --> ✔
     * 3. For each assessment the student has, put it in the file provided by the teacher
     */
    public function collect_reflections($data) {
        global $DB;
        $users = $this->get_selected_students($data->userid);

        $reflections = [];

        foreach ($users as $user) {
            $assessids = implode(',', $data->assessments);
            profile_load_custom_fields($user);
           
            $us = new stdClass();
            $us->id = $user->profile['IBCode']; // Personal code.
            $us->dp = $user->profile['Year'] == '11' ? 1 : '2';
            $us->firstname = $user->firstname;
            $us->lastname = $user->lastname;
            $us->si = $data->supervisorinitials;
            $us->reflections = $this->get_user_reflections($data->cid, $assessids, $user->id);

            $reflections[] = $us;
        }

        // Save the reflection data in the DB.
        $reflections = json_encode($reflections);
        $dataobject = new stdClass();
        $dataobject->reflections_json = $reflections;
        $rid = $DB->insert_record('report_reflectionexporter', $dataobject);

        return $rid;
    }

    // Get the active users in the course.
    public function get_active_users($courseid) {
        $context = context_course::instance($courseid);
        return  get_enrolled_users(
            $context,
            "mod/assign:submit",
            null,
            'u.*',
            'firstname',
            null,
            null,
            true
        );
    }
}
