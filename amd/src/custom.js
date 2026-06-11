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
 * Custom module
 *
 * @module     enrol_gapply/custom
 * @copyright  2024 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
    [
        'jquery',
        'enrol_gapply/jszip',
        'core/toast',
        'core/ajax',
        'core/notification',
        'core/templates',
        'enrol_gapply/jquery.dataTables',
        'enrol_gapply/dataTables.bootstrap4',
        'enrol_gapply/dataTables.select',
        'enrol_gapply/select.bootstrap4',
        'enrol_gapply/dataTables.buttons',
        'enrol_gapply/buttons.bootstrap4',
        'enrol_gapply/buttons.html5',
        'enrol_gapply/dataTables.rowGroup',
        'enrol_gapply/rowGroup.bootstrap4',
        'enrol_gapply/buttons.colVis',
    ], function($, JSZip, toast, Ajax, Notification, Templates) {
        window.JSZip = JSZip;
        let table;
        let detailModal, detailRoot;
        let originalTitle;

        return {
            init: function(tab, id) {
                const searchHtml = `<div class="d-flex align-items-center me-1 mr-1">
                        <input type="number" id="application-search-input" class="form-control form-control-sm"
                            placeholder="${M.util.get_string('applicationid', 'enrol_gapply')}">
                    </div>`;
                    $('#page-enrol-gapply-manage #page-content .nav.nav-tabs').prepend(searchHtml);

                    $('body').append(`<div id="enrol-gapply-loading" class="d-none align-items-center justify-content-center
                         position-fixed w-100 h-100"
                        style="top: 0;bottom: 0; left: 0; right: 0; z-index: 9999; background: rgba(0,0,0,0.5);">
                        <div class="spinner-grow text-light" style="width: 3rem; height: 3rem;" role="status">
                        <span class="sr-only">Loading...</span></div></div>`);
                // 'id' is the enrolment instance ID passed from manage.php.
                const tableElement = $("#gapplytable");
                const branch = tableElement.length ? parseInt(tableElement.data("moodle-branch")) : 405;
                const isModern = branch >= 403; // Moodle 4.3+
                const modalModule = isModern ? 'core/modal' : 'core/modal_factory';
                const eventsModule = 'core/modal_events';

                require([modalModule, eventsModule], function(Modal, ModalEvents) {
                    originalTitle = document.title;

                    $(document).on('click', 'a[data-type]', async function() {
                        let $this = $(this);
                        let fileModal = await Modal.create({
                            title: $this.text(),
                            body: '',
                            large: true,
                            isVerticallyCentered: true,
                            removeOnClose: true,
                            footer: `<button class="btn btn-primary text-uppercase font-weight-bold" data-action="download">
                                        ${M.util.get_string('download', 'enrol_gapply')}</button>
                                     <button class="btn btn-secondary text-uppercase font-weight-bold" data-action="hide">
                                        ${M.util.get_string('close', 'enrol_gapply')}</button>`
                        });

                        const fileRoot = fileModal.getRoot();
                        fileRoot.attr("id", "applyfile");
                        fileRoot.find('.modal-lg').toggleClass('modal-lg modal-xl');
                        const fileBody = fileRoot.find('.modal-body');
                        fileBody.addClass('p-0 h-vh');
                        let html = "";
                        const url = $this.data("url");
                        const type = $this.data("type");

                        if (type.includes("image")) {
                            html = `<img src="${url}" class="img-fluid mx-auto">`;
                            $("#applyfile .modal-body").removeClass("d-flex");
                        } else if (type.includes("video")) {
                            html = `<video src="${url}"
                        class="embed-responsive-item text-center m-0" controls width="100%" autoplay></video>`;
                        } else if (type.includes("audio")) {
                            html = `<audio src="${url}"
                        class="embed-responsive-item text-center m-0" controls width="100%" autoplay></audio>`;
                        } else if (type.includes("pdf")) {
                            html = `<object data="${url}" type="application/pdf" width="100%">
                            <p>${M.util.get_string('cannotopenpdffile', 'enrol_gapply', url)}</p></object>`;
                        } else if (type.includes("officedocument") || type.includes("msword")
                            || type.includes("ms-excel")
                            || type.includes("ms-powerpoint")
                            || type.includes("openxmlformats")) {
                            html = `<iframe id="fileviewer"
                            src="https://view.officeapps.live.com/op/embed.aspx?src=${url}"
                            class="embed-responsive-item" style="width: 100%;"></iframe>`;
                        } else if (type.includes("text") || type.includes("csv")) {
                            html = `<iframe id="fileviewer"
                        src="https://docs.google.com/viewer?url=${url}&embedded=true"
                        class="embed-responsive-item" style="width: 100%; border-radius: 0"></iframe>`;
                        } else {
                            html = `<p class="text-center py-5">${M.util.get_string('cannotopenfile', 'enrol_gapply',
                                url)}</p>`;
                            fileBody.removeClass("d-flex");
                        }

                        fileBody.html(html);

                        let newURL = new URL(url);

                        fileRoot.on("click", '[data-action="download"]', function() {
                            newURL.searchParams.append("forcedownload", 1);
                            window.open(newURL.toString());
                        });

                        fileRoot.on(ModalEvents.hidden, function() {
                            fileModal.destroy();
                        });

                        fileModal.show();
                    });

                    const timecreatedIndex = $("th").index($("th.timecreated"));
                    let profileFields = [];

                    $("th.profilefield").each(function() {
                        const pr = {
                            index: $("th").index($(this)),
                            text: $(this).text(),
                        };
                        profileFields.push(pr);
                    });

                    const renderFilterBox = (findex, ftext) => {
                        return `<div class="col-sm-6 col-md-4 col-lg-3 col-xl-2 pl-0 pr-2">
                                <div class="form-group mb-1">
                                    <label for="filter-${findex}">${ftext}</label>
                                    <input type="text" class="form-control form-control-sm"
                                        id="filter-${findex}" data-index="${findex}"/>
                                </div>
                            </div>`;
                    };

                    let option = {
                        ajax: function(adata, callback) {
                            Ajax.call([{
                                methodname: "enrol_gapply_get_applications",
                                args: {instanceid: parseInt(id), tab: tab}
                            }])[0].then(function(response) {
                                callback({data: response});
                                return response;
                            }).catch(Notification.exception);
                        },
                        deferRender: true,
                        rowId: 'id',
                        dom: `<'d-flex align-items-start justify-content-between'<'d-flex align-items-start'Bl>f>
                        <'#filterregion.w-100 row'>t<'row'<'col-sm-6'i><'col-sm-6'p>>`,
                        buttons: [
                            {
                                extend: "copyHtml5",
                                text: '<i class="fa fa-copy fa-fw"></i>',
                                titleAttr: "",
                                className: "btn btn-sm btn-alt-primary",
                                messageTop: null,
                                title: null,
                                exportOptions: {
                                    columns: ['.exportable']
                                }
                            },
                            {
                                extend: "csvHtml5",
                                text: '<i class="fa fa-file-code-o fa-fw"></i>',
                                titleAttr: "",
                                className: "btn btn-sm btn-alt-primary",
                                exportOptions: {
                                    columns: ['.exportable']
                                }
                            },
                            {
                                extend: "excelHtml5",
                                text: '<i class="fa fa-file-excel-o fa-fw"></i>',
                                titleAttr: "",
                                className: "btn btn-sm btn-alt-primary",
                                exportOptions: {
                                    columns: ['.exportable']
                                }
                            },
                            {
                                extend: "colvis",
                                text: '<i class="fa fa-columns fa-fw"></i>',
                                titleAttr: "",
                                className: "btn btn-sm btn-alt-primary",
                                columns: '.colvis'
                            },
                        ],
                        "language": {
                            "lengthMenu": "_MENU_",
                            "zeroRecords": M.util.get_string('nofound', "enrol_gapply"),
                            "search": M.util.get_string('search', "enrol_gapply"),
                            "info": M.util.get_string('datatableinfo', "enrol_gapply"),
                            "infoEmpty": M.util.get_string('datatableinfoempty', "enrol_gapply"),
                            "infoFiltered": M.util.get_string('datatableinfofiltered', "enrol_gapply"),
                            "paginate": {
                                "first": M.util.get_string('first', 'enrol_gapply'),
                                "last": M.util.get_string('last', 'enrol_gapply'),
                                "next": M.util.get_string('next', 'enrol_gapply'),
                                "previous": M.util.get_string('previous', 'enrol_gapply')
                            },
                            select: {
                                rows: {
                                    _: M.util.get_string('rowsselected', 'enrol_gapply'),
                                }
                            }
                        },
                        "order": [[timecreatedIndex, "desc"]],
                        "columnDefs": [{
                            orderable: false,
                            className: 'select-checkbox',
                            targets: 0,
                            render: function(data, type) {
                                if (type === 'display') {
                                    return '<i class="fa fa-fw fa-check-square"></i><i class="far fa-fw fa-square"></i>';
                                }
                                return data;
                            }
                        },
                        {
                            targets: 'userdetails',
                            className: 'text-truncate',
                        },
                        {
                            "targets": 'inv',
                            "visible": false,
                        },
                        {
                            'targets': 'noorder',
                            'orderable': false,
                        }
                        ],
                        'initComplete': function() {
                            // Wrap the table in a div with overflow-x:auto
                            $('#gapplytable').wrap('<div style="overflow-x:auto;"></div>');
                            $('.dataTables_length').addClass('mx-1');
                            $('#gapplytable').addClass('mx-n1');
                            $('.table-responsive').css('overflow', 'visible');
                            // Remove d-none class
                            $('#gapplytable').removeClass('d-none');
                            $(".dataTables_filter").addClass("d-flex align-items-start float-right");
                            $(`<a class="btn btn-sm btn-secondary font-weight-bold ml-1"
                                 href="javascript:void(0)" id="filters" data-toggle="tooltip" data-bs-toggle="tooltip"
                                 title="Filter"><i class="fa fa-filter left fa-fw"></i></a>`)
                                .insertAfter(".dataTables_filter label");
                            $(document).off('click', '#filters').on('click', '#filters', function() {
                                $('#filterregion').slideToggle('fast', 'swing');
                            });
                            $('#filterregion').css('display', 'none');
                            $(renderFilterBox(1, M.util.get_string('applicationid', 'enrol_gapply'))).appendTo("#filterregion");
                            profileFields.forEach((element) => {
                                $(renderFilterBox(element.index, element.text)).appendTo("#filterregion");
                            });
                            // Create sort dropdown
                            $(`<div class="dropdown d-inline right small">
                        <button class="btn btn-sm btn-secondary dropdown-toggle font-weight-bold ml-1"
                         id="dropdownMenuButton" data-toggle="dropdown" data-bs-toggle="dropdown" aria-haspopup="true"
                          aria-expanded="false">
                         <i class="fa fa-sort fa-fw"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right dropdown-menu-end" id="sortdropdown"
                        aria-labelledby="dropdownMenuButton">
                        </div></div>`).insertAfter("#filters");
                            profileFields.forEach((element) => {
                                $('#sortdropdown').append(`<a class="dropdown-item" href="javascript:void(0)"
                                     data-col="${element.index}">${element.text}</a>`);
                            });
                            $('#sortdropdown').append(`<a class="dropdown-item" href="javascript:void(0)"
                                 data-col="${timecreatedIndex}">${M.util.get_string('timecreated', 'enrol_gapply')}</a>
                                 <div class="dropdown-divider"></div>
                            <a class="dropdown-item active" href="javascript:void(0)"
                             data-order="desc">${M.util.get_string('desc', "enrol_gapply")}</a>
                            <a class="dropdown-item" href="javascript:void(0)" data-order="asc">
                            ${M.util.get_string('asc', "enrol_gapply")}</a>`);
                        },
                    };

                    $('table th #selectall').addClass('form-check-input');

                    option.select = {
                        style: 'multi+shift',
                        selector: 'td:first-child'
                    };

                    if (tableElement.length) {
                        table = tableElement.DataTable(option);
                    }

                    // Handle filter event
                    $(document).on('keyup', '#filterregion input', function() {
                        if (!table) {
                            return;
                        }
                        const findex = $(this).data('index');
                        const fvalue = $(this).val();
                        if (findex == 1 && fvalue !== '') {
                            table.column(findex).search('^' + fvalue + '$', true, false).draw();
                        } else {
                            table.column(findex).search(fvalue, false, true).draw();
                        }
                    });

                    // Handle sort event
                    $(document).on("click", "#sortdropdown.dropdown-menu a", function() {
                        if (!table) {
                            return;
                        }
                        // If sort order is selected
                        if ($(this).data("order")) {
                            // Remove active class from all sort order options
                            $(".dropdown-menu a[data-order]").removeClass("active");
                            // Add active class to the selected sort order option
                            $(this).addClass("active");
                            // Set the sort order to the selected sort order
                        } else {
                            // If sort column is selected
                            // remove active class from all sort column options
                            $(".dropdown-menu a[data-col]").removeClass("active");
                            // Add active class to the selected sort column option
                            $(this).addClass("active");
                            // Set the sort column to the selected sort column
                        }
                        // Get the sort column
                        const colIndex = $(".dropdown-menu a[data-col].active").data("col");
                        // Get the sort order
                        const order = $(".dropdown-menu a[data-order].active").data("order");
                        // Sort the table
                        table.order([colIndex, order]).draw();
                    });

                    let selecteddata;

                    let getRowData = (dt) => {
                        $(".action-button:not(.menu-action)").remove();
                        selecteddata = dt.rows({selected: true}).data().toArray().map(row => row[1]);
                        let $toolbar = $("#gapply-floating-toolbar");
                        if ($toolbar.length === 0) {
                            $toolbar = $(`
                                <div id="gapply-floating-toolbar" class="gapply-floating-toolbar">
                                    <div class="selection-info">
                                        <div class="selection-icon"><i class="fa fa-check"></i></div>
                                        <span class="selection-count">0 ${M.util.get_string("items", "enrol_gapply")}</span>
                                    </div>
                                    <div class="toolbar-actions"></div>
                                    <button class="close-toolbar" title="${M.util.get_string('close', 'enrol_gapply')}">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            `).appendTo("body");

                            $toolbar.find('.close-toolbar').on('click', function() {
                                if (table) {
                                    table.rows().deselect();
                                }
                                $('#selectall').prop('checked', false);
                            });
                        }

                        if (selecteddata.length > 0) {
                            let buttons = "";
                            if (tab !== 'approved') {
                                buttons += `
                                <button class="btn-success action-button" data-action="approve"
                                    data-toggle="tooltip" data-bs-toggle="tooltip"
                                    title="${M.util.get_string("approve", "enrol_gapply")}">
                                    <i class="fa fa-check"></i></button>`;
                                if (tab !== 'waitlisted') {
                                    buttons += `
                                    <button class="btn-info action-button" data-action="waitlist"
                                        data-toggle="tooltip" data-bs-toggle="tooltip"
                                        title="${M.util.get_string("waitlist", "enrol_gapply")}">
                                        <i class="fa fa-clock-o"></i></button>`;
                                }
                                if (tab !== 'rejected') {
                                    buttons += `
                                    <button class="btn-warning action-button" data-action="reject"
                                        data-toggle="tooltip" data-bs-toggle="tooltip"
                                        title="${M.util.get_string("reject", "enrol_gapply")}">
                                        <i class="fa fa-times"></i></button>`;
                                }
                            }
                            buttons += `
                                <button class="btn-danger action-button" data-action="delete"
                                    data-toggle="tooltip" data-bs-toggle="tooltip"
                                    title="${M.util.get_string("delete", "enrol_gapply")}">
                                    <i class="fa fa-trash"></i></button>`;

                            $toolbar.find('.toolbar-actions').html(buttons);

                            // Initialize tooltips on new buttons
                            if (typeof $.fn.tooltip !== 'undefined') {
                                $toolbar.find('[data-toggle="tooltip"]').tooltip();
                            }

                            const itemsStr = selecteddata.length === 1 ? 'item' : 'items';
                            const translated = M.util.get_string(itemsStr, "enrol_gapply").toUpperCase();
                            $toolbar.find('.selection-count').text(`${selecteddata.length} ${translated}`);
                            $toolbar.addClass("show");
                        } else {
                            $toolbar.removeClass("show");
                        }
                    };

                    if (table) {
                        table.on('select deselect', function() {
                            getRowData(table);
                        });
                    }

                    const showActionModal = async(action, ids) => {
                        selecteddata = ids;
                        let primarybutton = "btn-success";
                        if (action == "waitlist") {
                            primarybutton = "btn-info";
                        } else if (action == "reject") {
                            primarybutton = "btn-warning";
                        } else if (action == "delete") {
                            primarybutton = "btn-danger";
                        }

                        // If action is approve, we have to get groups and assignable roleids.
                        let groups = [];
                        let rolesanddates;
                        let roleoptions = "";
                        let groupoptions = "";
                        let startdate = "";
                        let enddate = "";
                        if (action == "approve") {
                            // Get list of groups, roles, and groupings as promises
                            const [groupsData, rolesData, groupingsData] = await Promise.all([
                                Ajax.call([{
                                    methodname: "enrol_gapply_get_groups",
                                    args: {courseid: parseInt($("#gapplytable").data("courseid"))}
                                }])[0],
                                Ajax.call([{
                                    methodname: "enrol_gapply_get_roles_and_dates",
                                    args: {instanceid: parseInt(id)}
                                }])[0],
                                Ajax.call([{
                                    methodname: "enrol_gapply_get_course_groupings",
                                    args: {courseid: parseInt($("#gapplytable").data("courseid"))}
                                }])[0]
                            ]);

                            groups = groupsData;
                            rolesanddates = rolesData;
                            const groupings = groupingsData;

                            if (groups.length > 0) {
                                groups.forEach(function(group) {
                                    // Render checkbox.
                                    groupoptions += `<div class="form-check mb-2">
                                                        <input type="checkbox" class="form-check-input groups"
                                                        id="group-${group.id}" name="groups[]" value="${group.id}">
                                                    <label class="form-check-label" for="group-${group.id}">
                                                        ${group.name}
                                                    </label>
                                                    </div>`;
                                });
                            }

                            groupoptions = `<div class="mt-2">
                                    <label class="font-weight-bold" for="groups-list-container">
                                    ${M.util.get_string('assigngroups', 'enrol_gapply')}</label>
                                    <div class="gapply-groups-container" id="groups-list-container">
                                        ${groupoptions}
                                    </div>
                                    <hr>
                                    <div id="new-group-container">
                                        <button type="button" class="btn btn-outline-primary btn-block toggle-new-group">
                                            <i class="fa fa-plus-circle me-2"></i>
                                            ${M.util.get_string('createnewgroup', 'enrol_gapply')}
                                        </button>
                                        <div class="new-group-form mt-3 p-3 border rounded bg-light" style="display: none;">
                                            <div class="form-group">
                                                <label class="font-weight-bold" for="new-group-name">
                                                ${M.util.get_string('groupname', 'enrol_gapply')}</label>
                                                <input type="text" class="form-control form-control new-group-name"
                                                    id="new-group-name"
                                                    placeholder="${M.util.get_string('entergroupname', 'enrol_gapply')}">
                                            </div>
                                            <div class="form-group">
                                                <label class="font-weight-bold" for="new-grouping">
                                                ${M.util.get_string('groupingoptional', 'enrol_gapply')}</label>
                                                <select class="custom-select new-grouping w-100" id="new-grouping">
                                                    <option value="0">
                                                    ${M.util.get_string('nogrouping', 'enrol_gapply')}</option>
                                                    ${groupings.map(g => `<option value="${g.id}">${g.name}</option>`).join('')}
                                                </select>
                                            </div>
                                            <button type="button"
                                                class="btn btn-primary btn-block confirm-create-group">
                                                ${M.util.get_string('confirmcreation', 'enrol_gapply')}
                                                </button>
                                        </div>
                                    </div>
                                </div>`;
                            const roles = rolesanddates.roles;
                            if (roles.length > 0) {
                                roleoptions = `<div class="form-group mt-3">
                                            <label for="role">${M.util.get_string('assignrole', 'enrol_gapply')}</label>
                                            <select class="custom-select w-100" id="role" name="role">`;
                                roles.forEach(function(role) {
                                    // Render select.
                                    roleoptions += `<option value="${role.id}"
                                 ${role.id == rolesanddates.defaultrole ? 'selected' : ''}>${role.name}</option>`;
                                });
                                roleoptions += `</select></div>`;
                            }

                            let start = rolesanddates.startdate > 0 ?
                                new Date(rolesanddates.startdate * 1000) : "";
                            if (start != "") {
                                start = start.getFullYear() + "-"
                                    + ("0" + (start.getMonth() + 1)).slice(-2)
                                    + "-" + ("0" + start.getDate()).slice(-2)
                                    + "T" + ("0" + start.getHours()).slice(-2) + ":" + ("0" + start.getMinutes()).slice(-2);
                            }
                            startdate = `<div class="form-group mt-3">
                                            <label for="startdate">${M.util.get_string('startdate', 'enrol_gapply')}</label>
                                            <input type="datetime-local" class="form-control w-100"
                                            id="startdate" name="startdate" value="${start}">
                                            </div>`;

                            let end = rolesanddates.enddate > 0 ? new Date(rolesanddates.enddate * 1000) : "";
                            if (end != "") {
                                end = end.getFullYear() + "-"
                                    + ("0" + (end.getMonth() + 1)).slice(-2)
                                    + "-" + ("0" + end.getDate()).slice(-2)
                                    + "T" + ("0" + end.getHours()).slice(-2) + ":" + ("0" + end.getMinutes()).slice(-2);
                            }
                            enddate = `<div class="form-group mt-3">
                                            <label for="startdate">
                                            ${M.util.get_string('enddate', 'enrol_gapply')}</label>
                                            <input type="datetime-local" class="form-control w-100"
                                            id="enddate" name="enddate" value="${end}">
                                            </div>`;
                        }

                        const isBulk = ids.length > 1;
                        const titleKey = isBulk ? action + 'applications' : action + 'application';
                        const bodyKey = isBulk ? 'areyousureyouwantto' + action + '_bulk' : 'areyousureyouwantto' + action;

                        let outcomemessage = "";
                        if (action !== 'delete') {
                            const tags = '{{firstname}}, {{lastname}}, {{fullname}}, {{coursename}}, {{courseid}}';
                            outcomemessage = `<div class="form-group mt-3">
                                <label for="outcomemessage">${M.util.get_string('outcomemessage', 'enrol_gapply')}</label>
                                <textarea class="form-control w-100" id="outcomemessage" name="outcomemessage" rows="3"></textarea>
                                <small class="form-text text-muted">
                                    ${M.util.get_string('availabletags', 'enrol_gapply', tags)}
                                </small>
                            </div>`;
                        }

                        let actionModal = await Modal.create({
                            title: M.util.get_string(titleKey, 'enrol_gapply'),
                            body: `<p class="mb-0">
                                    ${M.util.get_string(bodyKey, 'enrol_gapply', ids.length)}</p>
                                    ${roleoptions}
                                    ${startdate}
                                    ${enddate}
                                    ${groupoptions}
                                    ${outcomemessage}`,
                            isVerticallyCentered: true,
                            removeOnClose: true,
                            footer: `<button class="btn btn-secondary text-uppercase font-weight-bold" data-action="hide">
                                        ${M.util.get_string('cancel', 'enrol_gapply')}</button>
                                     <button class="btn ${primarybutton} text-uppercase font-weight-bold" data-action="proceed">
                                        ${M.util.get_string('proceed', 'enrol_gapply')}</button>`
                        });

                        let actionRoot = actionModal.getRoot();
                        actionRoot.addClass('gapply-confirm-modal');

                        actionRoot.on('click', '.toggle-new-group', (e) => {
                            e.preventDefault();
                            actionRoot.find('.new-group-form').slideToggle();
                        });

                        actionRoot.on('click', '.confirm-create-group', async(e) => {
                            e.preventDefault();
                            const btn = $(e.currentTarget);
                            const name = actionRoot.find('.new-group-name').val().trim();
                            const groupingid = parseInt(actionRoot.find('.new-grouping').val());

                            if (!name) {
                                toast.add(M.util.get_string('entergroupnameerror', 'enrol_gapply'), {
                                    type: 'danger'
                                });
                                return;
                            }

                            btn.prop('disabled', true).html('<i class="fa fa-circle-o-notch fa-spin"></i> '
                                + M.util.get_string('creating', 'enrol_gapply'));

                            try {
                                const newGroups = await Ajax.call([{
                                    methodname: 'enrol_gapply_create_groups',
                                    args: {
                                        groups: [{
                                            courseid: parseInt($('#gapplytable').data('courseid')),
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
                                        <input type="checkbox" class="form-check-input groups"
                                            id="group-${newGroup.id}" name="groups[]" value="${newGroup.id}" checked>
                                        <label class="form-check-label" for="group-${newGroup.id}">${newGroup.name}</label>
                                    </div>
                                `;
                                actionRoot.find('#groups-list-container').append(checkboxHtml);

                                // Sort the groups by name
                                const groups = actionRoot.find('#groups-list-container .form-check');
                                groups.sort((a, b) => {
                                    const nameA = $(a).find('label').text();
                                    const nameB = $(b).find('label').text();
                                    return nameA.localeCompare(nameB);
                                });
                                actionRoot.find('#groups-list-container').empty().append(groups);

                                // Reset and hide form.
                                actionRoot.find('.new-group-name').val('');
                                actionRoot.find('.new-grouping').val('0');
                                actionRoot.find('.new-group-form').slideUp();
                                btn.prop('disabled', false).text(M.util.get_string('confirmcreation', 'enrol_gapply'));

                            } catch (error) {
                                btn.prop('disabled', false).text(M.util.get_string('confirmcreation', 'enrol_gapply'));
                                Notification.exception(error);
                            }
                        });

                        actionRoot.find('[data-action="proceed"]').on('click', async function() {
                            const $btn = $(this);
                            $btn.prop('disabled', true).append('<i class="fa fa-circle-o-notch fa-spin ml-1 ms-1"></i>');

                            const selectedGroups = actionRoot.find('input.groups:checked').map(function() {
                                return parseInt(this.value);
                            }).get();

                            try {
                                await Ajax.call([{
                                    methodname: "enrol_gapply_manage_applications",
                                    args: {
                                        action: action,
                                        ids: ids.map(aid => parseInt(aid)),
                                        instanceid: parseInt(id),
                                        roleid: parseInt(actionRoot.find('select#role').val() || 0),
                                        start: actionRoot.find('input#startdate').val() != '' ?
                                            Math.floor(new Date(actionRoot.find('input#startdate').val()).getTime() / 1000) : 0,
                                        end: actionRoot.find('input#enddate').val() != '' ?
                                            Math.floor(new Date(actionRoot.find('input#enddate').val()).getTime() / 1000) : 0,
                                        groups: selectedGroups,
                                        message: actionRoot.find('#outcomemessage').val() || ''
                                    }
                                }])[0];

                                const isBulk = ids.length > 1;
                                let nextAppId = null;

                                // If we are in the detail modal, find the next application before removing the current one.
                                if (!isBulk && detailModal && detailModal.isVisible()) {
                                    const $nextBtn = detailRoot.find('.nav-button[data-action="next"]');
                                    if ($nextBtn.is(':enabled')) {
                                        nextAppId = $nextBtn.attr('data-nextid');
                                    } else {
                                        const $prevBtn = detailRoot.find('.nav-button[data-action="prev"]');
                                        if ($prevBtn.is(':enabled')) {
                                            nextAppId = $prevBtn.attr('data-previd');
                                        }
                                    }
                                }

                                // Remove from table if it exists.
                                if (table) {
                                    ids.forEach(function(aid) {
                                        const row = $(`#gapplytable [data-id="${aid}"]`).closest("tr");
                                        if (row.length) {
                                            table.row(row).remove().draw();
                                        }
                                    });
                                    table.rows().deselect();
                                }

                                const stringKey = isBulk ? action + 'success_bulk' : action + 'success';
                                toast.add(M.util.get_string(stringKey, 'enrol_gapply', ids.length), {
                                    type: 'success',
                                    subtitle: M.util.get_string(action + 'application', 'enrol_gapply')
                                });

                                $('#selectall').prop('checked', false);
                                $('#gapply-floating-toolbar').removeClass('show');

                                actionModal.hide();

                                // Navigation logic for single view.
                                if (!isBulk && detailModal && detailModal.isVisible()) {
                                    if (nextAppId) {
                                        loadApplicationData(nextAppId);
                                    } else {
                                        detailModal.hide();
                                    }
                                }
                            } catch (error) {
                                $btn.prop('disabled', false).find('.fa-spin').remove();
                                Notification.exception(error);
                            }
                        });

                        actionModal.show();
                    };

                    $(document).on('click', '.action-button', async function() {
                        if ($(this).closest('.gapply-modal').length) {
                            return;
                        }
                        const action = $(this).data("action");
                        let $this = $(this);
                        let ids = selecteddata;
                        if ($this.hasClass("menu-action")) {
                            ids = [$this.data("id")];
                        }
                        showActionModal(action, ids);
                    });

                    let latestRowsData = [];
                    const loadApplicationData = async(applicationid, searchData = null) => {
                        let userid, status, applicationtext, attachments, index, nextid, previd, total;

                        if (searchData) {
                            userid = searchData.userid;
                            status = searchData.status;
                            applicationtext = searchData.applytext;
                            attachments = searchData.attachments;
                            index = 0;
                            total = 1;
                            nextid = null;
                            previd = null;
                        } else {
                            // Find row in latestRowsData.
                            if (table) {
                                latestRowsData = table.rows({search: 'applied', order: 'applied'}).data().toArray();
                            } else {
                                latestRowsData = [];
                            }
                            const row = latestRowsData.find((item) => item[1] == applicationid);
                            if (!row) {
                                return;
                            }
                            userid = row[row.length - 2];
                            status = row[row.length - 6];

                            selecteddata = [applicationid];

                            index = latestRowsData.findIndex((item) => item[1] == applicationid);
                            nextid = latestRowsData[index + 1] ? latestRowsData[index + 1][1] : null;
                            previd = latestRowsData[index - 1] ? latestRowsData[index - 1][1] : null;
                            total = latestRowsData.length;

                            applicationtext = $(".applicationtext[data-id='" + applicationid + "']").html();
                            attachments = [];
                            $('#gapplytable a.attachmentlink[data-id="' + applicationid + '"]').each(function() {
                                const file = {
                                    'link': $(this).data('url'),
                                    'filename': $(this).text(),
                                    'type': $(this).data('type'),
                                };
                                attachments.push(file);
                            });
                        }

                        try {
                            if (!detailModal) {
                                const modalType = isModern ? Modal.TYPE : Modal.types.DEFAULT;
                                detailModal = await Modal.create({type: modalType, large: true});
                                detailRoot = detailModal.getRoot();
                                detailRoot.addClass('gapply-modal');
                                detailRoot.find('.modal-content').append(`<div class="gapply-modal-loading-overlay">
                                    <div class="spinner-border text-primary"></div></div>`);

                                detailRoot.find('.modal-title').addClass('w-100');

                                detailRoot.on(ModalEvents.hidden, function() {
                                    document.title = originalTitle;
                                    const url = new URL(window.location.href);
                                    url.searchParams.delete('aid');
                                    window.history.replaceState({}, '', url.toString());
                                });

                                detailRoot.on('click', '.show-outcome-message', function(e) {
                                    e.preventDefault();
                                    $(this).next('.outcome-message-content').slideToggle('fast');
                                    $(this).remove();
                                });
                            }

                            if (detailModal.isVisible()) {
                                detailRoot.addClass('is-loading');
                            }

                            const response = await Ajax.call([{
                                methodname: "enrol_gapply_get_user_summary",
                                args: {userid: parseInt(userid), instanceid: parseInt(id)}
                            }])[0];


                            const restoreWidth = () => {
                                const leftPane = detailRoot.find('#modal-left-pane');
                                const savedWidth = localStorage.getItem('enrol_gapply_modal_left_pane_width');
                                if (leftPane.length) {
                                    const widthValue = savedWidth ? (savedWidth + '%') : '66.66%';
                                    leftPane.css({
                                        'width': widthValue,
                                        'flex': 'none'
                                    });
                                }
                            };

                            const parsedAttachments = attachments.map(a => ({
                                url: a.link,
                                filename: a.filename,
                                mimetype: a.type
                            }));

                            const [headerHtml, bodyHtml] = await Promise.all([
                                Templates.render('enrol_gapply/modal_header', {
                                    user: response.user,
                                    statushtml: status,
                                    isapproved: tab === 'approved',
                                    iswaitlisted: tab === 'waitlisted',
                                    isrejected: tab === 'rejected',
                                    index: index + 1,
                                    total: total,
                                    nextid: nextid,
                                    previd: previd,
                                    applicationid: applicationid
                                }),
                                Templates.render('enrol_gapply/modal_body', {
                                    user: response.user,
                                    identity: response.identity,
                                    applytext: applicationtext,
                                    attachments: parsedAttachments
                                })
                            ]);

                            if (detailModal && detailModal.isVisible()) {
                                // Update header and body.
                                document.title = response.user.fullname;
                                const url = new URL(window.location.href);
                                url.searchParams.set('aid', applicationid);
                                window.history.replaceState({}, '', url.toString());
                                detailRoot.find('.modal-title').replaceWith(headerHtml);
                                detailModal.setBody(bodyHtml);
                                restoreWidth();
                                detailRoot.find('#fileselect').trigger('change');
                                detailRoot.removeClass('is-loading');
                            } else {
                                document.title = response.user.fullname;
                                const url = new URL(window.location.href);
                                url.searchParams.set('aid', applicationid);
                                window.history.replaceState({}, '', url.toString());
                                detailRoot.find('.modal-title').replaceWith(headerHtml);
                                detailModal.setBody(bodyHtml);
                                detailRoot.off(ModalEvents.shown).on(ModalEvents.shown, function() {
                                    restoreWidth();
                                    detailRoot.find('#fileselect').trigger('change');
                                    detailRoot.removeClass('is-loading');
                                });
                                detailModal.show();
                            }

                            return response;
                        } catch (error) {
                            Notification.exception(error);
                        }
                    };

                    $(document).on('click', '.showuserdetail', function(e) {
                        e.preventDefault();
                        loadApplicationData($(this).data("id"));
                    });

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
                            const deltaX = moveEvent.clientX - startX;
                            let newWidth = startWidth + deltaX;

                            if (newWidth < 300) {
                                newWidth = 300;
                            }
                            if (newWidth > modalWidth - 250) {
                                newWidth = modalWidth - 250;
                            }

                            leftPane.css({
                                'width': newWidth + 'px',
                                'flex': 'none'
                            });
                        });

                        $(document).on('mouseup.resizer', function() {
                            if (isResizing) {
                                isResizing = false;
                                $('body').removeClass('resizing');

                                const currentWidth = leftPane[0].getBoundingClientRect().width;
                                const percentage = (currentWidth / modalWidth) * 100;
                                localStorage.setItem('enrol_gapply_modal_left_pane_width', percentage);
                                leftPane.css('width', percentage + '%');

                                $(document).off('mousemove.resizer mouseup.resizer');
                            }
                        });
                    });

                    $(document).on('click', '.gapply-modal .collapsible-header', function() {
                        const section = $(this).closest('.collapsible-section');
                        section.toggleClass('collapsed');
                    });

                    $(document).on('change', '.gapply-modal #fileselect', function() {
                        const selected = $(this).find(':selected');
                        let vurl = $(this).val();
                        let vtype = selected.data('type') || '';
                        let vname = selected.text();

                        let vhtml = !vurl ? `<p class="text-center py-5">
                            ${M.util.get_string('noattachments', 'enrol_gapply')}</p>` : '';
                        if (vurl) {
                            if (vtype.includes("image")) {
                                vhtml = `<img src="${vurl}" class="img-fluid mx-auto" alt="${vname}">`;
                            } else if (vtype.includes("video")) {
                                vhtml = `<video src="${vurl}" controls width="100%" autoplay></video>`;
                            } else if (vtype.includes("audio")) {
                                vhtml = `<audio src="${vurl}" controls width="100%" autoplay></audio>`;
                            } else if (vtype.includes("pdf")) {
                                vhtml = `<object data="${vurl}" type="application/pdf"
                                    width="100%" style="height: 100%"></object>`;
                            } else {
                                const encodedUrl = encodeURIComponent(vurl);
                                vhtml = `<iframe
                                            src="https://docs.google.com/viewer?url=${encodedUrl}&embedded=true"
                                            style="width: 100%; height: 100%; border: none;"></iframe>`;
                            }
                        }
                        $('.gapply-modal #viewer').html(vhtml);
                    });

                    $(document).on('click', '.gapply-modal .action-button', function(e) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        const actionName = $(this).data('action');
                        const currentId = $('.gapply-modal .nav-button').attr('data-currentid');
                        showActionModal(actionName, [currentId]);
                    });

                    $(document).on('click', '.gapply-modal .nav-button', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        const $btn = $(this);
                        if ($btn.prop('disabled')) {
                            return;
                        }
                        const action = $btn.data('action');
                        const nextIdx = action === 'next' ? $btn.attr('data-nextid') : $btn.attr('data-previd');
                        if (nextIdx) {
                            loadApplicationData(nextIdx);
                        }
                    });

                    const searchApplication = async(appId) => {
                        if (!/^\d+$/.test(appId)) {
                            toast.add(M.util.get_string('error', 'enrol_gapply'), {type: 'danger'});
                            return;
                        }

                        try {
                            const response = await Ajax.call([{
                                methodname: 'enrol_gapply_get_application_info',
                                args: {applicationid: parseInt(appId), instanceid: parseInt(id)}
                            }])[0];

                            if (response.found) {
                                const searchData = {
                                    userid: response.userid,
                                    status: response.status,
                                    applytext: response.applytext,
                                    attachments: response.attachments
                                };
                                loadApplicationData(parseInt(appId), searchData);
                            } else {
                                toast.add(M.util.get_string('nofound', 'enrol_gapply'), {type: 'danger'});
                                const url = new URL(window.location.href);
                                url.searchParams.delete('aid');
                                window.history.replaceState({}, '', url.toString());
                            }
                        } catch (error) {
                            Notification.exception(error);
                        }
                    };

                    $(document).on('keypress', '#application-search-input', function(e) {
                        if (e.which === 13) {
                            e.preventDefault();
                            searchApplication($(this).val());
                        }
                    });

                    $(document).on('click', '#selectall', function() {
                        if (!table) {
                            return;
                        }
                        if ($(this).is(":checked")) {
                            table.rows({
                                search: 'applied'
                            }).select();
                        } else {
                            table.rows().deselect();
                        }
                    });

                    // Check for aid in URL on page load.
                    const urlParams = new URLSearchParams(window.location.search);
                    const aid = urlParams.get('aid');
                    if (aid) {
                        searchApplication(aid);
                    }
                });
            }
        };
    });
