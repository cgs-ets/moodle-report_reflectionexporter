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
 *
 * @package    report
 * @subpackage reflectionexporter
 * @copyright  2022 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'core/ajax',

], function (Ajax) {
    "use strict";

    var Delete = function () {

        if (document.querySelector("tbody") != null) {

            this._addEventListeners();
        }
    }



    Delete.prototype._addEventListeners = function () {
        const self = this;
        const table = document.querySelector("tbody");
        for (const row of table.rows) {
            const l = row.cells.length - 1;
            const cl = (row.cells[l]).children.length - 2;
            const aElement = row.cells[l].children[cl];
            console.log(aElement);
            aElement.setAttribute('data-row-index', row.rowIndex);
            aElement.addEventListener('click', self._deleteService.bind(this));
        }
    }

    Delete.prototype._deleteService = function (e) {

        const self = this;

        self.rowIndex = e.srcElement.parentElement.parentElement.parentElement.rowIndex - 1;

        // Call the service

        //Hide the bin and show the processing
        e.srcElement.parentElement.classList.add('deleting');
        e.srcElement.parentElement.nextElementSibling.classList.remove('deleting');

        Ajax.call([{
            methodname: "report_reflectionexporter_delete_process",
            args: {
                rid: e.target.getAttribute('data-to-delete')
            },
            done: function (response) {
                console.log(response);
                // remove table row

                document.querySelector("tbody").deleteRow(self.rowIndex);

                // check if its the last row. if so, remove the table completely
                if (document.querySelector("tbody").rows.length == 0) {
                    document.querySelector('div.select-work').classList.add('deleting');
                }

            },
            fail: function (reason) {
                console.log(reason);
            }
        }]);

    }



    return Delete;

});