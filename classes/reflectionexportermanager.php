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
use moodle_exception;
use moodle_url;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use stdClass;
use user_picture;
use ZipArchive;

use function report_reflectionexporter\wordcount\report_reflectionexporter_setup_wordcount_workbook;
require_once($CFG->dirroot . '/report/reflectionexporter/classes/report_reflectionexporter_countword_workbook.php');

class reflectionexportermanager {


    const STARTED = 'S';
    const FINISHED = 'F';
    const PDF_COMPLETED = 'C'; // Completed. Cant edit anymore.

    // Get the course's assessments that have submissions and the submission type is onlinetext.
    public static function get_submitted_assessments($courseid) {
        global $DB;

        $sql = "SELECT distinct assign.id, assign.name AS 'assignmentname'
                FROM {assign} as assign
                JOIN {assign_submission} as asub
                ON assign.id = asub.assignment
                JOIN {assignsubmission_onlinetext} as onlinetxt ON assign.id = onlinetxt.assignment
                WHERE assign.course = ? AND asub.status = ?";

        $params = ['course' => $courseid, 'status' => 'submitted'];

        $results = $DB->get_records_sql($sql, $params);

        return $results;
    }

    // Get the reflections the student submitted.
    public static function get_user_reflections($courseid, $assessids, $userid, $ibform, $wc = false) {
        global $DB;

        $sql = "SELECT onlinetxt.*, asub.timemodified AS 'month', asub.userid
                FROM {assignsubmission_onlinetext} AS onlinetxt
                JOIN {assign_submission} AS asub
                ON onlinetxt.submission = asub.id
                WHERE asub.status = ?
                AND asub.assignment IN ($assessids)
                AND asub.userid = ?;";

        $params = ['status' => 'submitted', 'userid' => $userid];
        $results = $DB->get_records_sql($sql, $params);
        $context = $context = context_course::instance($courseid);
        // Format has to me FORMAT_MOODLE otherwise the text might be too long and wont be display propery.
        // If the student added images, process the URL to avoid warning.
        // The image wont be seen in the PDF.
        foreach ($results as $r) {
            $onlinetext = file_rewrite_pluginfile_urls($r->onlinetext,
                                    'pluginfile.php',
                                    $context->id,
                                    'assignsubmission_onlinetext',
                                    'submissions_onlinetext',
                                    $r->id);
            $r->onlinetext = json_encode(strip_tags(format_text(str_replace('&nbsp;', ' ', $onlinetext), FORMAT_MOODLE)), JSON_HEX_QUOT | JSON_HEX_TAG);
            // Depending on the form the date has different format.
            switch ($ibform) {
                case 'EE_RPPF':
                    $r->month = date('F', $r->month);
                    break;
                case 'TK_PPF':
                    $r->month     = date("d/m/Y", $r->month);
                    if ($wc) {
                        $r->wordcount = strip_tags(format_text($onlinetext, FORMAT_MOODLE));
                    }
                    break;
            }
        }

        $results = array_values($results);

        return $results;
    }

    // Get the reflections_json column.
    public static function get_reflections_json($rid) {
        global $DB;
        $sql = "SELECT reflections_json FROM {report_reflectionexporter} WHERE id = ?";
        $params = ['id' => $rid];

        $result = $DB->get_record_sql($sql, $params);

        return $result;
    }

    // Get the no_reflections_json column.
    public static function get_no_reflections_json($rid) {
        global $DB;
        $sql = "SELECT no_reflections_json FROM {report_reflectionexporter} WHERE id = ?";
        $params = ['id' => $rid];

        $result = $DB->get_record_sql($sql, $params);

        return $result->no_reflections_json;
    }

    // Get the details of students selected in the form.
    public static function get_selected_students($studentids) {
        global $DB;

        $ids = implode(',', $studentids);

        $sql = "SELECT * FROM {user} WHERE id in ($ids)";

        $results = $DB->get_records_sql($sql);

        return $results;
    }

