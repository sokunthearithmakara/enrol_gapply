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
 * Application detail modal for MooTube embedded view.
 *
 * @module     enrol_gapply/applications_modal
 * @copyright  2026 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Modal from 'core/modal';
import ModalEvents from 'core/modal_events';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {getString} from 'core/str';
import {add as addToast} from 'core/toast';
import $ from 'jquery';

/** @type {Object|null} */
let detailModal = null;

/** @type {JQuery|null} */
let detailRoot = null;

/** @type {Object|null} Active modal config. */
let activeConfig = null;

/** @type {boolean} */
let handlersBound = false;

/**
 * Ensure gapply styles are loaded.
 */
const ensureStyles = () => {
    if (document.getElementById('enrol-gapply-styles')) {
        return;
    }
    const link = document.createElement('link');
    link.id = 'enrol-gapply-styles';
    link.rel = 'stylesheet';
    link.href = `${M.cfg.wwwroot}/enrol/gapply/styles.css`;
    document.head.appendChild(link);
};

/**
 * Get ordered application ids for navigation.
 *
 * @param {Function|null} getter Getter callback.
 * @return {Number[]}
 */
const getApplicationIds = (getter) => {
    if (typeof getter === 'function') {
        return getter();
    }
    return [];
};

/**
 * Show the action confirmation modal.
 *
 * @param {String} action Action name.
 * @param {Number[]} ids Application ids.
 * @param {Object} config Modal config.
 * @return {Promise<void>}
 */
