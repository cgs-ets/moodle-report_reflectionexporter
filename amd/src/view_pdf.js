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
 * @module     report_reflectionexporter/vew_pdf
 * @copyright  2022 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    "report_reflectionexporter/pdf",
    "report_reflectionexporter/pdf-lib",
    'core/templates',
    'core/ajax',
    'core/url'
], function (PDFJSLIB, PDFLib, Templates, Ajax, url) {
    "use strict";

    var ViewPDF = function () {
        $(document).on('user-changed', this._init.bind(this));
    }

    ViewPDF.prototype._init = function () {
        var self = this;

        Templates.render('report_reflectionexporter/loading', {}).done(function (html, js) {
            // Update the page.
            $('[data-region="pdf-comment-container"]').fadeOut("fast", function () {
                Templates.replaceNodeContents($('[data-region="pdf-comment-container"]'), html, js);
                $('[data-region="pdf-comment-container"]').fadeIn("fast");

            }.bind(this));

            const user = self._getUser();

            // Call the service that returns the PDF.
            Ajax.call([{
                methodname: "report_reflectionexporter_get_pdfbase64",
                args: {
                    recorid: user[0].id, // This is the id of the record that  in mdl_report_reflec_exporter_pdf
                },
                done: function (response) {

                    // Render the pdf container
                    const downloadurl = new URL(window.location.href);
                    self.pdfData = response.pdfbase64;
                    downloadurl.searchParams.append('d', 1);
                    self.completed = user[0].status == 'C' ? true : false;

                    const context = {
                        courseurl: document.getElementById("courseurl").getAttribute('href'),
                        downloadaction: downloadurl,
                        datajson: document.querySelector('.data-pdfjson').getAttribute('data-pdfs'),
                        completed: user[0].status == 'C' ? true : false
                    }

                    Templates.render('report_reflectionexporter/pdf_container', context).done(function (html, js) {

                        Templates.replaceNodeContents($('[data-region="pdf-comment-container"]'), html, js);

                        const pdfData = self.pdfData;
                        // The workerSrc property shall be specified.
                        PDFJSLIB.GlobalWorkerOptions.workerSrc = url.relativeUrl('/report/reflectionexporter/amd/src/pdf.worker.js');
                        var loadingTask = PDFJSLIB.getDocument({
                            data: atob(pdfData)
                        });
                        var currPage = 1;
                        var numPages = 0;
                        var thePDF = null;

                        loadingTask.promise.then(function (pdf) {

                            //Set PDFJS global object (so we can easily access in our page functions
                            thePDF = pdf;

                            //How many pages it has
                            numPages = pdf.numPages;

                            // In chase the PDF was rendered before
                            // deleteCanvas();

                            //Start with first page
                            pdf.getPage(1).then(handlePages).catch(error => console.log(error));
                        }, function (reason) {
                            console.error(reason);
                        });

                        async function handlePages(page) {
                            //This gives us the page's dimensions at full scale
                            var viewport = page.getViewport({
                                scale: 1.5
                            });
                            //We'll create a canvas for each page to draw it on

                            var canvas = document.createElement("canvas");
                            canvas.classList.add('pdf-page');
                            canvas.height = viewport.height;
                            canvas.width = viewport.width;
                            var context = canvas.getContext('2d');

                            // Draw it on the canvas
                            await page.render({
                                canvasContext: context,
                                viewport: viewport
                            });

                            //Add it to the web page
                            document.getElementById('viewer').appendChild(canvas);

                            //Move to next page
                            currPage++;

                            if (thePDF !== null && currPage <= numPages) {
                                thePDF.getPage(currPage).then(handlePages);
                            }
                        }

                        // Display the form or status
                        if (!self.completed) {
                            document.querySelector('form.reflection-comment-form').removeAttribute('hidden');
                            document.querySelector('button.save-show-next-btn').addEventListener('click', self._saveshownext.bind(self));
                            document.querySelector('button.save-exit-btn').addEventListener('click', self._savesandexit.bind(self));
                        } else {
                            document.querySelector('h4.reflection-completed').classList.add('reflection-completed-display');
                            document.querySelector('h4.reflection-completed').classList.remove('reflection-completed');

                            // Check if its the last student, if so, display the download button.
                            const select = document.querySelector("[data-action='change-user']");

                            if (select.options[select.selectedIndex].nextElementSibling == null) {
                                self._enableDownloadAndSummary();
                            }
                        }
                    });



                },
                fail: function (reason) {
                    console.log(reason);
                },
            }, ]);
        });
    }


    ViewPDF.prototype._getUser = function () {
        const userid = $('[data-action="change-user"]')[0].getAttribute('data-selected');
        const pdfjsons = JSON.parse(document.querySelector('div.data-pdfjson').getAttribute('data-pdfs'));
        const user = pdfjsons.filter(user => {
            if (user.userid == userid) return user;
        });
        return user;
    }

    ViewPDF.prototype._getUserId = function () {
        return $('[data-action="change-user"]')[0].getAttribute('data-selected');
    }

    ViewPDF.prototype._saveshownext = function (e) {

        e.preventDefault();
        console.log(e);
        this._save('shownext'); // TODO: Get the name of the button from the e object

    }

    // Save the data in BD
    ViewPDF.prototype._save = async function (btnClicked) {

        //Get the teacher comment from the textarea
        const commentEl = document.getElementById('comment');
        // Check it has content.
        if (commentEl.value.length === 0) {
            commentEl.classList.add('comment_error');
            return;
        } else {
            commentEl.classList.remove('comment_error');

            const pdfData = this.pdfData;
            const stringPdfToBinary = Uint8Array.from(atob(pdfData), (c) => c.charCodeAt(0));

            const pdfDoc = await PDFLib.PDFDocument.load(stringPdfToBinary);
            const form = pdfDoc.getForm();
            //Text12: Supervisor comments.
            const commentsupervisor = form.getField('Text12');
            commentsupervisor.setText(commentEl.value);

            // Flatten the form's fields. This makes the pdf uneditable.
            form.flatten();
            // Save the PDF with the teacher comment. Cant edit anymore
            const pdf = await pdfDoc.saveAsBase64();

            const user = this._getUser();
            const select = document.querySelector("[data-action='change-user']");

            const toUpdate = {
                id: user[0].id,
                userid: $('[data-action="change-user"]')[0].getAttribute('data-selected'),
                courseid: $('[data-region="user-info"]')[0].getAttribute('data-userid'),
                refexid: $('[data-region="user-info"]')[0].getAttribute('data-rid'),
                pdf: pdf,
                exit: '0',
                finished: select.options[select.selectedIndex].nextElementSibling == null ? '1' : '0'
            }
            if (btnClicked != 'shownext') { // CLicked on exit
                toUpdate.exit = '1';
            }

            // Update the pdf value in mdl_report_reflec_exporter_pdf
            this._updatePDFInDB(toUpdate);

        }
    }
    // Call WS to save pdf data in DB.
    ViewPDF.prototype._updatePDFInDB = function (record) {

        const self = this;
        const arg = JSON.stringify(record);
        const exit = record.exit;

        Ajax.call([{
            methodname: "report_reflectionexporter_update_pdfbase64",
            args: {
                pdf: arg,
            },
            done: function (response) {
                console.log(response);

                // if it comes from save and exit. redirect to the course.
                if (exit == '1') {
                    // This way we cant go back.
                    window.location.replace(document.getElementById("courseurl").getAttribute('href'));
                    return;
                }

                // update the pdfjson with the status, so it cant be edited anymore
                self._updatejson();

                const userid = document.querySelector("[data-action='change-user']").getAttribute('data-selected');
                const useridnumber = parseInt(userid, 10);
                const select = document.querySelector("[data-action='change-user']");

                document.getElementById('comment').value = ''; // Clear the textarea

                if (select.options[select.selectedIndex].nextElementSibling != null) { // Check we didnt reach the end.

                    const nextuser = select.options[select.selectedIndex].nextElementSibling.value;

                    select.setAttribute('data-selected', nextuser);
                    select.value = String(nextuser);

                    // Trigger user change with the id of the next user.
                    if (!isNaN(useridnumber) && useridnumber > 0) {
                        $(document).trigger('user-changed', nextuser);
                    }

                } else {
                    $(document).trigger('user-changed', userid);
                }



            },
            fail: function (reason) {
                console.log(reason);
            },
        }, ]);
    }

    ViewPDF.prototype._zipdownload = function (e) {
        document.getElementById('zipform').submit();
    }


    ViewPDF.prototype._savesandexit = function (e) {
        e.preventDefault();
        this._save('showexit');
    }

    ViewPDF.prototype._updatejson = function () {

        const json = JSON.parse(document.querySelector('.data-pdfjson').getAttribute('data-pdfs'));
        const userid = document.querySelector("[data-action='change-user']").getAttribute('data-selected');
        var i = 0;
        for (i; i < json.length; i++) {
            if (json[i].userid == userid) {
                json[i].status = 'C';
                break;
            }
        }

        document.querySelector('.data-pdfjson').setAttribute('data-pdfs', JSON.stringify(json));
    }

    ViewPDF.prototype._enableDownloadAndSummary = function () {
        const self = this;
        document.querySelector('div.next-action').classList.remove('next-action-hidden');
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

    ViewPDF.prototype._loadsummary = function () {

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
        }, ]);
    }

    return ViewPDF;

});