    // Save the PDF filled with the students reflections.
    public static function save_pdfbase64($pdfs) {
        global $DB;
        $pdfs = json_decode($pdfs);
        $dataobjects = [];

        foreach ($pdfs as $pdf) {
            $data = new stdClass();
            $data->userid = $pdf->uid;
            $data->courseid = $pdf->courseid;
            $data->refexid = $pdf->rid; // Id of mdl_report_reflectionexporter.
            $data->pdf = $pdf->pdf;
            $data->formname = $pdf->formname;
            $data->id = $DB->insert_record('report_reflec_exporter_pdf', $data, true);
            unset($data->pdf); // Dont send it back.
            $dataobjects[] = $data;
        }

        return $dataobjects;
    }

    // Update the PDF with the supervisor comment.
    public static function update_pdfbase64($pdfdata) {
        global $DB;

        $dataobject = new stdClass();
        $dataobject->id = $pdfdata->id;
        $dataobject->pdf = $pdfdata->pdf;
        $dataobject->status = self::PDF_COMPLETED;

        $DB->update_record('report_reflec_exporter_pdf', $dataobject);

        if ($pdfdata->finished == '1') {
            $status = self::FINISHED;
        } else {
            $status = self::STARTED;
        }

        self::update_process_status($pdfdata->refexid, $status);
    }

    public static function update_process_status($rid, $status) {
        global $DB;
        $dataobject = new stdClass();
        $dataobject->id = $rid;
        $dataobject->status = $status;
        $DB->update_record('report_reflectionexporter', $dataobject);
    }
    public static function get_pdfbase64($rid) {
        global $DB;

        $sql  = "SELECT pdf FROM {report_reflec_exporter_pdf} WHERE id = ?";
        $params = ['id' => $rid];

        $r = $DB->get_record_sql($sql, $params);

        return $r->pdf;
    }

    public static function generate_zip($data) {
        global $DB, $CFG;
        $data = json_decode($data);

        // Increase the server timeout to handle the creation and sending of large zip files.
        \core_php_time_limit::raise();

        $userids     = implode(',', array_column($data, 'userid'));
        $exportids   = implode(',', array_column($data, 'id'));        // Id from mdl_report_reflec_exporter_pdf.
        $refexpids   = array_column($data, 'refexid');  // Id from mdl_report_reflectionexporter.
        $refexpids   = $refexpids[count($refexpids) - 1];
        $courseid   = array_column($data, 'refexid');  // Id from mdl_report_reflectionexporter.
        $courseid   = $courseid[count($courseid) - 1];

        $sql = "SELECT  exp.*, u.lastname, u.firstname
                FROM mdl_user as u
                INNER JOIN mdl_report_reflec_exporter_pdf exp
                ON u.id = exp.userid
                WHERE u.id in ($userids) AND exp.id in ($exportids ) AND exp.refexid = $refexpids";

        $results = $DB->get_records_sql($sql);

        // Prepare Tmp File for Zip archive.
        $file = tempnam($CFG->tempdir, '/reflections');
        $zip = new ZipArchive();
        $zip->open($file, ZipArchive::OVERWRITE);

        foreach ($results as $result) {
            // Collect the pdfs.
            $filename = strtoupper($result->lastname) . '_' . $result->firstname . '_' .$result->formname .'.pdf';
            $zip->addFromString($filename, base64_decode($result->pdf));
        }

        // Close and send to users.
        $zip->close();
        $foldername = date('Y') . '_' .$result->formname . '.zip';
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Length: ' . filesize($file));
        header('Content-Disposition: attachment; filename=" ' . $foldername . '"');
        header('Pragma: public');
        readfile($file);
        unlink($file);

        // Update status.
        self::update_download_status($refexpids);

        die();
    }

