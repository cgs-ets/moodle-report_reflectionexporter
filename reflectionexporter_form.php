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

        // Supervisor initials.
        $attributes = array('size' => '6');
        $mform->addElement('text', 'supervisorinitials', get_string('supervisorinitials', 'report_reflectionexporter'), $attributes);
        $mform->settype('supervisorinitials', PARAM_TEXT);
        $mform->addRule('supervisorinitials', null, 'required');

        // Allocated students.
        //$manager = new reflectionexportermanager();
        reflectionexportermanager::get_active_users($this->_customdata['id']);
        $students =  reflectionexportermanager::get_active_users($this->_customdata['id']); //($manager->get_active_users($this->_customdata['id']));
        $studentsarray = array();
        foreach ($students as $uid => $student) {
            $studentsarray[$uid] = $student->firstname . ' ' . $student->lastname;
        }
        $options = array(
            'multiple' => true,
            'noselectionstring' => '',
        );
        $mform->addElement('autocomplete', 'userid', get_string('activeusers', 'report_reflectionexporter'), $studentsarray, $options);
        $mform->addRule('userid', null, 'required');
        // Assessments.
        $assessarray = array();
        $results = $this->_customdata['aids']; // Assignment ids.

        $assessarray[] = '';
        foreach ($results as $result) {
            $assessarray[$result->id] = $result->assignmentname;
        }

        $mform->addElement('select', 'assessments', get_string('assessments', 'report_reflectionexporter'), $assessarray);
        $mform->getElement('assessments')->setMultiple(true);
        $mform->addRule('assessments', null, 'required');
        $mform->addHelpButton('assessments', 'assessments', 'report_reflectionexporter');

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
    }

    // Custom validation should be added here.
    public function validation($data, $files) {

        $errors = parent::validation($data, $files);

        if (count($data['assessments']) == 0) {
            $errors['assessments'] = get_string('assessmenterror', 'report_reflectionexporter');
        }

        if (empty($data['supervisorinitials'])) {
            $errors['supervisorinitials'] = get_string('supervisorinitialserror', 'report_reflectionexporter');
        }

        return $errors;
    }
}
