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
 * Javascript to handle changing users via the user selector in the header.
 *
 * @module     report_reflectionexporter/grading_navigation
 * @copyright  2016 Damyon Wiese <damyon@moodle.com>
 * @copyright  2022 Veronica Bermegui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/notification', 'core/str', 'core/ajax', 'report_reflectionexporter/grading_form_change_checker'],
    function ($, notification, str, ajax, checker) {

        /**
         * ViewingNavigation class.
         *
         * @class report_reflectionexporter/viewing_navigation
         * @param {String} selector The selector for the page region containing the user navigation.
         */
        var ViewingNavigation = function (selector) {
            this._regionSelector = selector;
            this._region = $(selector);
            this._filters = [];
            this._users = [];
            this._filteredUsers = [];
            this._lastXofYUpdate = 0;
            this._firstLoadUsers = true;
        
            // Get the current user list from a webservice.
            this._loadAllUsers();

            // We do not allow navigation while ajax requests are pending.
            // Attach listeners to the select and arrow buttons.

            this._region.find('[data-action="previous-user"]').on('click', this._handlePreviousUser.bind(this));
            this._region.find('[data-action="next-user"]').on('click', this._handleNextUser.bind(this));
            this._region.find('[data-action="change-user"]').on('change', this._handleChangeUser.bind(this));

            $(document).on('user-changed', this._refreshSelector.bind(this));
            $(document).on('done-saving-show-next', this._handleNextUser.bind(this));

            var userid = $('[data-region="viewing-navigation-panel"]').data('first-userid');
            if (userid) {
                this._selectUserById(userid);
            }

            $(document).bind("start-loading-user", function () {
                this._isLoading = true;
            }.bind(this));
            $(document).bind("finish-loading-user", function () {
                this._isLoading = false;
            }.bind(this));
        };

        /** @property {Boolean} Boolean tracking active ajax requests. */
        ViewingNavigation.prototype._isLoading = false;

        /** @property {String} Selector for the page region containing the user navigation. */
        ViewingNavigation.prototype._regionSelector = null;

        /** @property {Array} The list of active filter keys */
        ViewingNavigation.prototype._filters = null;

        /** @property {Array} The list of users */
        ViewingNavigation.prototype._users = null;

        /** @property {JQuery} JQuery node for the page region containing the user navigation. */
        ViewingNavigation.prototype._region = null;

        /** @property {String} Last active filters */
        ViewingNavigation.prototype._lastFilters = '';

        /**
         * Load the list of all users for this assignment.
         *
         * @private
         * @method _loadAllUsers
         * @return {Boolean} True if the user list was fetched.
         */
        ViewingNavigation.prototype._loadAllUsers = function () {
            var select = this._region.find('[data-action=change-user]');
            var assignmentid = select.attr('data-assignmentid');
            var groupid = select.attr('data-groupid');

            ajax.call([{
                methodname: 'report_reflectionexporter_list_participants',
                args: {
                    assignid: assignmentid,
                    groupid: groupid,
                    filter: '',
                    onlyids: true,
                    tablesort: true
                },
                done: this._usersLoaded.bind(this),
                fail: notification.exception
            }]);
            return true;
        };

        /**
         * Call back to rebuild the user selector and x of y info when the user list is updated.
         *
         * @private
         * @method _usersLoaded
         * @param {Array} users
         */
        ViewingNavigation.prototype._usersLoaded = function (users) {
            this._firstLoadUsers = false;
            this._filteredUsers = this._users = users;
            if (this._users.length) {

            } else {
                this._selectNoUser();
            }
            this._triggerNextUserEvent();
        };

        /**
         * Close the configure filters panel if a click is detected outside of it.
         *
         * @private
         * @method _checkClickOutsideConfigureFilters
         * @param {Event} event
         */
        ViewingNavigation.prototype._checkClickOutsideConfigureFilters = function (event) {
            var configPanel = this._region.find('[data-region="configure-filters"]');

            if (!configPanel.is(event.target) && configPanel.has(event.target).length === 0) {
                var toggleLink = this._region.find('[data-region="user-filters"]');

                configPanel.hide();
                configPanel.attr('aria-hidden', 'true');
                toggleLink.attr('aria-expanded', 'false');
                $(document).unbind('click.report_reflectionexporter_grading_navigation');
            }
        };

        /**
         * Close the configure filters panel if a click is detected outside of it.
         *
         * @private
         * @method _updateFilterPreference
         * @param {Number} userId The current user id.
         * @param {Array} filterList The list of current filter values.
         * @param {Array} preferenceNames The names of the preferences to update
         * @return {Promise} Resolved when all the preferences are updated.
         */
        ViewingNavigation.prototype._updateFilterPreferences = function (userId, filterList, preferenceNames) {
            var preferences = [],
                i = 0;

            if (filterList.length == 0 || this._firstLoadUsers) {
                // Nothing to update.
                var deferred = $.Deferred();
                deferred.resolve();
                return deferred;
            }
            // General filter.
            // Set the user preferences to the current filters.
            for (i = 0; i < filterList.length; i++) {
                var newValue = filterList[i];
                if (newValue == 'none') {
                    newValue = '';
                }

                preferences.push({
                    userid: userId,
                    name: preferenceNames[i],
                    value: newValue
                });
            }

        };

        /**
         * Select no users, because no users match the filters.
         *
         * @private
         * @method _selectNoUser
         */
        ViewingNavigation.prototype._selectNoUser = function () {
            // Detect unsaved changes, and offer to save them - otherwise change user right now.
            if (this._isLoading) {
                return;
            }
            if (checker.checkFormForChanges('[data-region="grade-panel"] .gradeform')) {
                // Form has changes, so we need to confirm before switching users.
                str.get_strings([{
                        key: 'unsavedchanges',
                        component: 'report_reflectionexporter'
                    },
                    {
                        key: 'unsavedchangesquestion',
                        component: 'report_reflectionexporter'
                    },
                    {
                        key: 'saveandcontinue',
                        component: 'report_reflectionexporter'
                    },
                    {
                        key: 'cancel',
                        component: 'core'
                    },
                ]).done(function (strs) {
                    notification.confirm(strs[0], strs[1], strs[2], strs[3], function () {
                        $(document).trigger('save-changes', -1);
                    });
                });
            } else {
                $(document).trigger('user-changed', -1);
            }
        };

        /**
         * Select the specified user by id.
         *
         * @private
         * @method _selectUserById
         * @param {Number} userid
         */
        ViewingNavigation.prototype._selectUserById = function (userid) {
            var select = this._region.find('[data-action=change-user]');
            var useridnumber = parseInt(userid, 10);

            // Detect unsaved changes, and offer to save them - otherwise change user right now.
            if (this._isLoading) {
                return;
            }
            if (checker.checkFormForChanges('[data-region="grade-panel"] .gradeform')) {
                // Form has changes, so we need to confirm before switching users.
                str.get_strings([{
                        key: 'unsavedchanges',
                        component: 'report_reflectionexporter'
                    },
                    {
                        key: 'unsavedchangesquestion',
                        component: 'report_reflectionexporter'
                    },
                    {
                        key: 'saveandcontinue',
                        component: 'report_reflectionexporter'
                    },
                    {
                        key: 'cancel',
                        component: 'core'
                    },
                ]).done(function (strs) {
                    notification.confirm(strs[0], strs[1], strs[2], strs[3], function () {
                        $(document).trigger('save-changes', useridnumber);
                    });
                });
            } else {
                select.attr('data-selected', userid);

                if (!isNaN(useridnumber) && useridnumber > 0) {
                    $(document).trigger('user-changed', userid);
                }
            }
        };


        /**
         * Change to the previous user in the viewing list.
         *
         * @private
         * @method _handlePreviousUser
         * @param {Event} e
         */
        ViewingNavigation.prototype._handlePreviousUser = function (e) {
            e.preventDefault();
            var select = this._region.find('[data-action=change-user]');
            var currentUserId = select.attr('data-selected');
            var i = 0;
            var currentIndex = 0;

            for (i = 0; i < this._filteredUsers.length; i++) {
                if (this._filteredUsers[i].id == currentUserId) {
                    currentIndex = i;
                    break;
                }
            }

            var count = this._filteredUsers.length;
            var newIndex = (currentIndex - 1);
            if (newIndex < 0) {
                newIndex = count - 1;
            }

            if (count) {
                this._selectUserById(this._filteredUsers[newIndex].id);
            }
        };

        /**
         * Change to the next user in the viewing list.
         *
         * @param {Event} e
         * @param {Boolean} saved Has the form already been saved? Skips checking for changes if true.
         */
        ViewingNavigation.prototype._handleNextUser = function (e, saved) {
            e.preventDefault();
            var select = this._region.find('[data-action=change-user]');
            var currentUserId = select.attr('data-selected');
            var i = 0;
            var currentIndex = 0;

            for (i = 0; i < this._filteredUsers.length; i++) {
                if (this._filteredUsers[i].id == currentUserId) {
                    currentIndex = i;
                    break;
                }
            }

            var count = this._filteredUsers.length;
            var newIndex = (currentIndex + 1) % count;

            if (saved && count) {
                // If we've already saved the grade, skip checking if we've made any changes.
                var userid = this._filteredUsers[newIndex].id;
                var useridnumber = parseInt(userid, 10);
                select.attr('data-selected', userid);
                if (!isNaN(useridnumber) && useridnumber > 0) {
                    $(document).trigger('user-changed', userid);
                }
            } else if (count) {
                this._selectUserById(this._filteredUsers[newIndex].id);
            }
        };



        /**
         * Respond to a user-changed event by updating the selector.
         *
         * @private
         * @method _refreshSelector
         * @param {Event} event
         * @param {String} userid
         */
        ViewingNavigation.prototype._refreshSelector = function (event, userid) {
            var select = this._region.find('[data-action=change-user]');
            userid = parseInt(userid, 10);

            if (!isNaN(userid) && userid > 0) {
                select.attr('data-selected', userid);
            }

        };

        /**
         * Trigger the next user event depending on the number of filtered users
         *
         * @private
         * @method _triggerNextUserEvent
         */
        ViewingNavigation.prototype._triggerNextUserEvent = function () {

            $(document).trigger('next-user', {
                nextUser: false
            });
        };

        /**
         * Change to a different user in the viewing list.
         *
         * @private
         * @method _handleChangeUser
         */
        ViewingNavigation.prototype._handleChangeUser = function () {
            var select = this._region.find('[data-action=change-user]');
            var userid = parseInt(select.val(), 10);

            if (this._isLoading) {
                return;
            }
            if (checker.checkFormForChanges('[data-region="grade-panel"] .gradeform')) {
                // Form has changes, so we need to confirm before switching users.
                str.get_strings([{
                        key: 'unsavedchanges',
                        component: 'report_reflectionexporter'
                    },
                    {
                        key: 'unsavedchangesquestion',
                        component: 'report_reflectionexporter'
                    },
                    {
                        key: 'saveandcontinue',
                        component: 'report_reflectionexporter'
                    },
                    {
                        key: 'cancel',
                        component: 'core'
                    },
                ]).done(function (strs) {
                    notification.confirm(strs[0], strs[1], strs[2], strs[3], function () {
                        $(document).trigger('save-changes', userid);
                    });
                });
            } else {
                if (!isNaN(userid) && userid > 0) {
                    select.attr('data-selected', userid);

                    $(document).trigger('user-changed', userid);
                }
            }
        };

        return ViewingNavigation;
    });