const showActionModal = async(action, ids, config) => {
    let primarybutton = 'btn-success';
    if (action === 'waitlist') {
        primarybutton = 'btn-info';
    } else if (action === 'reject') {
        primarybutton = 'btn-warning';
    } else if (action === 'delete') {
        primarybutton = 'btn-danger';
    }

    const isBulk = ids.length > 1;
    const titleKey = isBulk ? action + 'applications' : action + 'application';
    const bodyKey = isBulk ? 'areyousureyouwantto' + action + '_bulk' : 'areyousureyouwantto' + action;
    const tags = '{{firstname}}, {{lastname}}, {{fullname}}, {{coursename}}, {{courseid}}';

    const [
        strAssigngroups, strCreatenewgroup, strGroupname, strEntergroupname, strGroupingoptional,
        strNogrouping, strConfirmcreation, strAssignrole, strStartdate, strEnddate, strOutcomemessage,
        strAvailabletags, strCancel, strProceed, strEntergroupnameerror,
        strTitle, strBody, strSuccess, strSuccessBulk, strActionlabel,
    ] = await Promise.all([
        getString('assigngroups', 'enrol_gapply'),
        getString('createnewgroup', 'enrol_gapply'),
        getString('groupname', 'enrol_gapply'),
        getString('entergroupname', 'enrol_gapply'),
        getString('groupingoptional', 'enrol_gapply'),
        getString('nogrouping', 'enrol_gapply'),
        getString('confirmcreation', 'enrol_gapply'),
        getString('assignrole', 'enrol_gapply'),
        getString('startdate', 'enrol_gapply'),
        getString('enddate', 'enrol_gapply'),
        getString('outcomemessage', 'enrol_gapply'),
        getString('availabletags', 'enrol_gapply', tags),
        getString('cancel', 'enrol_gapply'),
        getString('proceed', 'enrol_gapply'),
        getString('entergroupnameerror', 'enrol_gapply'),
        getString(titleKey, 'enrol_gapply'),
        getString(bodyKey, 'enrol_gapply', ids.length),
        getString(action + 'success', 'enrol_gapply'),
        getString(action + 'success_bulk', 'enrol_gapply', ids.length),
        getString(action + 'application', 'enrol_gapply'),
    ]);

    let roleoptions = '';
    let groupoptions = '';
    let startdate = '';
    let enddate = '';

    if (action === 'approve') {
        const [groupsData, rolesData, groupingsData] = await Promise.all([
            Ajax.call([{methodname: 'enrol_gapply_get_groups', args: {courseid: config.courseid}}])[0],
            Ajax.call([{methodname: 'enrol_gapply_get_roles_and_dates', args: {instanceid: config.instanceid}}])[0],
            Ajax.call([{methodname: 'enrol_gapply_get_course_groupings', args: {courseid: config.courseid}}])[0],
        ]);

        if (groupsData.length > 0) {
            groupsData.forEach((group) => {
                groupoptions += `<div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input groups"
                        id="group-${group.id}" name="groups[]" value="${group.id}">
                    <label class="form-check-label" for="group-${group.id}">${group.name}</label>
                </div>`;
            });
        }

        groupoptions = `<div class="mt-2">
            <label class="font-weight-bold" for="groups-list-container">${strAssigngroups}</label>
            <div class="gapply-groups-container" id="groups-list-container">${groupoptions}</div>
            <hr>
            <div id="new-group-container">
                <button type="button" class="btn btn-outline-primary btn-block toggle-new-group">
                    <i class="fa fa-plus-circle me-2"></i>${strCreatenewgroup}
                </button>
                <div class="new-group-form mt-3 p-3 border rounded bg-light" style="display: none;">
                    <div class="form-group">
                        <label class="font-weight-bold" for="new-group-name">${strGroupname}</label>
                        <input type="text" class="form-control new-group-name" id="new-group-name"
                            placeholder="${strEntergroupname}">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold" for="new-grouping">${strGroupingoptional}</label>
                        <select class="custom-select new-grouping w-100" id="new-grouping">
                            <option value="0">${strNogrouping}</option>
                            ${groupingsData.map(g => `<option value="${g.id}">${g.name}</option>`).join('')}
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary btn-block confirm-create-group">
                        ${strConfirmcreation}
                    </button>
                </div>
            </div>
        </div>`;

        const roles = rolesData.roles;
        if (roles.length > 0) {
            roleoptions = `<div class="form-group mt-3">
                <label for="role">${strAssignrole}</label>
                <select class="custom-select w-100" id="role" name="role">`;
            roles.forEach((role) => {
                const selected = role.id == rolesData.defaultrole ? 'selected' : '';
                roleoptions += `<option value="${role.id}" ${selected}>${role.name}</option>`;
            });
            roleoptions += '</select></div>';
        }

        const formatDatetime = (timestamp) => {
            if (!timestamp) {
                return '';
            }
            const date = new Date(timestamp * 1000);
            return date.getFullYear() + '-' +
                ('0' + (date.getMonth() + 1)).slice(-2) + '-' +
                ('0' + date.getDate()).slice(-2) + 'T' +
                ('0' + date.getHours()).slice(-2) + ':' +
                ('0' + date.getMinutes()).slice(-2);
        };

        startdate = `<div class="form-group mt-3">
            <label for="startdate">${strStartdate}</label>
            <input type="datetime-local" class="form-control w-100" id="startdate" name="startdate"
                value="${formatDatetime(rolesData.startdate)}">
        </div>`;
        enddate = `<div class="form-group mt-3">
            <label for="enddate">${strEnddate}</label>
            <input type="datetime-local" class="form-control w-100" id="enddate" name="enddate"
                value="${formatDatetime(rolesData.enddate)}">
        </div>`;
    }

    let outcomemessage = '';
    if (action !== 'delete') {
        outcomemessage = `<div class="form-group mt-3">
            <label for="outcomemessage">${strOutcomemessage}</label>
            <textarea class="form-control w-100" id="outcomemessage" name="outcomemessage" rows="3"></textarea>
            <small class="form-text text-muted">${strAvailabletags}</small>
        </div>`;
    }

    const actionModal = await Modal.create({
        title: strTitle,
        body: `<p class="mb-0">${strBody}</p>
            ${roleoptions}${startdate}${enddate}${groupoptions}${outcomemessage}`,
        isVerticallyCentered: true,
        removeOnClose: true,
        footer: `<button class="btn btn-secondary text-uppercase font-weight-bold" data-action="hide">
            ${strCancel}</button>
            <button class="btn ${primarybutton} text-uppercase font-weight-bold" data-action="proceed">
            ${strProceed}</button>`,
    });

    const actionRoot = actionModal.getRoot();
    actionRoot.addClass('gapply-confirm-modal');

    actionRoot.on('click', '.toggle-new-group', (e) => {
        e.preventDefault();
        actionRoot.find('.new-group-form').slideToggle();
    });

    actionRoot.on('click', '.confirm-create-group', async(e) => {
        e.preventDefault();
        const btn = $(e.currentTarget);
        const name = actionRoot.find('.new-group-name').val().trim();
        const groupingid = parseInt(actionRoot.find('.new-grouping').val(), 10);

        if (!name) {
            addToast(strEntergroupnameerror, {type: 'danger'});
            return;
        }

        btn.prop('disabled', true);
        try {
            const newGroups = await Ajax.call([{
                methodname: 'enrol_gapply_create_groups',
                args: {
                    groups: [{
                        courseid: config.courseid,
                        name: name,
                        description: '',
                        descriptionformat: 1,
                    }],
                },
            }])[0];

            const newGroup = newGroups[0];
            if (groupingid > 0) {
                await Ajax.call([{
                    methodname: 'enrol_gapply_assign_grouping',
                    args: {assignments: [{groupingid: groupingid, groupid: newGroup.id}]},
                }])[0];
            }

            actionRoot.find('#groups-list-container').append(`
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input groups" id="group-${newGroup.id}" value="${newGroup.id}" checked>
                    <label class="form-check-label" for="group-${newGroup.id}">${newGroup.name}</label>
                </div>`);
            actionRoot.find('.new-group-name').val('');
            actionRoot.find('.new-grouping').val('0');
            actionRoot.find('.new-group-form').slideUp();
        } catch (error) {
            Notification.exception(error);
        } finally {
            btn.prop('disabled', false).text(strConfirmcreation);
        }
    });

    actionRoot.find('[data-action="proceed"]').on('click', async function() {
        const $btn = $(this);
        $btn.prop('disabled', true);

        const selectedGroups = actionRoot.find('input.groups:checked').map(function() {
            return parseInt(this.value, 10);
        }).get();

        try {
            await Ajax.call([{
                methodname: 'enrol_gapply_manage_applications',
                args: {
                    action: action,
                    ids: ids.map((aid) => parseInt(aid, 10)),
                    instanceid: config.instanceid,
                    roleid: parseInt(actionRoot.find('select#role').val() || 0, 10),
                    start: actionRoot.find('input#startdate').val() ?
                        Math.floor(new Date(actionRoot.find('input#startdate').val()).getTime() / 1000) : 0,
                    end: actionRoot.find('input#enddate').val() ?
                        Math.floor(new Date(actionRoot.find('input#enddate').val()).getTime() / 1000) : 0,
                    groups: selectedGroups,
                    message: actionRoot.find('#outcomemessage').val() || '',
                },
            }])[0];

            addToast(isBulk ? strSuccessBulk : strSuccess, {
                type: 'success',
                subtitle: strActionlabel,
            });

            actionModal.hide();

            let nextAppId = null;
            if (!isBulk && detailModal && detailModal.isVisible()) {
                const idsList = getApplicationIds(config.getApplicationIds);
                const currentIndex = idsList.indexOf(parseInt(ids[0], 10));
                if (currentIndex >= 0) {
                    nextAppId = idsList[currentIndex + 1] ?? idsList[currentIndex - 1] ?? null;
                }
            }

            if (typeof config.onAction === 'function') {
                await config.onAction();
            }

            if (!isBulk && detailModal && detailModal.isVisible()) {
                if (nextAppId) {
                    await loadApplicationData(nextAppId, config);
                } else {
                    detailModal.hide();
                }
            }
        } catch (error) {
            Notification.exception(error);
        } finally {
            $btn.prop('disabled', false);
        }
    });

    actionModal.show();
};

