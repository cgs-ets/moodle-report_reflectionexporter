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
 * Based on the ibform passed, call the appropiate file
 *
 * @package    report
 * @subpackage reflectionexporter
 * @copyright  2023 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'report_reflectionexporter/reflectionexporter',
    'report_reflectionexporter/tokexporter',

], function (ReflectionExporter, TOKexporter) {

    function init(data) {
        var control = new Controls(data);
        control.main();
    }

    function Controls(data) {
        this.data = data;
    }

    Controls.prototype.main = function () {
        console.log(this.data);
        switch (this.data.ibform) {
            case 'EE_RPPF':
                ReflectionExporter.init(this.data);
                break;
            case 'TK_PPF':
                TOKexporter.init(this.data);
                break;

            default:
                break;
        }

    }

    return {
        init: init,
    };
})