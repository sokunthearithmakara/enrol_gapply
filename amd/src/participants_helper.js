/**
 * Helper for participants page.
 *
 * @module     enrol_gapply/participants_helper
 * @copyright  2026 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery',
    'core/ajax',
    'core/notification',
    'core/modal_events',
    'core/modal_save_cancel',
    'core/str',
    'core_table/dynamic',
    'core/config',
    'core/toast',
], function($, Ajax, Notification, ModalEvents, SaveCancelModal, Str, DynamicTable, Config, toast) {

    return {
        /**
         * Initialize the helper.
         *
         * @param {Object} config
         */
        init: async function(config) {
            const injectOptions = async() => {
                const select = $('#formactionid');
                if (!select.length) {
                    return;
                }

                // Avoid duplicate injection.
                if (select.find('option[value="#addtogroup"]').length) {
                    return;
                }

                const addOption = $(`<option value="#addtogroup">${await Str.get_string('addtogroup', 'enrol_gapply')}</option>`);
                const removeOption = $(`<option value="#removefromgroup">
                    ${await Str.get_string('removefromgroup', 'enrol_gapply')}</option>`);

                const lastOptgroup = select.find('optgroup').last();
                if (lastOptgroup.length) {
                    lastOptgroup.append(addOption).append(removeOption);
                } else {
                    select.append(addOption).append(removeOption);
                }
            };

            // Initial injection.
            await injectOptions();

            // Intercept the change event in the capture phase.
            // This allows us to handle the event before Moodle core's bubbling listener resets the select.
            document.addEventListener('change', async(e) => {
                if (e.target.id === 'formactionid') {
                    const action = e.target.value;
                    if (action === '#addtogroup' || action === '#removefromgroup') {
                        // Prevent Moodle core from seeing this event.
                        e.stopImmediatePropagation();
                        e.preventDefault();

                        const toggleGroup = e.target.dataset.togglegroup || 'participants-table';
                        const userids = [];
                        // Use a more robust selector that matches how Moodle core finds slave checkboxes.
                        let checkboxes = $(`input[data-toggle="slave"][data-togglegroup*="${toggleGroup}"]:checked`);

                        // Fallback: if no checkboxes found with togglegroup, try a more generic selector.
                        if (checkboxes.length === 0) {
                            checkboxes = $('input[name^="user"]:checked');
                        }

                        checkboxes.each(function() {
                            // Use 'name' attribute to match Moodle core pattern (user{id}).
                            const nameAttr = $(this).attr('name');
                            if (nameAttr) {
                                const userid = nameAttr.replace('user', '');
                                if (userid && !isNaN(userid)) {
                                    userids.push(userid);
                                }
                            }
                        });

                        if (userids.length === 0) {
                            toast.add(await Str.get_string('selectparticipant', 'enrol_gapply'), {
                                type: 'danger'
                            });
                            $(e.target).val('');
                            return;
                        }

                        handleGroupAction(config.courseid, userids, action === '#addtogroup');
                        // Reset the select.
                        $(e.target).val('');
                    }
                }
            }, true); // Use capture phase.

            // Listen for table refresh to re-inject options.
            const tableRoot = document.querySelector('[data-region="core_table/dynamic"]');
            if (tableRoot) {
                tableRoot.addEventListener(DynamicTable.Events.tableContentRefreshed, () => {
                    injectOptions();
                });
            }
        }
    };

    /**
     * Handle the group action (add/remove).
     *
     * @param {Number} courseid
     * @param {Number[]} userids
     * @param {Boolean} isAdd
     */
    async function handleGroupAction(courseid, userids, isAdd) {
        try {
            const [groups, groupings] = await Promise.all([
                Ajax.call([{
                    methodname: 'core_group_get_course_groups',
                    args: {courseid: courseid}
                }])[0],
                Ajax.call([{
                    methodname: 'enrol_gapply_get_course_groupings',
                    args: {courseid: courseid}
                }])[0]
            ]);

            if (!groups.length && !isAdd) {
                Notification.alert('Info', 'No groups found in this course.');
                return;
            }

            const modalId = Math.floor(Math.random() * 1000000);
            const modal = await SaveCancelModal.create({
                title: isAdd ? await Str.get_string('addtogroup', 'enrol_gapply')
                    : await Str.get_string('removefromgroup', 'enrol_gapply'),
                body: `
                    <div class="form-group">
                        <label class="d-block mb-2 font-weight-bold">${await Str.get_string('selectgroups', 'enrol_gapply')}</label>
                        <div id="group-checkboxes-${modalId}" class="gapply-groups-container">
                            ${groups.map(g => `
                                <div class="form-check mb-2">
                                    <input type="checkbox" class="form-check-input group-checkbox"
                                        id="group-${modalId}-${g.id}" value="${g.id}">
                                    <label class="form-check-label" for="group-${modalId}-${g.id}">${g.name}</label>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    ${isAdd ? `
                    <hr>
                    <div id="new-group-container-${modalId}">
                        <button type="button" class="btn btn-outline-primary btn-block toggle-new-group">
                            <i class="fa fa-plus-circle"></i> ${await Str.get_string('createnewgroup', 'enrol_gapply')}
                        </button>
                        <div class="new-group-form mt-3 p-3 border rounded bg-light" style="display: none;">
                            <div class="form-group">
                                <label for="new-group-name" class="font-weight-bold">
                                ${await Str.get_string('groupname', 'enrol_gapply')}</label>
                                <input type="text" class="form-control new-group-name" id="new-group-name"
                                    placeholder="${await Str.get_string('entergroupname', 'enrol_gapply')}">
                            </div>
                            <div class="form-group">
                                <label for="new-grouping" class="font-weight-bold">
                                ${await Str.get_string('groupingoptional', 'enrol_gapply')}</label>
                                <select class="custom-select new-grouping w-100" id="new-grouping">
                                    <option value="0">${await Str.get_string('nogrouping', 'enrol_gapply')}</option>
                                    ${groupings.map(g => `<option value="${g.id}">${g.name}</option>`).join('')}
                                </select>
                            </div>
                            <button type="button"
                                class="btn btn-primary btn-block confirm-create-group">
                                ${await Str.get_string('confirmcreation', 'enrol_gapply')}</button>
                        </div>
                    </div>` : ''}`,
                buttons: {
                    save: isAdd ? await Str.get_string('add', 'enrol_gapply') : await Str.get_string('remove', 'enrol_gapply')
                }
            });

            // Add settings button to footer.
            const groupSettingsUrl = Config.wwwroot + '/group/index.php?id=' + courseid;
            const settingsBtn = $(`
                <a href="${groupSettingsUrl}" class="btn btn-link p-0 mr-auto align-self-center"
                    target="_blank" title="${await Str.get_string('groupsettings', 'enrol_gapply')}">
                    <i class="fa fa-cog fa-lg"></i>
                </a>
            `);
            modal.getFooter().prepend(settingsBtn);

            // Handle toggle new group form.
            modal.getRoot().on('click', '.toggle-new-group', (e) => {
                e.preventDefault();
                modal.getRoot().find('.new-group-form').slideToggle();
            });

            // Handle group creation.
            modal.getRoot().on('click', '.confirm-create-group', async(e) => {
                e.preventDefault();
                const btn = $(e.currentTarget);
                const name = modal.getRoot().find('.new-group-name').val().trim();
                const groupingid = parseInt(modal.getRoot().find('.new-grouping').val());

                if (!name) {
                    toast.add(await Str.get_string('entergroupnameerror', 'enrol_gapply'), {
                        type: 'danger'
                    });
                    return;
                }

                btn.prop('disabled', true)
                    .html('<i class="fa fa-circle-o-notch fa-spin"></i> ' + await Str.get_string('creating', 'enrol_gapply'));

                try {
                    const newGroups = await Ajax.call([{
                        methodname: 'enrol_gapply_create_groups',
                        args: {
                            groups: [{
                                courseid: courseid,
                                name: name,
                                description: '',
                                descriptionformat: 1
                            }]
                        }
                    }])[0];

                    const newGroup = newGroups[0];

                    if (groupingid > 0) {
                        await Ajax.call([{
                            methodname: 'enrol_gapply_assign_grouping',
                            args: {
                                assignments: [{
                                    groupingid: groupingid,
                                    groupid: newGroup.id
                                }]
                            }
                        }])[0];
                    }

                    // Add to the list.
                    const checkboxHtml = `
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input group-checkbox"
                                id="group-${modalId}-${newGroup.id}" value="${newGroup.id}" checked>
                            <label class="form-check-label" for="group-${modalId}-${newGroup.id}">${newGroup.name}</label>
                        </div>
                    `;
                    modal.getRoot().find(`#group-checkboxes-${modalId}`).append(checkboxHtml);

                    // Sort the groups by name
                    const groups = modal.getRoot().find(`#group-checkboxes-${modalId} .form-check`);
                    groups.sort((a, b) => {
                        const nameA = $(a).find('label').text();
                        const nameB = $(b).find('label').text();
                        return nameA.localeCompare(nameB);
                    });
                    modal.getRoot().find(`#group-checkboxes-${modalId}`).empty().append(groups);

                    // Reset and hide form.
                    modal.getRoot().find('.new-group-name').val('');
                    modal.getRoot().find('.new-grouping').val('0');
                    modal.getRoot().find('.new-group-form').slideUp();
                    btn.prop('disabled', false).text(await Str.get_string('confirmcreation', 'enrol_gapply'));

                } catch (error) {
                    btn.prop('disabled', false).text(await Str.get_string('confirmcreation', 'enrol_gapply'));
                    Notification.exception(error);
                }
            });

            modal.getRoot().on(ModalEvents.save, async(e) => {
                e.preventDefault();
                const selectedGroupIds = modal.getRoot().find('.group-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();

                if (selectedGroupIds.length === 0) {
                    Notification.alert('Info', await Str.get_string('selectgroup', 'enrol_gapply'));
                    return;
                }

                const method = isAdd ? 'enrol_gapply_add_group_members' : 'enrol_gapply_delete_group_members';
                const members = [];
                selectedGroupIds.forEach(groupid => {
                    userids.forEach(userid => {
                        members.push({groupid: groupid, userid: userid});
                    });
                });

                Ajax.call([{
                    methodname: method,
                    args: {members: members}
                }])[0].then(() => {
                    modal.hide();
                    const tableRoot = $('[data-region="core_table/dynamic"]').first()[0];
                    if (tableRoot) {
                        DynamicTable.refreshTableContent(tableRoot);
                    }
                    return;
                }).catch(Notification.exception);
            });

            modal.show();
        } catch (error) {
            Notification.exception(error);
        }
    }
});
