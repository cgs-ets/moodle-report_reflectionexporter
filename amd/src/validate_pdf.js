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
 * Validate that the PDF provided is a form.
 * @package    report
 * @subpackage reflectionexporter
 * @copyright  2022 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'report_reflectionexporter/pdf-lib',
    'report_reflectionexporter/dispatchjob',
    'core/url'


], function (PDFLib, Dispatcher, URL) {
    "use strict";

    var ValidatePDF = function (data) {
        this._isForm(data);
    }


    ValidatePDF.prototype._isForm = async function (data) {

        const formPdfBytes = await fetch(data.fileurl).then((res) => res.arrayBuffer());
        const pdfDoc = await PDFLib.PDFDocument.load(formPdfBytes);
        const form = pdfDoc.getForm();
        const fields = form.getFields();

        if (fields.length == 0) {
            // take the user back.
            const link = URL.relativeUrl('/report/reflectionexporter/index.php', {
                cid: data.cid,
                cmid: data.cmid,
                rid: data.rid,
                wf: 1,
            });

            window.location.replace(link);
            return;
        } else {
            Dispatcher.init(JSON.parse(document.querySelector('.importing-animation').getAttribute('data-info')))
        }
    }




    return ValidatePDF;

});