    public static function generate_spreadsheet($data, $context, $course) {
        global $DB, $CFG;
        // Increase the server timeout to handle the creation and sending of large zip files.
        \core_php_time_limit::raise();

        $ref = self::get_reflections_json($data);
        $data = json_decode($ref->reflections_json); // Get the reflections_json column.
        $tempdir = make_temp_directory('report_reflectionexporter/spreadsheet');

        report_reflectionexporter_setup_wordcount_workbook($context, $data, $tempdir, $course);

        // Now make a zip file of the temp dir and then delete it.
        self::zip_spreadsheetworkbook();
    }

    /**
     * Creates a zip file with excel files in it
     */
    private static function zip_spreadsheetworkbook() {
        global $CFG;
        $foldertozip = $CFG->tempdir.'/report_reflectionexporter/spreadsheet';
        // Get real path for our folder.
        $rootpath = realpath($foldertozip);

        // Initialize archive object.
        $zip = new \ZipArchive();
        $filename = $CFG->tempdir.'/report_reflectionexporter/wordcount.zip';

        $zip->open( $filename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // Create recursive directory iterator.
        /** @var SplFileInfo[] $files */
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootpath), RecursiveIteratorIterator::LEAVES_ONLY);
        $filestodelete = [];

        foreach ($files as $name => $file) {
            // Skip directories (they would be added automatically).
            if (!$file->isDir()) {
                // Get real and relative path for current file.
                $filepath = $file->getRealPath();
                $relativepath = substr($filepath, strlen($rootpath) + 1);
                // Add current file to archive.
                $zip->addFile($filepath, $relativepath);
                $filestodelete[] = $filepath;
            }
        }

        if ($zip->numFiles > 0) {
            // Zip archive will be created only after closing object.
            $zip->close();

            foreach ($filestodelete as $file) {
                unlink($file);
            }

            header("Content-Type: application/zip");
            header("Content-Disposition: attachment; filename=wordcount.zip");
            header("Content-Length: " . filesize("$filename"));
            readfile("$filename");
            unlink("$filename");
        }

