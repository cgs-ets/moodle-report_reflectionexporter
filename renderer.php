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
 * Renderer for core_grading subsystem
 *
 * @package    report_reflectionexporter
 * @copyright  2022 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use report_reflectionexporter\reflectionexportermanager;

defined('MOODLE_INTERNAL') || die();

class report_reflectionexporter_renderer extends plugin_renderer_base {

    /**
     * Renders the template action icon
     *
     * @param aray $url action URL
     * @param string $text action text
     * @param string $icon the name of the icon to use
     * @param string $class extra class of this action
     * @return string
     */
    public function pick_action_icon($dataobject) {
       
        $data['newicon'] = new moodle_url('/report/reflectionexporter/pix/icon.png');
        $data['existingicon'] = new moodle_url('/report/reflectionexporter/pix/continueproc.png');
        $data['deleteicon'] = new moodle_url('/report/reflectionexporter/pix/delete.png');
        $data['newproc'] = $dataobject->newproc;
        $procs = reflectionexportermanager::get_process();
        $data['processfound'] = count($procs) > 0;
        
        foreach($procs as $proc) {
            $pr = new stdClass();
            $pr->datecreated = userdate($proc->timecreated, get_string('strftimedatefullshort', 'core_langconfig'));
            $f = $proc->status == 'F' ? '1' : '0';
            $params = array('cid' => $dataobject->cid, 'cmid' => $dataobject->cmid, 'rid' => $proc->id, 'n' => 0, 'f' => $f);
            $pr->actionurl = new moodle_url('/report/reflectionexporter/reflectionexporter_process.php', $params);
            $pr->status = $proc->status == 'F' ? 'Finished' : 'Started';
            $pr->title = $proc->status == 'F' ? 'Show' : 'Continue';
            $pr->deleteurl = new moodle_url('/report/reflectionexporter/index.php', ['cid' => $dataobject->cid, 'cmid' => $dataobject->cmid]);;
            $pr->todelete =  $proc->id;
            $data['processes'] [] = $pr;
        }
        echo $this->output->render_from_template('report_reflectionexporter/pick', $data);
    }

    public function render_importing_process($data) {

        $info = new stdClass();
        $context = context_course::instance($data->cid);
        $info->message = 'Importing reflections to PDF. Please do not close the browser';
        $data->coursename = $context->get_context_name(false, false, true);
        $info->data =  json_encode($data);
     
        $info->notprocess = count(json_decode(reflectionexportermanager::get_no_reflections_json($data->rid)));
        $info->new = $data->new;

        echo $this->output->render_from_template('report_reflectionexporter/generating_pdf', $info);
    }

    public function render_viewer($udata) {
        $context = context_course::instance($udata->cid);
        $data = new stdClass();
        $data->courseid = $udata->cid;
        $data->coursename = $context->get_context_name();
        $data->showuseridentity = true;
        $data->reflecid = $udata->rid;
        $data->firstuserid = 0;

        echo $this->output->render_from_template('report_reflectionexporter/viewer', $data);
    }

    
}
