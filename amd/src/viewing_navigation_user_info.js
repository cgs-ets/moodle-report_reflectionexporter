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
 * Javascript controller for the "User summary" panel at the top of the page.
 *
 * @module     report_reflectionexporter/viewing_navigation_user_info
 * @package    report_reflectionexporte
 * @class      UserInfo
 * @copyright  2016 Damyon Wiese <damyon@moodle.com>
 * @copyright  2022 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later 
 */
define(['jquery', 'core/notification', 'core/ajax', 'core/templates', 'report_reflectionexporter/view_pdf', 'report_reflectionexporter/viewer_panel'], function ($, notification, ajax, templates, viewPDF, ViewerPanel) {

    /**
     * UserInfo class.
     *
     * @class UserInfo
     * @param {String} selector The selector for the page region containing the user navigation.
     */
    var UserInfo = function (selector) {
        this._regionSelector = selector;
        this._region = $(selector);
        this._userCache = {};

        $(document).on('user-changed', this._refreshUserInfo.bind(this));
    };

    /** @type {String} Selector for the page region containing the user navigation. */
    UserInfo.prototype._regionSelector = null;

    /** @type {Array} Cache of user info contexts. */
    UserInfo.prototype._userCache = null;

    /** @type {JQuery} JQuery node for the page region containing the user navigation. */
    UserInfo.prototype._region = null;

    /** @type {Integer} Remember the last user id to prevent unnecessary reloads. */
    UserInfo.prototype._lastUserId = 0;

    /**
     * Get the assignment id
     *
     * @private
     * @method getRefExporterId
     * @return {Integer} assignment id
     */
    UserInfo.prototype.getRefExporterId = function () {
        return this._region.attr('data-rid');
    };

    /**
     * Get the user context - re-render the template in the page.
     *
     * @private
     * @method _refreshUserInfo
     * @param {Event} event
     * @param {Number} userid
     */
    UserInfo.prototype._refreshUserInfo = function (event, userid) {
        var promise = $.Deferred();

        // Put the current user ID in the DOM so yui can access it.
        this._region.attr('data-userid', userid);

        // Skip reloading if it is the same user.
        if (this._lastUserId == userid) {
            return;
        }
        this._lastUserId = userid;

        // First insert the loading template.
        templates.render('report_reflectionexporter/loading', {}).done(function (html, js) {
            // Update the page.
            this._region.fadeOut("fast", function () {
                templates.replaceNodeContents(this._region, html, js);
                this._region.fadeIn("fast");
            }.bind(this));

            if (userid < 0) {
                // Render the template.
                templates.render('report_reflectionexporter/viewing_navigation_no_users', {}).done(function (html, js) {
                    if (userid == this._lastUserId) {
                        // Update the page.
                        this._region.fadeOut("fast", function () {
                            templates.replaceNodeContents(this._region, html, js);
                            this._region.fadeIn("fast");
                        }.bind(this));
                    }
                }.bind(this)).fail(notification.exception);
                return;
            }

            if (typeof this._userCache[userid] !== "undefined") {
                promise.resolve(this._userCache[userid]);
            } else {
                // Load context from ajax.
                var rid = this.getRefExporterId();
                var requests = ajax.call([{
                    methodname: 'report_reflectionexporter_get_participant',
                    args: {
                        userid: userid,
                        refid: rid
                    }
                }]);

                requests[0].done(function (participant) {
                    if (!participant.hasOwnProperty('id')) {
                        promise.reject('No users');
                    } else {
                        this._userCache[userid] = participant;
                        promise.resolve(this._userCache[userid]);
                    }
                }.bind(this)).fail(notification.exception);
            }

            promise.done(function (context) {

                // Render the template.
                context.courseid = $('[data-region="viewer-navigation-panel"]').attr('data-courseid');

                if (context.user) {
                    context.identity = context.user.email;;
                    // Add profile image url to context.
                    if (context.user.profileimageurl) {
                        context.profileimageurl = context.user.profileimageurl;
                    }
                }

                templates.render('report_reflectionexporter/viewing_navigation_user_summary', context).done(function (html, js) {
                    // Update the page.
                    if (userid == this._lastUserId) {
                        this._region.fadeOut("fast", function () {
                            templates.replaceNodeContents(this._region, html, js);
                            this._region.fadeIn("fast");
                        }.bind(this));

                       

                    }
                }.bind(this)).fail(notification.exception);


            }.bind(this)).fail(function () {
                    // Render the template.
                    templates.render('report_reflectionexporter/viewing_navigation_no_users', {}).done(function (html, js) {
                        // Update the page.
                        this._region.fadeOut("fast", function () {
                            templates.replaceNodeContents(this._region, html, js);
                            this._region.fadeIn("fast");
                        }.bind(this));
                    }.bind(this)).fail(notification.exception);
                }
                .bind(this));
        }.bind(this)).fail(notification.exception);


    };

    return UserInfo;
});