/**
 * Load and display application detail modal.
 *
 * @param {Number} applicationid Application id.
 * @param {Object} config Modal config.
 * @return {Promise<void>}
 */
const loadApplicationData = async(applicationid, config) => {
    ensureStyles();
    activeConfig = config;

    const ids = getApplicationIds(config.getApplicationIds);
    const index = ids.indexOf(parseInt(applicationid, 10));
    const previd = index > 0 ? ids[index - 1] : null;
    const nextid = index >= 0 && index < ids.length - 1 ? ids[index + 1] : null;
    const total = ids.length || 1;
    const displayIndex = index >= 0 ? index + 1 : 1;

    try {
        const appInfo = await Ajax.call([{
            methodname: 'enrol_gapply_get_application_info',
            args: {applicationid: applicationid, instanceid: config.instanceid},
        }])[0];

        if (!appInfo.found) {
            addToast(getString('nofound', 'enrol_gapply'), {type: 'danger'});
            return;
        }

        const summaryResponse = await Ajax.call([{
            methodname: 'enrol_gapply_get_user_summary',
            args: {userid: parseInt(appInfo.userid, 10), instanceid: config.instanceid},
        }])[0];

        if (!detailModal) {
            detailModal = await Modal.create({type: Modal.TYPE, large: true});
            detailRoot = detailModal.getRoot();
            detailRoot.addClass('gapply-modal');
            detailRoot.find('.modal-content').append(`<div class="gapply-modal-loading-overlay">
                <div class="spinner-border text-primary"></div></div>`);
            detailRoot.find('.modal-title').addClass('w-100');

            detailRoot.on('click', '.show-outcome-message', function(e) {
                e.preventDefault();
                $(this).next('.outcome-message-content').slideToggle('fast');
                $(this).remove();
            });
        }

        if (detailModal.isVisible()) {
            detailRoot.addClass('is-loading');
        }

        const statusraw = appInfo.statusraw || config.status || 'new';
        const parsedAttachments = (appInfo.attachments || []).map((attachment) => ({
            url: attachment.link,
            filename: attachment.filename,
            mimetype: attachment.type,
        }));

        const restoreWidth = () => {
            const leftPane = detailRoot.find('#modal-left-pane');
            const savedWidth = localStorage.getItem('enrol_gapply_modal_left_pane_width');
            if (leftPane.length) {
                const widthValue = savedWidth ? (savedWidth + '%') : '66.66%';
                leftPane.css({width: widthValue, flex: 'none'});
            }
        };

        const [headerHtml, bodyHtml] = await Promise.all([
            Templates.render('enrol_gapply/modal_header', {
                user: summaryResponse.user,
                statushtml: appInfo.status,
                isapproved: statusraw === 'approved',
                iswaitlisted: statusraw === 'waitlisted',
                isrejected: statusraw === 'rejected',
                index: displayIndex,
                total: total,
                nextid: nextid,
                previd: previd,
                applicationid: applicationid,
            }),
            Templates.render('enrol_gapply/modal_body', {
                user: summaryResponse.user,
                identity: summaryResponse.identity,
                applytext: appInfo.applytext,
                attachments: parsedAttachments,
            }),
        ]);

        detailRoot.find('.modal-title').replaceWith(headerHtml);
        detailModal.setBody(bodyHtml);

        if (detailModal.isVisible()) {
            restoreWidth();
            detailRoot.find('#fileselect').trigger('change');
            detailRoot.removeClass('is-loading');
        } else {
            detailRoot.off(ModalEvents.shown).on(ModalEvents.shown, function() {
                restoreWidth();
                detailRoot.find('#fileselect').trigger('change');
                detailRoot.removeClass('is-loading');
            });
            detailModal.show();
        }
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Bind global modal handlers once.
 */
const bindHandlers = () => {
    if (handlersBound) {
        return;
    }
    handlersBound = true;

    $(document).on('mousedown', '.gapply-modal #modal-resizer', function(e) {
        e.preventDefault();
        const leftPane = $('.gapply-modal #modal-left-pane');
        const modalBody = $('.gapply-modal .modal-body');
        if (!leftPane.length || !modalBody.length) {
            return;
        }

        let isResizing = true;
        $('body').addClass('resizing');
        const startX = e.clientX;
        const startWidth = leftPane[0].getBoundingClientRect().width;
        const modalWidth = modalBody.width();

        $(document).on('mousemove.resizer', function(moveEvent) {
            if (!isResizing) {
                return;
            }
            let newWidth = startWidth + (moveEvent.clientX - startX);
            newWidth = Math.max(300, Math.min(newWidth, modalWidth - 250));
            leftPane.css({width: newWidth + 'px', flex: 'none'});
        });

        $(document).on('mouseup.resizer', function() {
            if (!isResizing) {
                return;
            }
            isResizing = false;
            $('body').removeClass('resizing');
            const percentage = (leftPane[0].getBoundingClientRect().width / modalWidth) * 100;
            localStorage.setItem('enrol_gapply_modal_left_pane_width', percentage);
            leftPane.css('width', percentage + '%');
            $(document).off('mousemove.resizer mouseup.resizer');
        });
    });

    $(document).on('click', '.gapply-modal .collapsible-header', function() {
        $(this).closest('.collapsible-section').toggleClass('collapsed');
    });

    $(document).on('change', '.gapply-modal #fileselect', function() {
        const selected = $(this).find(':selected');
        const vurl = $(this).val();
        const vtype = selected.data('type') || '';
        let vhtml = !vurl ? `<p class="text-center py-5">${M.util.get_string('noattachments', 'enrol_gapply')}</p>` : '';
        if (vurl) {
            if (vtype.includes('image')) {
                vhtml = `<img src="${vurl}" class="img-fluid mx-auto" alt="${selected.text()}">`;
            } else if (vtype.includes('video')) {
                vhtml = `<video src="${vurl}" controls width="100%" autoplay></video>`;
            } else if (vtype.includes('audio')) {
                vhtml = `<audio src="${vurl}" controls width="100%" autoplay></audio>`;
            } else if (vtype.includes('pdf')) {
                vhtml = `<object data="${vurl}" type="application/pdf" width="100%" style="height: 100%"></object>`;
            } else {
                const encodedUrl = encodeURIComponent(vurl);
                vhtml = `<iframe src="https://docs.google.com/viewer?url=${encodedUrl}&embedded=true"
                    style="width: 100%; height: 100%; border: none;"></iframe>`;
            }
        }
        $('.gapply-modal #viewer').html(vhtml);
    });

    $(document).on('click', '.gapply-modal .action-button', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        if (!activeConfig) {
            return;
        }
        const actionName = $(this).data('action');
        const currentId = $('.gapply-modal .nav-button').attr('data-currentid');
        showActionModal(actionName, [currentId], activeConfig);
    });

    $(document).on('click', '.gapply-modal .nav-button', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const $btn = $(this);
        if ($btn.prop('disabled') || !activeConfig) {
            return;
        }
        const targetId = $btn.data('action') === 'next' ? $btn.attr('data-nextid') : $btn.attr('data-previd');
        if (targetId) {
            loadApplicationData(parseInt(targetId, 10), activeConfig);
        }
    });
};

/**
 * Open the application detail modal.
 *
 * @param {Number} applicationid Application id.
 * @param {Object} config Config object.
 * @return {Promise<void>}
 */
export const show = async(applicationid, config) => {
    bindHandlers();
    await loadApplicationData(applicationid, config);
};

export {showActionModal};

export default {
    show,
    showActionModal,
};
