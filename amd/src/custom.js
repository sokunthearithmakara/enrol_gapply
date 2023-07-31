/* eslint-disable no-unused-vars */
/* eslint-disable no-empty-function */
/* eslint-disable no-undef */
/* eslint-disable max-len */
/**
 * Available modules not used: enrol_gapply/select2
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
                // Get current language
                $("body").on('click', 'a[data-type]', function(e) {
                    var modal = `<div class="modal fade" id="applyfile" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="applyfileLabel" aria-modal="true" role="dialog">
                                                    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="applyfileLabel"></h5>
                                                                <button class="close" data-dismiss="modal" aria-label="Close">
                                                                <i class="fa fa-times" aria-hidden="true"></i>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body p-0 text-center">
                                                            </div>
                                                            <div class="modal-footer">
                                                                <a href="javascript:void(0)" id="forcedownloadbutton"
                                                                    class="btn btn-primary text-uppercase font-weight-bold">${M.util.get_string("download", "enrol_gapply")}</a>
                                                                <button class="btn btn-secondary text-uppercase font-weight-bold"
                                                                    data-dismiss="modal">${M.util.get_string("close", "enrol_gapply")}</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                            </div>`;
                    // Remove existing modal
                    $("#applyfile").remove();
                    $("body").append(modal);
                    $("#applyfileLabel").html($(this).text());
                    $("#applyfile").modal("show");
                    if ($(this).data("type").includes("image")) {
                        $("#applyfile .modal-body").html(`<img src="${$(this).data("url")}" class="img-fluid mx-auto">`);
                    } else if ($(this).data("type").includes("video")) {
                        $("#applyfile .modal-body").html(`<video src="${$(this).data("url")}"
                    class="embed-responsive-item text-center m-0" controls width="100%" autoplay></video>`);
                    } else if ($(this).data("type").includes("audio")) {
                        $("#applyfile .modal-body").html(`<audio src="${$(this).data("url")}"
                    class="embed-responsive-item text-center m-0" controls width="100%" autoplay></audio>`);
                    } else if ($(this).data("type").includes("pdf")) {
                        $("#applyfile .modal-body").html(`<object data="${$(this).data("url")}" type="application/pdf"
                     width="100%" style="height: 80vh">
                                            <p>${M.util.get_string('cannotopenpdffile', 'enrol_gapply', $(this).data("url"))}</p>
                                            </object>`);
                    } else if ($(this).data("type").includes("officedocument") || $(this).data("type").includes("msword")
                        || $(this).data("type").includes("ms-excel") || $(this).data("type").includes("ms-powerpoint") || $(this).data("type").includes("openxmlformats")) {
                        $("#applyfile .modal-body").html(`<iframe id="fileviewer"
                    src="https://view.officeapps.live.com/op/embed.aspx?src=${$(this).data("url")}"
                    class="embed-responsive-item" style="width: 100%; height: 80vh"></iframe>`);
                    } else if ($(this).data("type").includes("text") || $(this).data("type").includes("csv")) {
                        $("#applyfile .modal-body").html(`<iframe id="fileviewer"
                    src="https://docs.google.com/viewer?url=${$(this).data("url")}&embedded=true"
                    class="embed-responsive-item" style="width: 100%; height: 80vh; border-radius: 0"></iframe>`);
                    } else {
                        $("#applyfile .modal-body").html(`<p
                    class="text-center py-5">${M.util.get_string('cannotopenfile', 'enrol_gapply', $(this).data("url"))}</p>`);
                    }

                    var newURL = new URL($(this).data("url"));

                    $("#applyfile").on("click", "#forcedownloadbutton", function(e) {
                        newURL.searchParams.append("forcedownload", 1);
                        window.open(newURL.toString());
                    });
                });

                var timecreatedIndex = $("th").index($("th.timecreated"));
                var profileFields = [];
                $("th.profilefield").each(function(index, element) {
                    var pr = {
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

                var option = {
                    ajax: {
                        url: M.cfg.wwwroot + '/enrol/gapply/ajax.php' + `?id=${id}&action=getapplications&tab=${tab}&sesskey=${M.cfg.sesskey}`,
                        dataSrc: function(json) {
                            return json;
                        },
                    },
                    deferRender: true,
                    rowId: 'id',
                    dom: `<'d-flex align-items-start justify-content-between'<'d-flex align-items-start'Bl>f><'#filterregion.w-100 row'>t<'row'<'col-sm-6'i><'col-sm-6'p>>`,
                    buttons: [
                        // Copy button
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
                        // Csv button
                        {
                            extend: "csvHtml5",
                            text: '<i class="fa fa-file-csv fa-fw"></i>',
                            titleAttr: "",
                            className: "btn btn-sm btn-alt-primary",
                            exportOptions: {
                                columns: ['.exportable']
                            }
                        },
                        // Excel button
                        {
                            extend: "excelHtml5",
                            text: '<i class="fa fa-file-excel fa-fw"></i>',
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
                        $(`<a class="btn btn-sm btn-secondary font-weight-bold ml-1" href="javascript:void(0)" id="filters" data-toggle="tooltip" title="Filter" onclick="$('#filterregion').slideToggle('fast', 'swing')"><i class="fa fa-filter left fa-fw"></i></a>`).insertAfter(".dataTables_filter label");
                        $('#filterregion').css('display', 'none');
                        profileFields.forEach((element) => {
                            $(renderFilterBox(element.index, element.text)).appendTo("#filterregion");
                        });
                        // Create sort dropdown
                        $(`<div class="dropdown d-inline right small">
                    <button class="btn btn-sm btn-secondary dropdown-toggle font-weight-bold ml-1" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-sort fa-fw"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" id="sortdropdown" aria-labelledby="dropdownMenuButton">

                    </div>
                </div>`).insertAfter("#filters");
                        profileFields.forEach((element) => {
                            $('#sortdropdown').append(`<a class="dropdown-item" href="javascript:void(0)" data-col="${element.index}">${element.text}</a>`);
                        });
                        $('#sortdropdown').append(`<a class="dropdown-item" href="javascript:void(0)" data-col="${timecreatedIndex}">${M.util.get_string('timecreated', 'enrol_gapply')}</a><div class="dropdown-divider"></div>
                        <a class="dropdown-item active" href="javascript:void(0)" data-order="desc">${M.util.get_string('desc', "enrol_gapply")}</a>
                        <a class="dropdown-item" href="javascript:void(0)" data-order="asc">${M.util.get_string('asc', "enrol_gapply")}</a>`);
                    },
                };

                if (!$("#gapplytable").hasClass("approved")) {
                    option.select = {
                        style: 'os',
                        selector: 'td:first-child'
                    };
                }

                var table = $('#gapplytable').DataTable(option);

                // Handle filter event
                $('body').on('keyup', '#filterregion input', function() {
                    var index = $(this).data('index');
                    var value = $(this).val();
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
                    var colIndex = $(".dropdown-menu a[data-col].active").data("col");
                    // Get the sort order
                    var order = $(".dropdown-menu a[data-order].active").data("order");
                    // Sort the table
                    table.order([colIndex, order]).draw();
                });

                var selecteddata;

                let getRowData = (table, rows) => {
                    $(".action-button:not(.menu-action)").remove();
                    selecteddata = table.rows({selected: true}).data().toArray().map(row => row[1]);
                    if (selecteddata.length > 0) {
                        $("#gapplytable_length label").after(`
            <button class="btn btn-sm alert-success action-button" data-action="approve" data-toggle="tooltip" title="${M.util.get_string("approve", "enrol_gapply")}"><i class="fa fa-fw fa-check"></i></button>
            <button class="btn btn-sm alert-info action-button" data-action="waitlist" data-toggle="tooltip" title="${M.util.get_string("waitlist", "enrol_gapply")}"><i class="fa fa-fw fa-stopwatch"></i></button>
            <button class="btn btn-sm alert-warning action-button" data-action="reject" data-toggle="tooltip" title="${M.util.get_string("reject", "enrol_gapply")}"><i class="fa fa-fw fa-times"></i></button>
            <button class="btn btn-sm alert-danger action-button" data-action="delete" data-toggle="tooltip" title="${M.util.get_string("delete", "enrol_gapply")}"><i class="fa fa-fw fa-trash"></i></button>`
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

                $(document).on('click', '.action-button', function() {
                    var action = $(this).data("action");
                    var primarybutton = "btn-success";
                    if (action == "waitlist") {
                        primarybutton = "btn-info";
                    } else if (action == "reject") {
                        primarybutton = "btn-warning";
                    } else if (action == "delete") {
                        primarybutton = "btn-danger";
                    }
                    if ($(this).hasClass("menu-action")) {
                        selecteddata = [$(this).data("id")];
                    }

                    // Ajax to get a list of groups
                    $.ajax({
                        method: "POST",
                        url: M.cfg.wwwroot + "/enrol/gapply/ajax.php",
                        data: {
                            action: "getgroups",
                            id: $("#gapplytable").data("instance"),
                            sesskey: M.cfg.sesskey,
                        },
                        dataType: "json",
                        success: function(data) {
                            var groupoptions = "";

                            if (data.length > 0 && action == "approve") {
                                data.forEach(function(group) {
                                    // Render checkbox
                                    groupoptions += `<div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input groups" id="group-${group.id}" name="groups[]" value="${group.id}">
                    <label class="custom-control-label" for="group-${group.id}">${group.name}</label>
                    </div>`;
                                });

                                groupoptions = `<div class="form-group mt-3">
                                                    <label for="groups">${M.util.get_string('assigngroups', 'enrol_gapply')}</label>
                                                    <div id="groups">${groupoptions}</div>
                                                </div>`;
                            }

                            var modal = `<div class="modal fade" id="approveModal" tabindex="-1" role="dialog" aria-labelledby="approveModalLabel"  aria-hidden="true" style="background: rgba(0,0,0,0.5);">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="approveModalLabel">${M.util.get_string(action + 'applications', 'enrol_gapply')}</h5>
                                                <button class="close" data-dismiss="modal" aria-label="Close">
                                                <i class="fa fa-fw fa-times"></i>
                                                </button>
                                                </div>
                                            <div class="modal-body">
                                                <p class="mb-0">${M.util.get_string('areyousureyouwantto' + action, 'enrol_gapply')}</p>
                                                ${groupoptions}
                                            </div>
                                            <div class="modal-footer">
                                                <button   class="btn btn-secondary text-uppercase font-weight-bold" data-dismiss="modal">${M.util.get_string('cancel', 'enrol_gapply')}</button>
                                                <button   class="btn ${primarybutton} text-uppercase font-weight-bold" id="proceed">${M.util.get_string('proceed', 'enrol_gapply')}</button>

                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                            // Remove any existing modal
                            $("#approveModal").remove();
                            $("body").append(modal);
                            $("#approveModal").modal("show");

                            // Approve
                            $("#approveModal #proceed").click(function() {
                                $("#approveModal").modal("hide");
                                $("#enrol-gapply-loading").toggleClass("d-none d-flex");
                                $.ajax({
                                    url: "/enrol/gapply/ajax.php",
                                    method: "POST",
                                    data: {
                                        action: action,
                                        ids: selecteddata.toString(),
                                        id: $("#gapplytable").data("instance"),
                                        groups: $("#approveModal input.groups:checked").map(function() {
                                            return this.value;
                                        }).get().toString(),
                                    },
                                    success: function(data) {
                                        // Reload page
                                        location.reload();
                                    },
                                    error: function(data) {
                                        location.reload();
                                    }
                                });
                            });
                        }
                    });
                });

                $(document).on('click', '.showuserdetail', function() {
                    $("#enrol-gapply-loading").toggleClass("d-none d-flex");
                    var userid = $(this).data("userid");

                    var applicationid = $(this).data("id");
                    var status = $(this).data("statusformatted");
                    var sts = $(this).data("status");
                    selecteddata = [applicationid];
                    var applicationtext = $(".applicationtext[data-id='" + applicationid + "']").html();
                    var attachments = [];
                    $('#gapplytable a.attachmentlink[data-id="' + applicationid + '"]').each(function() {
                        var file = {
                            'link': $(this).data('url'),
                            'filename': $(this).text(),
                            'type': $(this).data('type'),
                        };
                        attachments.push(file);
                    });
                    var select = "";
                    if (attachments.length > 0) {
                        // Select input
                        select = `<select class="custom-select w-100 my-2" id="fileselect">`;
                        attachments.forEach(function(attachment) {
                            select += `<option data-type="${attachment.type}" value="${attachment.link}">${attachment.filename}</option>`;
                        });
                        select += `</select>`;
                    }

                    // AJAX request to get user details
                    $.ajax({
                        url: M.cfg.wwwroot + `/enrol/gapply/ajax.php?action=getuserbyid&id=${id}&userid=` + userid + '&sesskey=' + M.cfg.sesskey,
                        type: 'GET'
                    }).fail(response => {
                        var modal = `<div class="modal fade" id="userdetailModal" tabindex="-1" role="dialog" aria-labelledby="userdetailModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document" style="max-width: calc(100% - 2rem);">
                    <div class="modal-content">
                    ${response.responseText}
                    </div>
                </div>
            </div>`;
                        // Remove any existing modal
                        $("#userdetailModal").remove();
                        $("body").append(modal);
                        $("#userdetailModal").modal("show");
                        if (sts == "approved") {
                            $("#userdetailModal .action-button").remove();
                        }

                        $("#enrol-gapply-loading").toggleClass("d-none d-flex");
                        $("#userdetailModal .select-input").html(select);
                        $("#userdetailModal #applicationtext").html(applicationtext);
                        $("#userdetailModal #currentstatus").html(status);
                        let changefile = function(url, type, name) {
                            $(".fileview #viewer").html(name);
                            if (type.includes("image")) {
                                $(".fileview #viewer").html(`<img src="${url}" class="img-fluid mx-auto">`);
                            } else if (type.includes("video")) {
                                $(".fileview #viewer").html(`<video src="${url}"
                    class="embed-responsive-item text-center m-0" controls width="100%" autoplay></video>`);
                            } else if (type.includes("audio")) {
                                $(".fileview #viewer").html(`<audio src="${url}"
                    class="embed-responsive-item text-center m-0" controls width="100%" autoplay></audio>`);
                            } else if (type.includes("pdf")) {
                                $(".fileview #viewer").html(`<object data="${url}" type="application/pdf"
                     width="100%" style="height: calc(100% - 5px);">
                                            <p>Unable to display PDF file on this device. <a href="${url}">Download</a> instead.</p>
                                            </object>`);
                            } else if (type.includes("officedocument") || type.includes("msword")
                                || type.includes("ms-excel") || type.includes("ms-powerpoint") || type.includes("openxmlformats")) {
                                $(".fileview #viewer").html(`<iframe id="fileviewer"
                    src="https://view.officeapps.live.com/op/embed.aspx?src=${url}"
                    class="embed-responsive-item" style="width: 100%; height: calc(100% - 5px);"></iframe>`);
                            } else if (type.includes("text") || type.includes("csv")) {
                                $(".fileview #viewer").html(`<iframe id="fileviewer"
                    src="https://docs.google.com/viewer?url=${url}&embedded=true"
                    class="embed-responsive-item" style="width: 100%; height:  calc(100% - 5px); border-radius: 0"></iframe>`);
                            } else {
                                $(".fileview #viewer").html(`<p
                    class="text-center py-5">Unable to display file. <a href="${url}">Download</a> instead.</p>`);
                            }
                            $("#userdetailmodalLabel").html(name);
                            $("#downloadbutton").attr("href", url);
                        };

                        $("#fileselect").change(function() {
                            changefile($(this).val(), $(this).find(":selected").data("type"), $(this).find(":selected").text());
                        });

                        $("#fileselect").trigger("change");
                    }).done(response => {
                        $("#enrol-gapply-loading").toggleClass("d-none d-flex");
                        toast("Unable to get the application detail.", {
                            "type": "error"
                        });
                        window.console.log(response);
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