        die(); // If not set, a invalid zip file error is thrown.

    }


    public static function update_download_status($id) {
        global $DB;
        // Update status.
        $dataobject = new stdClass();
        $dataobject->id = $id;
        $dataobject->status = self::FINISHED;

        $DB->update_record('report_reflectionexporter', $dataobject);
    }

    public static function delete_process($rid) {
        global $DB;

        $r = $DB->delete_records('report_reflec_exporter_pdf', ['refexid' => $rid]);
        $r2 = $DB->delete_records('report_reflectionexporter', ['id' => $rid]);

        return $r == $r2;
    }

    // To display the table with the processes started but not finished.
    public static function get_process($ibform) {
        global $DB, $COURSE;

        $sql = "SELECT *
                FROM mdl_report_reflectionexporter
                WHERE courseid = ? AND formname = ?";
        $params = ['courseid' => $COURSE->id, 'formname' => $ibform];

        $results = array_values($DB->get_records_sql($sql, $params));

        return $results;
    }

    // To fill the pdfjson property in the template.
    public static function get_existing_proc($rid) {
        global $DB;
        $sql = "SELECT id, userid, courseid, refexid, status, formname
                FROM {report_reflec_exporter_pdf}
                WHERE refexid = ?";
        $params = ['refexid' => $rid];

        $results = array_values($DB->get_records_sql($sql, $params));

        return $results;
    }

    // Returns student records based on the reflection export created.
    public static function get_students_from_export($rid) {
        global $DB;
        $record = json_decode((self::get_reflections_json($rid))->{'reflections_json'});
        $uids = array_column($record, 'uid');
        $uids = implode(',', $uids);

        $sql = "SELECT *
                FROM {user}
                WHERE id IN ($uids)";

        $students = $DB->get_records_sql($sql);

        return $students;
    }

    // Teacher is filling the form onbehalf of other teacher(s).
    // Get the data needed: students ids, groups id, supervisor initials for each student.
    public static function process_groupselectionjson($groups) {
        $groups = json_decode($groups);
        $studentids = [];
        $supervisorids = []; // Map where index = student id, value = supervisor initial.
        foreach ($groups as $group) {
            foreach ($group->students as $student) {
                $studentids[] = $student->id;
                $supervisorids[$student->id] = $group->si;
            }
        }
        return [$studentids, $supervisorids];
    }
    // Collect the reflections the student selected in the form did.
    // Save data in report_reflectionexporter table.
    public static function collect_and_save_reflections($data) {
        global $DB;

        if (isset($data->onbehalf)) {
            list($data->refexporteruserid, $supervisorids) = self::process_groupselectionjson($data->groupselectionjson);
        }

        $users = self::get_selected_students($data->refexporteruserid);
        $reflections = [];
        $noreflections = [];
        $selectedorder = [$data->assessments1, $data->assessments2, $data->assessments3];

        foreach ($users as $user) {
            $assessids = implode(',', $selectedorder);
            profile_load_custom_fields($user);

            $us = new stdClass();
            $us->id = $user->profile['IBCode']; // Personal code.
            $us->dp = $user->profile['Year'] == '11' ? 1 : '2';
            $us->firstname = $user->firstname;
            $us->lastname = $user->lastname;
            $us->uid = $user->id;
            $us->si = isset($data->onbehalf) ? $supervisorids[$user->id] : $data->supervisorinitials;
            $ref = self::get_user_reflections($data->cid, $assessids, $user->id, $data->ibform);

            $us->reflections = self::map_assessment_order($ref, $selectedorder);

            if (count($us->reflections) < 3) {
                $us->missing = self::get_missing_assignments($us->reflections, $selectedorder);
                unset($us->reflections); // We dont need the reflection.
                $noreflections[]  = $us;
            } else {
                $reflections[] = $us;
            }
        }

        // Check that $reflections has something to process. In case they all fail, let the user know.
        if (count($reflections) == 0) {
            $rid = 0;
        } else {
            // Save the reflection data in the DB.

            $dataobject = new stdClass();
            $dataobject->reflections_json = json_encode($reflections);
            $dataobject->no_reflections_json = json_encode($noreflections);
            $dataobject->courseid = $data->courseid;
            $dataobject->timecreated = time();
            $dataobject->formname = $data->ibform;

            $rid = $DB->insert_record('report_reflectionexporter', $dataobject);
        }

        return $rid;
    }

    // Order the assesments based on the selection the teacher did on the form.
    private static function map_assessment_order($reflections, $order) {

        $reflectionsaux = [];

        foreach ($reflections as $reflection) {
            // Get the index to set the order.
            $key = array_search($reflection->assignment, $order);
            $reflectionsaux[$key] = $reflection;
        }

        return $reflectionsaux;
    }

    private static function get_missing_assignments($reflections, $assessids) {

        $missingassesments = '';
        $assignments = array_column($reflections, 'assignment');
        $missing = array_diff($assessids, $assignments);

        foreach ($missing as $m) {
            $missingassesments .= self::get_assessment_name($m) . ' ,';
        }

        $missingassesments = substr($missingassesments, 0, -1); // Remove last comma.
        return $missingassesments;
    }

    private static function get_assessment_name($assesmentid) {
        global $DB;

        $sql = "SELECT name FROM mdl_assign where id = ?;";
        $params = ['id' => $assesmentid];

        $result = $DB->get_record_sql($sql, $params);

        return $result->name;
    }

    // Get the pdf the user submitted in the form.
    public static function get_file_url($context, $rid, $ibform) {
        $fs = get_file_storage();
        $filearea = IB_FORMS_FILEAREA[$ibform];
        $files = $fs->get_area_files($context->id, 'report_reflectionexporter', $filearea, $rid);
        $filename = '';
        foreach ($files as $f) {
            $filename = $f->get_filename();
        }
        $url = moodle_url::make_pluginfile_url($context->id, 'report_reflectionexporter', $filearea, $rid, '/', $filename, false);

        return $url->__toString();
    }

    // Get the active users in the course.
    public static function get_active_users($courseid) {
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

    // Return groups that have members in it and it has at least a teacher.
    public static function get_groups_with_teachers($courseid) {
        $groups = groups_get_all_groups($courseid, 0, 0, 'g.*', true);
        $groupsaux = [];
        foreach ($groups as $group) {
            list($students, $teachers)  = self::get_students_teachers_in_group($group->members, $courseid);
            // Add the separated teachers and students.
            $group->students = $students;
            $group->teachers = $teachers;
            unset($group->members); // We dont need them anymore.
            if (count($teachers) > 0) {
                $groupsaux[$group->id] = $group;
            }
        }

        return $groupsaux;
    }


    // Traverse the group members and classify them in students and teachers.
    public static function get_students_teachers_in_group($users, $courseid) {
        global $DB;
        $context = context_course::instance($courseid);
        $teachers = [];
        $students = [];

        foreach ($users as $userid) {
            $roles = get_user_roles($context, $userid, true);
            foreach ($roles as $role) {
                if (in_array($role->roleid, [3, 4])) { // Role id = 3 --> Editing Teacher. Role id = 4 --> Non editing teacher.
                    $teachers[] = $userid;
                }

                if (in_array($role->roleid, [5])) { // Role id = 5 -->Student.
                    $students[] = $userid;
                }
            }
        }
        // Collect the data we need.
        if (count($students) > 0 && count($teachers)) {
            $students = implode(',', $students);
            $students = array_values($DB->get_records_sql("SELECT id, firstname, lastname FROM mdl_user WHERE id in ($students)"));
            $teachers = implode(',', $teachers);
            $teachers = array_values($DB->get_records_sql("SELECT id, firstname, lastname FROM mdl_user WHERE id in ($teachers)"));
        }

        return [$students, $teachers];
    }

    // Get the teachers details we will add in the textinputs in the form.
    public static function get_teacher_from_selector($data) {
        $context = context_course::instance($data->courseid);
        $users = get_users_by_capability($context, 'report/reflectionexporter:grade', 'u.id, u.firstname, u.lastname', 'u.lastname', '', $data->groupid);
        $userctx = [];

        foreach ($users as $user) {
            $firstname = substr($user->firstname, 0, 1);
            $lastname = substr($user->lastname, 0, 1);
            $user->si = "$firstname.$lastname";
            $user->userid = $user->id;
            $user->groupid = $data->groupid;
            $user->name = $data->groupname;

            $userctx[] = $user;
        }

        return $userctx;
    }

    public static function get_user_details($user, array $userfields = array()) {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . "/user/profile/lib.php"); // Custom field library.
        require_once($CFG->dirroot . "/lib/filelib.php");      // File handling on description and friends.

        $defaultfields = user_get_default_fields();

        if (empty($userfields)) {
            $userfields = $defaultfields;
        }

        foreach ($userfields as $thefield) {
            if (!in_array($thefield, $defaultfields)) {
                throw new moodle_exception('invaliduserfield', 'error', '', $thefield);
            }
        }

        // Make sure id and fullname are included.
        if (!in_array('id', $userfields)) {
            $userfields[] = 'id';
        }

        if (!in_array('fullname', $userfields)) {
            $userfields[] = 'fullname';
        }

        $userdetails = array();
        $userdetails['id'] = $user->id;

        if (in_array('username', $userfields)) {
            $userdetails['username'] = $user->username;
        }

        if (in_array('firstname', $userfields)) {
                $userdetails['firstname'] = $user->firstname;
        }
        if (in_array('lastname', $userfields)) {
                $userdetails['lastname'] = $user->lastname;
        }

        $userdetails['fullname'] = fullname($user);
        $userdetails['email'] = $user->email;

        if (in_array('customfields', $userfields)) {
            $categories = profile_get_user_fields_with_data_by_category($user->id);
            $userdetails['customfields'] = array();
            foreach ($categories as $categoryid => $fields) {
                foreach ($fields as $formfield) {
                    if ($formfield->is_visible() && !$formfield->is_empty()) {
                        // TODO: Part of MDL-50728, this conditional coding must be moved to
                        // proper profile fields API so they are self-contained.
                        // We only use display_data in fields that require text formatting.
                        if ($formfield->field->datatype == 'text' || $formfield->field->datatype == 'textarea') {
                            $fieldvalue = $formfield->display_data();
                        } else {
                            // Cases: datetime, checkbox and menu.
                            $fieldvalue = $formfield->data;
                        }

                        $userdetails['customfields'][] =
                            array('name' => $formfield->field->name, 'value' => $fieldvalue,
                                'type' => $formfield->field->datatype, 'shortname' => $formfield->field->shortname);
                    }
                }
            }
            // Unset customfields if it's empty.
            if (empty($userdetails['customfields'])) {
                unset($userdetails['customfields']);
            }
        }

        // Profile image.
        if (in_array('profileimageurl', $userfields)) {
            $userpicture = new \user_picture($user);
            $userpicture->size = 1; // Size f1.
            $userdetails['profileimageurl'] = $userpicture->get_url($PAGE)->out(false);
        }
        if (in_array('profileimageurlsmall', $userfields)) {
            if (!isset($userpicture)) {
                $userpicture = new user_picture($user);
            }
            $userpicture->size = 0; // Size f2.
            $userdetails['profileimageurlsmall'] = $userpicture->get_url($PAGE)->out(false);
        }

        return $userdetails;
    }

    // The prescribed title the student selects comes form a choice activity.
    public static function get_tok_prescribed_title($courseid, $users, $choiceid) {
        global $DB;

        $sql = "SELECT u.id AS 'userid', co.text AS 'prescribedtitle'
                FROM {choice} c
                JOIN {choice_options} co ON c.id = co.choiceid
                JOIN {choice_answers} ca ON c.id = ca.choiceid AND ca.optionid = co.id
                JOIN {user} u ON u.id = ca.userid
                WHERE c.course = ? AND u.id IN ($users) AND c.id = ?";

        $params = ['course' => $courseid, 'id' => $choiceid];

        $results = $DB->get_records_sql($sql, $params);

        return $results;
    }

    public static function get_tok_prescribed_title_choice_activity($courseid) {
        global $DB;

        $sql = "SELECT c.id, c.name
                FROM mdl_choice c
                WHERE c.course = ?";
             $params = ['course' => $courseid];

        $results = $DB->get_records_sql($sql, $params);

        return $results;
    }

    /**
     *  Process the Extended Essay (EE) export.
     */
    public static function process_ee_form($fromform, $id, $cmid) {
        $rid = self::collect_and_save_reflections($fromform);
        if ($rid == 0) { // No students reflections found with the data provided.
            redirect(new moodle_url('/report/reflectionexporter/index.php', ['cid' => $id, 'cmid' => $cmid, 'np' => 1]));
        } else {
            $fromform->rid = $rid;
            report_reflectionexporter_filemanager_postupdate($fromform);
            $params = array('cid' => $id, 'cmid' => $cmid, 'rid' => $rid, 'n' => 1, 'ibform' => $fromform->ibform);
            redirect(new moodle_url('/report/reflectionexporter/reflectionexporter_process.php', $params));
        }
    }

    /**
     *  Process the Extended Theory Of Knowledge.
     *  Get the title choice made by the students
     *  Get the 3 interactions from the assessment
     *  Get the feedback(s) comment given in the 3 assessments.
     */
    public static function process_tok_form($fromform, $id, $cmid) {
        $rid = self::collect_and_save_interactions($fromform, $id);
        if ($rid == 0) { // No students interactions found with the data provided.
            redirect(new moodle_url('/report/reflectionexporter/index.php', ['cid' => $id, 'cmid' => $cmid, 'np' => 1]));
        } else {
            $fromform->rid = $rid;
            report_reflectionexporter_tok_filemanager_postupdate($fromform);
            $params = array('cid' => $id, 'cmid' => $cmid, 'rid' => $rid, 'n' => 1, 'ibform' => $fromform->ibform);
            redirect(new moodle_url('/report/reflectionexporter/reflectionexporter_process.php', $params));
        }
    }

    private static function collect_and_save_interactions($data, $courseid) {
        global $DB, $CFG;
        $interactions   = [];
        $nointeractions = [];
        $selectedorder  = [$data->interaction1, $data->interaction2, $data->interaction3];
        $users          =  groups_get_members($data->groupsallocated);//self::get_active_users($courseid);
        $userids        = implode(',', array_keys($users));
        $titles         = self::get_tok_prescribed_title($courseid, $userids, $data->titlechoiceid);

        foreach ($users as $user) {
            $assessids = implode(',', $selectedorder);
            profile_load_custom_fields($user);

            $std                  = new stdClass();
            $std->id              = $user->profile['IBCode']; // Personal code.
            $std->uid             = $user->id;
            $std->firstname       = $user->firstname;
            $std->lastname        = $user->lastname;
            $std->session         = $data->session;
            $std->teachersname    = $data->teachersname;
            $std->prescribedtitle = isset($titles[$user->id]) ? ($titles[$user->id])->prescribedtitle : '';
            $std->schoolname      = $CFG->report_reflectionexporter_school_name;
            $std->schoolnumber    = $CFG->report_reflectionexporter_school_number;

            $ref = self::get_user_reflections($data->cid, $assessids, $user->id, $data->ibform);
            $std->interactions = self::map_assessment_order($ref, $selectedorder);

            // Get the text for the essay assessment. This has the word count.
            $essayref = self::get_user_reflections($data->cid, $data->tokessay, $user->id, $data->ibform, true);
            $std->wordcount = count($essayref) > 0 ? ($essayref[count($essayref) - 1])->wordcount : '';

            if (count($std->interactions) < 3) {
                $std->missing = self::get_missing_assignments($std->interactions, $selectedorder);
                unset($std->interactions); // We dont need the reflection.
                $nointeractions[]  = $std;
            } else {
                $interactions[] = $std;
            }
        }

        if (count($interactions) == 0) {
            $rid = 0;
        } else {
            // Save the reflection data in the DB.
            $dataobject = new stdClass();
            $dataobject->reflections_json = json_encode($interactions);
            $dataobject->no_reflections_json = json_encode($nointeractions);
            $dataobject->courseid = $data->courseid;
            $dataobject->formname = $data->ibform;
            $dataobject->userid = $data->userid;
            $dataobject->timecreated = time();

            $rid = $DB->insert_record('report_reflectionexporter', $dataobject);
        }

        return $rid;
    }

    /**
     * For TOK forms, the teacher can give feedback comments to the assessments
     * with the interactions.
     */
    private static function get_feedback_comments($assesmentids, $student) {
        global $DB;

        $sql = "SELECT ac.assignment, ac.commenttext
                FROM {assignfeedback_comments} ac
                JOIN {assign_grades} ag
                ON ac.assignment = ag.assignment
                WHERE  ag.userid = ?
                AND ac.assignment IN ($assesmentids);";

        $paramsarray = ['userid' => $student->uid];

        $results = $DB->get_records_sql($sql, $paramsarray);
        $comments = '';

        foreach ($results as $comment) {

            $comments .= strip_tags(format_text($comment->commenttext, FORMAT_MOODLE)) . PHP_EOL;
        }

        return $comments;

    }
}

