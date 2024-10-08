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
    ], function($, JSZip, toast) {
        window.JSZip = JSZip;
        return {
            init: function(tab, id) {
                $('body').append(`<div id="enrol-gapply-loading" class="d-none align-items-center justify-content-center
                     position-fixed w-100 h-100"
    style="top: 0;bottom: 0; left: 0; right: 0; z-index: 9999; background: rgba(0,0,0,0.5);">
    <div class="spinner-grow text-light" style="width: 3rem; height: 3rem;" role="status">
    <span class="sr-only">Loading...</span></div></div>`);

                $(document).on('click', 'a[data-type]', function() {
                    let modal = `<div class="modal fade" id="applyfile" data-backdrop="static"
                     data-keyboard="false" tabindex="-1" aria-labelledby="applyfileLabel" aria-modal="true" role="dialog">
                            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="applyfileLabel"></h5>
                                        <button class="close" data-dismiss="modal" aria-label="Close">
                                        <i class="fa fa-times" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <div class="modal-body p-0 text-center d-flex justify-content-center">
                                    </div>
                                    <div class="modal-footer">
                                        <a href="javascript:void(0)" id="forcedownloadbutton"
                                            class="btn btn-primary text-uppercase font-weight-bold">
                                            ${M.util.get_string("download", "enrol_gapply")}</a>
                                        <button class="btn btn-secondary text-uppercase font-weight-bold"
                                            data-dismiss="modal">${M.util.get_string("close", "enrol_gapply")}</button>
                                    </div>
                                </div>
                            </div>
                    </div>`;
                    // Remove existing modal.
                    $("#applyfile").remove();
                    $("body").append(modal);
                    $("#applyfileLabel").html($(this).text());
                    $("#applyfile").modal("show");
                    let html = "";
                    if ($(this).data("type").includes("image")) {
                        html = `<img src="${$(this).data("url")}" class="img-fluid mx-auto">`;
                        $("#applyfile .modal-body").removeClass("d-flex");
                    } else if ($(this).data("type").includes("video")) {
                        html = `<video src="${$(this).data("url")}"
                    class="embed-responsive-item text-center m-0" controls width="100%" autoplay></video>`;
                    } else if ($(this).data("type").includes("audio")) {
                        html = `<audio src="${$(this).data("url")}"
                    class="embed-responsive-item text-center m-0" controls width="100%" autoplay></audio>`;
                    } else if ($(this).data("type").includes("pdf")) {
                        html = `<object data="${$(this).data("url")}" type="application/pdf" width="100%" style="height: 80vh">
                        <p>${M.util.get_string('cannotopenpdffile', 'enrol_gapply', $(this).data("url"))}</p></object>`;
                    } else if ($(this).data("type").includes("officedocument") || $(this).data("type").includes("msword")
                        || $(this).data("type").includes("ms-excel")
                        || $(this).data("type").includes("ms-powerpoint")
                        || $(this).data("type").includes("openxmlformats")) {
                        html = `<iframe id="fileviewer"
                        src="https://view.officeapps.live.com/op/embed.aspx?src=${$(this).data("url")}"
                        class="embed-responsive-item" style="width: 100%; height: 80vh"></iframe>`;
                    } else if ($(this).data("type").includes("text") || $(this).data("type").includes("csv")) {
                        html = `<iframe id="fileviewer"
                    src="https://docs.google.com/viewer?url=${$(this).data("url")}&embedded=true"
                    class="embed-responsive-item" style="width: 100%; height: 80vh; border-radius: 0"></iframe>`;
                    } else {
                        html = `<p class="text-center py-5">${M.util.get_string('cannotopenfile', 'enrol_gapply',
                            $(this).data("url"))}</p>`;
                        $("#applyfile .modal-body").removeClass("d-flex");
                    }
                    $("#applyfile .modal-body").html(html);

                    let newURL = new URL($(this).data("url"));

                    $("#applyfile").on("click", "#forcedownloadbutton", function() {
                        newURL.searchParams.append("forcedownload", 1);
                        window.open(newURL.toString());
                    });
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

                const renderFilterBox = (index, text) => {
                    return `<div class="col-sm-6 col-md-4 col-lg-3 col-xl-2 pl-0 pr-2">
                            <div class="form-group mb-1">
                                <label for="filter-${index}">${text}</label>
                                <input type="text" class="form-control form-control-sm" id="filter-${index}" data-index="${index}"/>
                            </div>
                        </div>`;
                };

                let option = {
                    ajax: {
                        url: M.cfg.wwwroot
                            + `/enrol/gapply/ajax.php?id=${id}&action=getapplications&tab=${tab}&sesskey=${M.cfg.sesskey}`,
                        dataSrc: function(json) {
                            return json;
                        },
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
                        visible: $("#gapplytable").hasClass("approved") ? false : true
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
                             href="javascript:void(0)" id="filters" data-toggle="tooltip"
                             title="Filter"><i class="fa fa-filter left fa-fw"></i></a>`).insertAfter(".dataTables_filter label");
                        $(document).off('click', '#filters').on('click', '#filters', function() {
                            $('#filterregion').slideToggle('fast', 'swing');
                        });
                        $('#filterregion').css('display', 'none');
                        profileFields.forEach((element) => {
                            $(renderFilterBox(element.index, element.text)).appendTo("#filterregion");
                        });
                        // Create sort dropdown
                        $(`<div class="dropdown d-inline right small">
                    <button class="btn btn-sm btn-secondary dropdown-toggle font-weight-bold ml-1"
                     id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                     <i class="fa fa-sort fa-fw"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" id="sortdropdown" aria-labelledby="dropdownMenuButton">
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

                if (!$("#gapplytable").hasClass("approved")) {
                    option.select = {
                        style: 'os',
                        selector: 'td:first-child'
                    };
                }

                let table = $('#gapplytable').DataTable(option);

                // Handle filter event
                $('body').on('keyup', '#filterregion input', function() {
                    const index = $(this).data('index');
                    const value = $(this).val();
                    table.column(index).search(value, false, true).draw();
                });

                // Handle sort event
                $("body").on("click", "#sortdropdown.dropdown-menu a", function() {
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

                let getRowData = (table) => {
                    $(".action-button:not(.menu-action)").remove();
                    selecteddata = table.rows({selected: true}).data().toArray().map(row => row[1]);
                    if (selecteddata.length > 0) {
                        $("#gapplytable_length label").after(`
                            <button class="btn btn-sm alert-success action-button" data-action="approve" data-toggle="tooltip"
                             title="${M.util.get_string("approve", "enrol_gapply")}"><i class="fa fa-fw fa-check"></i></button>
                            <button class="btn btn-sm alert-info action-button" data-action="waitlist" data-toggle="tooltip"
                             title="${M.util.get_string("waitlist", "enrol_gapply")}"><i class="fa fa-fw fa-clock-o"></i></button>
                            <button class="btn btn-sm alert-warning action-button" data-action="reject" data-toggle="tooltip"
                             title="${M.util.get_string("reject", "enrol_gapply")}"><i class="fa fa-fw fa-times"></i></button>
                            <button class="btn btn-sm alert-danger action-button" data-action="delete" data-toggle="tooltip"
                             title="${M.util.get_string("delete", "enrol_gapply")}"><i class="fa fa-fw fa-trash"></i></button>`
                        );
                    } else {
                        $(".action-button:not(.menu-action)").remove();
                    }
                };

                table.on('select', function(e, dt, type, indexes) {
                    getRowData(dt, indexes);
                });
                table.on('deselect', function(e, dt, type, indexes) {
                    getRowData(dt, indexes);
                });

                $(document).on('click', '.action-button', async function() {
                    const action = $(this).data("action");
                    let primarybutton = "btn-success";
                    if (action == "waitlist") {
                        primarybutton = "btn-info";
                    } else if (action == "reject") {
                        primarybutton = "btn-warning";
                    } else if (action == "delete") {
                        primarybutton = "btn-danger";
                    }
                    let $this = $(this);
                    if ($this.hasClass("menu-action")) {
                        selecteddata = [$this.data("id")];
                    }

                    // If action is approve, we have to get groups and assignable roleids.
                    let groups = [];
                    let rolesanddates;
                    let roleoptions = "";
                    let groupoptions = "";
                    let startdate = "";
                    let enddate = "";
                    if (action == "approve") {
                        // Get list of groups as a promise
                        groups = await $.ajax({
                            method: "POST",
                            url: M.cfg.wwwroot + "/enrol/gapply/ajax.php",
                            data: {
                                action: "getgroups",
                                id: $("#gapplytable").data("instance"),
                                sesskey: M.cfg.sesskey,
                            },
                            dataType: "json",
                        });
                        // Get list of roles as a promise
                        rolesanddates = await $.ajax({
                            method: "POST",
                            url: M.cfg.wwwroot + "/enrol/gapply/ajax.php",
                            data: {
                                action: "getrolesanddates",
                                id: $("#gapplytable").data("instance"),
                                sesskey: M.cfg.sesskey
                            },
                            dataType: "json",
                        });

                        if (groups.length > 0) {
                            groups.forEach(function(group) {
                                // Render checkbox.
                                groupoptions += `<div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input groups"
                                                    id="group-${group.id}" name="groups[]" value="${group.id}">
                                                <label class="custom-control-label" for="group-${group.id}">${group.name}</label>
                                                </div>`;
                            });

                            groupoptions = `<div class="form-group mt-3">
                                                    <label for="groups">${M.util.get_string('assigngroups', 'enrol_gapply')}</label>
                                                    <div id="groups">${groupoptions}</div>
                                                </div>`;
                        }
                        const roles = rolesanddates.roles;
                        const roleids = Object.keys(roles);
                        if (roleids.length > 0) {
                            roleoptions = `<div class="form-group mt-3">
                                        <label for="role">${M.util.get_string('assignrole', 'enrol_gapply')}</label>
                                        <select class="custom-select w-100" id="role" name="role">`;
                            roleids.forEach(function(roleid) {
                                // Render select.
                                roleoptions += `<option value="${roleid}"
                             ${roleid == rolesanddates.defaultrole ? 'selected' : ''}>${roles[roleid]}</option>`;
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

                    let modal = `<div class="modal fade" id="approveModal" tabindex="-1" role="dialog"
                         aria-labelledby="approveModalLabel"  aria-hidden="true" style="background: rgba(0,0,0,0.5);">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="approveModalLabel">
                                                ${M.util.get_string(action + 'applications', 'enrol_gapply')}</h5>
                                                <button class="close" data-dismiss="modal" aria-label="Close">
                                                <i class="fa fa-fw fa-times"></i>
                                                </button>
                                                </div>
                                            <div class="modal-body">
                                            <p class="mb-0">${M.util.get_string('areyousureyouwantto' + action, 'enrol_gapply')}</p>
                                                ${roleoptions}
                                                ${startdate}
                                                ${enddate}
                                                ${groupoptions}
                                            </div>
                                            <div class="modal-footer">
                                                <button class="btn btn-secondary text-uppercase font-weight-bold"
                                                 data-dismiss="modal">${M.util.get_string('cancel', 'enrol_gapply')}</button>
                                                <button class="btn ${primarybutton} text-uppercase font-weight-bold"
                                                 id="proceed">${M.util.get_string('proceed', 'enrol_gapply')}</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                    // Remove any existing modal
                    $("#approveModal").remove();
                    $("body").append(modal);
                    $("#approveModal").modal("show");

                    // Approve.
                    $("#approveModal #proceed").click(function() {
                        $("#approveModal").modal("hide");
                        $("#enrol-gapply-loading").toggleClass("d-none d-flex");
                        $.ajax({
                            url: M.cfg.wwwroot + "/enrol/gapply/ajax.php",
                            method: "POST",
                            dataType: "text",
                            data: {
                                action: action,
                                ids: selecteddata.toString(),
                                id: $("#gapplytable").data("instance"),
                                groups: $("#approveModal input.groups:checked").map(function() {
                                    return this.value;
                                }).get().toString(),
                                roleid: $("#approveModal select#role").val(),
                                start: $("#approveModal input#startdate").val() != '' ?
                                    new Date($("#approveModal input#startdate").val()).getTime() / 1000 : 0,
                                end: $("#approveModal input#enddate").val() != '' ?
                                    new Date($("#approveModal input#enddate").val()).getTime() / 1000 : 0,
                                sesskey: M.cfg.sesskey,
                            },
                            success: function(response) {
                                window.console.log(response);
                                if (response == "success") {
                                    selecteddata.forEach(function(id) {
                                        table.row($(`[data-id="${id}"]`).closest("tr")).remove().draw();
                                    });
                                    toast.add(M.util.get_string(action + 'success', 'enrol_gapply'), {
                                        type: 'success',
                                    });
                                } else {
                                    toast.add(M.util.get_string('anerroroccurred', 'enrol_gapply'), {
                                        type: 'danger',
                                    });
                                }
                            },
                            error: function() {
                                toast.add(M.util.get_string('anerroroccurred', 'enrol_gapply'), {
                                    type: 'danger',
                                });
                            },
                            complete: function() {
                                $("#enrol-gapply-loading").toggleClass("d-none d-flex");
                                $('#userdetailModal').modal('hide');
                                // Unselect all rows
                                table.rows().deselect();
                            }
                        });
                    });
                });

                $(document).on('click', '.showuserdetail', function() {
                    $("#enrol-gapply-loading").toggleClass("d-none d-flex");
                    const userid = $(this).data("userid");
                    const applicationid = $(this).data("id");
                    const status = $(this).data("statusformatted");
                    const sts = $(this).data("status");
                    selecteddata = [applicationid];
                    const applicationtext = $(".applicationtext[data-id='" + applicationid + "']").html();
                    let attachments = [];
                    $('#gapplytable a.attachmentlink[data-id="' + applicationid + '"]').each(function() {
                        const file = {
                            'link': $(this).data('url'),
                            'filename': $(this).text(),
                            'type': $(this).data('type'),
                        };
                        attachments.push(file);
                    });
                    let select = "";
                    if (attachments.length > 0) {
                        // Select input
                        select = `<select class="custom-select w-100 my-2" id="fileselect">`;
                        attachments.forEach(function(attachment) {
                            select += `<option data-type="${attachment.type}"
                             value="${attachment.link}">${attachment.filename}</option>`;
                        });
                        select += `</select>`;
                    }

                    // AJAX request to get user details.
                    $.ajax({
                        url: M.cfg.wwwroot
                            + `/enrol/gapply/ajax.php?action=getuserbyid&id=${id}&userid=${userid}&sesskey=${M.cfg.sesskey}`,
                        type: 'GET',
                        dataType: 'text',
                        success: function(response) {
                            let modal = `<div class="modal fade p-0" id="userdetailModal" tabindex="-1" role="dialog"
                             aria-labelledby="userdetailModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-xl" role="document" style="max-width: calc(100% - 2rem);">
                                <div class="modal-content">
                                ${response}
                                </div>
                            </div>
                        </div>`;
                            $("#userdetailModal").remove();
                            $("body").append(modal);
                            $("#userdetailModal").modal("show");
                            if (sts == "approved") {
                                $("#userdetailModal .action-button").remove();
                            }

                            $("#userdetailModal .select-input").html(select);
                            $("#userdetailModal #applicationtext").html(applicationtext);
                            $("#userdetailModal #currentstatus").html(status);

                            let changefile = function(url, type, name) {
                                $(".fileview #viewer").html(name);
                                let html = '';
                                if (type.includes("image")) {
                                    html = `<img src="${url}" class="img-fluid mx-auto">`;
                                } else if (type.includes("video")) {
                                    html = `<video src="${url}" class="embed-responsive-item text-center m-0"
                                     controls width="100%" autoplay></video>`;
                                } else if (type.includes("audio")) {
                                    html = `<audio src="${url}" class="embed-responsive-item text-center m-0" controls width="100%"
                                     autoplay></audio>`;
                                } else if (type.includes("pdf")) {
                                    html = `<object data="${url}" type="application/pdf" width="100%"
                                     style="height: calc(100% - 7px);"><p>${M.util.get_string('cannotopenpdffile',
                                        'enrol_gapply', url)}</p></object>`;
                                } else if (type.includes("officedocument") || type.includes("msword")
                                    || type.includes("ms-excel") || type.includes("ms-powerpoint")
                                    || type.includes("openxmlformats")) {
                                    html = `<iframe id="fileviewer"
        src="https://view.officeapps.live.com/op/embed.aspx?src=${url}"
        class="embed-responsive-item" style="width: 100%; height: calc(100% - 7px);"></iframe>`;
                                } else if (type.includes("text") || type.includes("csv")) {
                                    html = `<iframe id="fileviewer"
        src="https://docs.google.com/viewer?url=${url}&embedded=true"
        class="embed-responsive-item" style="width: 100%; height:  calc(100% - 7px); border-radius: 0"></iframe>`;
                                } else {
                                    html = `<p class="text-center py-5">
                                    ${M.util.get_string('cannotopenfile', 'enrol_gapply', url)}</p>`;
                                }
                                $(".fileview #viewer").html(html);
                                $("#userdetailmodalLabel").html(name);
                                $("#downloadbutton").attr("href", url);
                            };

                            $("#fileselect").change(function() {
                                changefile($(this).val(), $(this).find(":selected").data("type"), $(this).find(":selected").text());
                            });

                            $("#fileselect").trigger("change");
                        },
                        error: function() {
                            toast.add(M.util.get_string('anerroroccurred', 'enrol_gapply'), {
                                type: 'danger'
                            });
                        },
                        complete: function() {
                            $("#enrol-gapply-loading").toggleClass("d-none d-flex");
                        }
                    });
                });

                $(document).on('click', '#selectall', function() {
                    if ($(this).is(":checked")) {
                        table.rows({
                            search: 'applied'
                        }).select();
                    } else {
                        table.rows().deselect();
                    }
                });
            }
        };
    });