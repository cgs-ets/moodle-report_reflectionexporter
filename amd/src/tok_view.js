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
 * Javascript to handle the PDF view
 *
 * @module     report_reflectionexporter/tok_view
 * @copyright  2023 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'core/templates',
    'core/ajax',
], function (Templates, Ajax) {
    "use strict";

    var TokView = function () {
        var self = this;
        Templates.render('report_reflectionexporter/loading', {}).done(function (html, js) {
            // Update the page.
            $('[data-region="pdf-comment-container"]').fadeOut("fast", function () {
                Templates.replaceNodeContents($('[data-region="pdf-comment-container"]'), html, js);
                $('[data-region="pdf-comment-container"]').fadeIn("fast");

            }.bind(this));

            // document.getElementById('download').addEventListener('click', self._zipdownload);
            self._enableDownloadAndSummary();


        });

    }


    TokView.prototype._zipdownload = function (e) {
        document.getElementById('zipform').submit();
    }

    TokView.prototype._enableDownloadAndSummary = function () {
        const self = this;
        document.getElementById('download').addEventListener('click', self._zipdownload);

        // Only display if there are no processes.
        let notprocess = document.querySelector('.importing-animation').getAttribute('data-notprocess');
        notprocess = parseInt(notprocess, 10);
        console.log("NOT PROCESS");
        console.log(notprocess);

        if (notprocess > 0) {
            document.getElementById('summary').classList.remove('btn-summary-hidden');
            document.getElementById('summary').addEventListener('click', self._loadsummary);
        }
    }

    TokView.prototype._loadsummary = function () {

        Ajax.call([{
            methodname: "report_reflectionexporter_get_ommited",
            args: {
                recordid: document.querySelector('[data-region="viewer-navigation-panel"]').getAttribute('data-rid'),
            },
            done: function (response) {
                console.log(response);
                let context = {
                    students: JSON.parse(response.context)
                };
                console.log(context);

                Templates.render('report_reflectionexporter/students_table', context).done(function (html, js) {
                    Templates.replaceNodeContents(document.querySelector('div.omitted-table'), html, js);
                    var modal = document.getElementById("myModal");
                    var span = document.getElementsByClassName("close")[0];
                    modal.style.display = "block"
                    span.onclick = function () {
                        modal.style.display = "none";
                    }

                    // When the user clicks anywhere outside of the modal, close it.
                    window.onclick = function (event) {
                        if (event.target == modal) {
                            modal.style.display = "none";
                        }
                    }
                })


            },
            fail: function (reason) {
                console.log(reason);
            },
        },]);
    }

    return TokView;

});