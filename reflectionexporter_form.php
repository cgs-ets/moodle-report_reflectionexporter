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
 * Defines the APIs used by reflectionexporter report
 *
 * @package    report
 * @subpackage reflectionexporter
 * @copyright  2022 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/formslib.php");

use report_reflectionexporter\reflectionexportermanager;

class reflectionexporter_form extends moodleform {

    public function definition() {
        global $PAGE;
        $mform = $this->_form; // Don't forget the underscore.

        // Hidden elements.
        $mform->addElement('hidden', 'cid', $this->_customdata['id']);
        $mform->settype('cid', PARAM_RAW); // To be able to pre-fill the form.
        $mform->addElement('hidden', 'cmid', $this->_customdata['cmid']);
        $mform->settype('cmid', PARAM_INT); // To be able to pre-fill the form.
        $mform->addElement('hidden', 'aids', json_encode($this->_customdata['aids']));
        $mform->settype('aids', PARAM_RAW); // To be able to pre-fill the form.

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement(
            'filemanager',
            'attachment_filemanager',
            get_string('attachment_filemanager', 'report_reflectionexporter'),
            null,
            array('subdirs' => 0, 'maxbytes' => 5000000, 'maxfiles' => 1, 'accepted_types' => array('.pdf'))
        );

        $mform->addHelpButton('attachment_filemanager', 'attachment_filemanager', 'report_reflectionexporter');
        $mform->addRule('attachment_filemanager', null, 'required');

        // Ask if the user is going to generate the reflections for their student OR do it on behalf of others.
        // If its on behalf of others
        // Open the option to select the groups --> Each group will have the teacher responsible for the students in the group reflection.

        $mform->addElement('checkbox', 'onbehalf', get_string('onbehalf', 'report_reflectionexporter'));
        $mform->disabledIf('onbehalf','groupscount', 'eq', 0);
        $mform->addHelpButton('onbehalf', 'onbehalf', 'report_reflectionexporter');
        // Supervisor initials.  DEFAULT. Teacher is doing their job only.

        $attributes = array('size' => '6');
        $mform->addElement('text', 'supervisorinitials', get_string('supervisorinitials', 'report_reflectionexporter'), $attributes);
        $mform->settype('supervisorinitials', PARAM_TEXT);
        $mform->hideIf('supervisorinitials', 'onbehalf', 'checked');

        // Teacher is doing another teachers job.

        // Group selection.
        $groups = reflectionexportermanager::get_groups_with_teachers($this->_customdata['id']);
        $mform->addElement('text', 'groupscount', 'Count',['class' => 'reflection-exporter-group-count']);
        $mform->setType('groupscount', PARAM_RAW);
        $mform->setDefault('groupscount', count($groups));

        $grouparray = array();
        $grouparrayaux = array();
        $studentsingroups = array();
        $teachersingroups = array();

        foreach ($groups as $groupid => $group) {
            $id = $groupid;
            $grouparray[$id] = $group->name;
            $data = new stdClass();
            $data->groupname = $group->name;
            $data->groupid = $groupid;
            $data->courseid = $this->_customdata['id'];
            $data->teachers = $group->teachers;
            $data->students = $group->students;
            $grouparrayaux[] = $data;
        }

        // Check that there are groups created.
        if (count ($grouparray) > 0 ) {

            $select = $mform->addElement('select', 'coursegroups', get_string('coursegroups', 'report_reflectionexporter'), $grouparray);
            $select->setMultiple(true);
            $mform->addHelpButton('coursegroups', 'coursegroups', 'report_reflectionexporter');
            $mform->hideIf('coursegroups', 'onbehalf', 'notchecked');
        }

        // Add the Teachers found based on the groups.
        // Keep them all hidden unless the teacher picks the group this teacher belongs to.
        foreach ($grouparrayaux as $aux) {

            $details = $aux->teachers;
            foreach ($details as $detail) {
                $fieldname = "teacher_" . $aux->groupid . '_' . $detail->id;
                $si = substr($detail->firstname, 0, 1) . '.' . substr($detail->lastname, 0, 1);
                $label = get_string('teacheringroup', 'report_reflectionexporter', ['firstname' => $detail->firstname, 'lastname' => $detail->lastname, ]);

                $mform->addElement('text', $fieldname, $label, array('size' => '6', 'class' => 'teacher-initial-field-hide'));
                $mform->settype($fieldname, PARAM_TEXT);
                $mform->setDefault($fieldname, $si);
                $mform->hideIf($fieldname, 'onbehalf', 'notchecked');

                $detail->si = $si;
                $detail->groupid = $aux->groupid;
                $detail->students = $aux->students;
                $teachersingroups[] = $detail;
            }

            $stdaux = new stdClass();
            $stdaux->groupid = $aux->groupid;
            $stdaux->students = $aux->students;
            $studentsingroups[] = $stdaux;
        }

        // Keep a JSON to keep track of the groups selected.
        $mform->addElement('text', 'groupselectionjson', 'Group Selection JSON');
        $mform->settype('groupselectionjson', PARAM_TEXT);
        $mform->setDefault('groupselectionjson', '[]');
        $mform->disabledIf('groupselectionjson', 'onbehalf', 'notchecked');
        // Auxiliar input to keep a reference to the full list of teachers in the groups. This is used in the JS.
        $mform->addElement('text', 'groupselectionjsonaux', 'Group Selection JSON AUX');
        $mform->settype('groupselectionjsonaux', PARAM_TEXT);
        $mform->setDefault('groupselectionjsonaux', json_encode($teachersingroups));
        $mform->disabledIf('groupselectionjsonaux', 'onbehalf', 'notchecked');

        // Allocated students. Only if the teacher is filling the form on behalf of another teacher.
        reflectionexportermanager::get_active_users($this->_customdata['id']);

        $students = reflectionexportermanager::get_active_users($this->_customdata['id']);
        $studentsarray = array();

        foreach ($students as $uid => $student) {
            $studentsarray[$uid] = $student->firstname . ' ' . $student->lastname;
        }

        $options = array(
            'multiple' => true,
            'noselectionstring' => 'Select students',
            'valuehtmlcallback' => function ($value) {
                global $DB, $OUTPUT;
                $user = $DB->get_record('user', ['id' => (int)$value], '*', IGNORE_MISSING);
                if (!$user || !user_can_view_profile($user)) {
                    return false;
                }
                $details = reflectionexportermanager::get_user_details($user);
                return $OUTPUT->render_from_template(
                    'core_search/form-user-selector-suggestion',
                    $details
                );
            }
        );
        $mform->addElement('autocomplete', 'userid', get_string('activeusers', 'report_reflectionexporter'), $studentsarray, $options);
        $mform->hideIf('userid', 'onbehalf', 'checked');

        // Assessments. One for each reflection. As they could be in different order and I need a way to know which one is 1st, 2nd and 3rd
        $assessarray = array();
        $results = $this->_customdata['aids']; // Assignment ids.

        // 1st reflection
        $assessarray[] = '';
        foreach ($results as $result) {
            $assessarray[$result->id] = $result->assignmentname;
        }

        $mform->addElement('select', 'assessments', get_string('assessments', 'report_reflectionexporter'), $assessarray);
        $mform->getElement('assessments')->setMultiple(false);
        $mform->addRule('assessments', null, 'required');
        $mform->addHelpButton('assessments', 'assessments', 'report_reflectionexporter');

        // 2nd reflection

        $assessarray2[] = '';
        foreach ($results as $result) {
            $assessarray2[$result->id] = $result->assignmentname;
        }

        $mform->addElement('select', 'assessments2', get_string('assessments2', 'report_reflectionexporter'), $assessarray2);
        $mform->getElement('assessments2')->setMultiple(false);
        $mform->addRule('assessments2', null, 'required');
        $mform->addHelpButton('assessments2', 'assessments2', 'report_reflectionexporter');

        $assessarray3[] = '';
        foreach ($results as $result) {
            $assessarray3[$result->id] = $result->assignmentname;
        }

        $mform->addElement('select', 'assessments3', get_string('assessments3', 'report_reflectionexporter'), $assessarray3);
        $mform->getElement('assessments3')->setMultiple(false);
        $mform->addRule('assessments3', null, 'required');
        $mform->addHelpButton('assessments3', 'assessments3', 'report_reflectionexporter');

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);

        $PAGE->requires->js_call_amd('report_reflectionexporter/supervisor_initial_control', 'init', []);
    }

    // Custom validation should be added here.
    public function validation($data, $files) {

        $errors = parent::validation($data, $files);

        if ($data['assessments'] == 0) {
            $errors['assessments'] = get_string('assessmenterror', 'report_reflectionexporter');
        }
        if ($data['assessments2'] == 0) {
            $errors['assessments2'] = get_string('assessment2error', 'report_reflectionexporter');
        }
        if ($data['assessments3'] == 0) {
            $errors['assessments3'] = get_string('assessment3error', 'report_reflectionexporter');
        }

        if(!isset($data['onbehalf'])) {

            if (count($data['userid']) == 0) {
                $errors['userid'] = get_string('useridterror', 'report_reflectionexporter');
            }

            if (empty($data['supervisorinitials'])) {
                $errors['supervisorinitials'] = get_string('supervisorinitialserror', 'report_reflectionexporter');
            }
        }

        return $errors;
    }
}
