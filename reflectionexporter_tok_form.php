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
 * This form is for the  theory of knowledge - planning and progress form.
 *
 * @package    report
 * @subpackage reflectionexporter
 * @copyright  2022 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use report_reflectionexporter\reflectionexportermanager;

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/formslib.php");

class reflectionexporter_tok_form extends moodleform {

    public function definition() {
        global $PAGE, $USER;
        $mform = $this->_form; // Don't forget the underscore.

         // Hidden elements.
         $mform->addElement('hidden', 'cid', $this->_customdata['id']);
         $mform->settype('cid', PARAM_RAW); // To be able to pre-fill the form.

         $mform->addElement('hidden', 'cmid', $this->_customdata['cmid']);
         $mform->settype('cmid', PARAM_INT); // To be able to pre-fill the form.

         $mform->addElement('hidden', 'ibform', $this->_customdata['ibform']);
         $mform->settype('ibform', PARAM_TEXT); // To be able to pre-fill the form.

         $mform->addElement('hidden', 'aids', json_encode($this->_customdata['aids']));
         $mform->settype('aids', PARAM_RAW); // To be able to pre-fill the form.

        $mform->addElement('hidden', 'choices', json_encode($this->_customdata['choices']));
        $mform->settype('choices', PARAM_RAW); // To be able to pre-fill the form.

        $mform->addElement('hidden', 'userid', $this->_customdata['userid']);
        $mform->settype('userid', PARAM_RAW); // To be able to pre-fill the form.

        $assessarray = array();

        $results = $this->_customdata['aids']; // Assignments.

        $choices = $this->_customdata['choices'];

        $choicearray   = array();
        $choicearray[] = '';

        foreach ($choices as $choice) {
            $choicearray[$choice->id] = $choice->name;
        }

        // There are no choices to pick the title from. It can't go on.
        if (count($choicearray) == 1 ) { // Only have the empty value
            $message = get_string('nochoice', 'report_reflectionexporter');
            $buttonarray = array();
            $buttonarray[] = $mform->createElement('cancel', '', $message);
            $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);

        } else {

            $mform->addElement('header', 'general', get_string('general', 'form'));

            $mform->addElement(
                'filemanager',
                'tokattachment_filemanager', // It has to have the _filemanger in the name.
                get_string('tkattachment_filemanager', 'report_reflectionexporter'),
                null,
                array('subdirs' => 0,
                'maxbytes' => 5000000,
                'maxfiles' => 1,
                'accepted_types' => array('.pdf'))
            );

            $mform->addHelpButton('tokattachment_filemanager', 'tokattachment_filemanager', 'report_reflectionexporter');
            $mform->addRule('tokattachment_filemanager', null, 'required');

            // Session.
            $attributes = array('size' => '16');
            $mform->addElement('text', 'session', get_string('session', 'report_reflectionexporter'), $attributes);
            $mform->addRule('session', null, 'required');
            $mform->settype('session', PARAM_TEXT);


            // Teachers name
            $attributes = array('size' => '16');
            $mform->addElement('text', 'teachersname', get_string('teachersname', 'report_reflectionexporter'), $attributes);
            $mform->settype('teachersname', PARAM_TEXT);
            $mform->addRule('teachersname', null, 'required');
            $mform->setDefault('teachersname', "$USER->firstname $USER->lastname");


            // Group selection.
            $groups = reflectionexportermanager::get_groups_with_teachers($this->_customdata['id']);
            $grouparray = array();
            $grouparray[] = '';

            foreach ($groups as $groupid => $group) {
                $id = $groupid;
                $grouparray[$id] = $group->name;
                $data = new stdClass();
                $data->groupname = $group->name;
                $data->groupid = $groupid;
                $data->courseid = $this->_customdata['id'];
                $data->teachers = $group->teachers;
                $data->students = $group->students;
            }

            $mform->addElement('select', 'groupsallocated', get_string('groupsallocated', 'report_reflectionexporter'), $grouparray, ['class' => 'assessment-reflection-exporter']);
            $mform->getElement('groupsallocated')->setMultiple(false);
            $mform->addRule('groupsallocated', null, 'required');
            $mform->addHelpButton('groupsallocated', 'groupsallocated', 'report_reflectionexporter');

            // Prescribed title. We will use the choice activity to get the title
            $mform->addElement('select', 'titlechoiceid', get_string('prescribedtitle', 'report_reflectionexporter'), $choicearray, ['class' => 'assessment-reflection-exporter']);
            $mform->getElement('titlechoiceid')->setMultiple(false);
            $mform->addRule('titlechoiceid', null, 'required');
            $mform->addHelpButton('titlechoiceid', 'prescribedtitle', 'report_reflectionexporter');

            // Assessments.

            $assessessayarray[] = '';
            $assessarray[] = '';
            $assessarray2[] = '';
            $assessarray3[] = '';

            foreach ($results as $result) {
                $assessessayarray[$result->id] = $result->assignmentname;
                $assessarray[$result->id] = $result->assignmentname;
                $assessarray2[$result->id] = $result->assignmentname;
                $assessarray3[$result->id] = $result->assignmentname;
            }

            //  Assessment that has the essay and the word count.
            $mform->addElement('select', 'tokessay', get_string('tokessay', 'report_reflectionexporter'), $assessessayarray, ['class' => 'assessment-reflection-exporter']);
            $mform->getElement('tokessay')->setMultiple(false);
            $mform->addRule('tokessay', null, 'required');
            $mform->addHelpButton('tokessay', 'tokessay', 'report_reflectionexporter');

             // One for each iteraction because they could be in different order and I need a way to know which one is 1st, 2nd and 3rd.
            // 1st iteraction.
            $mform->addElement('select', 'interaction1', get_string('interaction1', 'report_reflectionexporter'), $assessarray, ['class' => 'assessment-reflection-exporter']);
            $mform->getElement('interaction1')->setMultiple(false);
            $mform->addRule('interaction1', null, 'required');
            $mform->addHelpButton('interaction1', 'interaction1', 'report_reflectionexporter');

            // 2nd iteraction.

            $mform->addElement('select', 'interaction2', get_string('interaction2', 'report_reflectionexporter'), $assessarray2);
            $mform->getElement('interaction2')->setMultiple(false);
            $mform->addRule('interaction2', null, 'required');
            $mform->addHelpButton('interaction2', 'interaction2', 'report_reflectionexporter');

            // 3rd iteraction.
            $mform->addElement('select', 'interaction3', get_string('interaction3', 'report_reflectionexporter'), $assessarray3);
            $mform->getElement('interaction3')->setMultiple(false);
            $mform->addRule('interaction3', null, 'required');
            $mform->addHelpButton('interaction3', 'interaction3', 'report_reflectionexporter');

            // Submit/cancel buttons.
            $buttonarray = array();
            $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('savechanges'));
            $buttonarray[] = $mform->createElement('cancel');
            $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
        }


    }

    // Custom validation should be added here.
    public function validation($data, $files) {

        $errors = parent::validation($data, $files);

        if ($data['interaction1'] == 0) {
            $errors['interaction1'] = get_string('interaction1error', 'report_reflectionexporter');
        }

        if ($data['interaction2'] == 0) {
            $errors['interaction2'] = get_string('interaction2error', 'report_reflectionexporter');
        }

        if ($data['interaction3'] == 0) {
            $errors['interaction3'] = get_string('interaction3error', 'report_reflectionexporter');
        }
        if ($data['titlechoiceid'] == 0) {
            $errors['titlechoiceid'] = get_string('titlechoiceiderror', 'report_reflectionexporter');
        }

        return $errors;
    }

}
