
import $ from "jquery";
import Notification from 'core/notification';
import {add as addToast} from 'core/toast';

export const init = () => {
    $(document).on('click', "a[data-type]", function() {
        var modal = `<div class="modal fade" id="applyfile" data-backdrop="static"
         data-keyboard="false" tabindex="-1" aria-labelledby="applyfileLabel" aria-modal="true" role="dialog">
                        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="applyfileLabel"></h5>
                                    <button   class="close" data-dismiss="modal" aria-label="Close">
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
        // Remove existing modal
        $("#applyfile").remove();
        $("body").append(modal);
        $("#applyfileLabel").html($(this).text());
        $("#applyfile").modal("show");
        let html = '';
        if ($(this).data("type").includes("image")) {
            $("#applyfile .modal-body").removeClass("d-flex");
            html = `<img src="${$(this).data("url")}" class="img-fluid mx-auto">`;
        } else if ($(this).data("type").includes("video")) {
            html = `<video src="${$(this).data("url")}"
                    class="embed-responsive-item text-center m-0" controls width="100%" autoplay></video>`;
        } else if ($(this).data("type").includes("audio")) {
            $("#applyfile .modal-body").removeClass("d-flex");
            html = `<audio src="${$(this).data("url")}"
                    class="embed-responsive-item text-center m-0" controls width="100%" autoplay></audio>`;
        } else if ($(this).data("type").includes("pdf")) {
            html = `<object data="${$(this).data("url")}" type="application/pdf"
                     width="100%" style="height: 80vh">
                                            <p>${M.util.get_string('cannotopenpdffile', 'enrol_gapply', $(this).data("url"))}</p>
                                            </object>`;
        } else if ($(this).data("type").includes("officedocument") || $(this).data("type").includes("msword")
            || $(this).data("type").includes("ms-excel")
            || $(this).data("type").includes("ms-powerpoint") || $(this).data("type").includes("openxmlformats")) {
            html = `<iframe id="fileviewer"
                    src="https://view.officeapps.live.com/op/embed.aspx?src=${$(this).data("url")}"
                    class="embed-responsive-item" style="width: 100%; height: 80vh"></iframe>`;
        } else if ($(this).data("type").includes("text") || $(this).data("type").includes("csv")) {
            html = `<iframe id="fileviewer"
                    src="https://docs.google.com/viewer?url=${$(this).data("url")}&embedded=true"
                    class="embed-responsive-item" style="width: 100%; height: 80vh; border-radius: 0"></iframe>`;
        } else {
            $("#applyfile .modal-body").removeClass("d-flex");
            html = `<p class="text-center py-5">${M.util.get_string('cannotopenfile', 'enrol_gapply', $(this).data("url"))}</p>`;
        }
        $("#applyfile .modal-body").html(html);
        var newURL = new URL($(this).data("url"));

        $("#applyfile").on("click", "#forcedownloadbutton", function() {
            newURL.searchParams.append("forcedownload", 1);
            window.open(newURL.toString());
        });
    });

    $(document).on('click', "#withdraw", function(e) {
        e.preventDefault();
        const withdraw = () => {
            $.ajax({
                method: "POST",
                url: M.cfg.wwwroot + "/enrol/gapply/ajax.php",
                data: {
                    action: "withdraw",
                    id: $(".btn#withdraw").data("instance"),
                    sesskey: M.cfg.sesskey,
                },
                dataType: "text",
                success: () => {
                    addToast(M.util.get_string('applicationwithdrawnsuccess', 'enrol_gapply'), {
                        type: 'success'
                    });
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                },
                error: () => {
                    addToast(M.util.get_string('anerroroccurred', 'enrol_gapply'), {
                        type: 'danger'
                    });
                }
            });
        };

        try { // 4.1 +
            Notification.deleteCancelPromise(
                M.util.get_string('withdrawapplication', 'enrol_gapply'),
                M.util.get_string('withdrawapplicationconfirm', 'enrol_gapply'),
                M.util.get_string('withdraw', 'enrol_gapply'),
            ).then(() => {
                return withdraw();
            }).catch(() => {
                return;
            });
        } catch { // 4.1
            Notification.saveCancel(
                M.util.get_string('withdrawapplication', 'enrol_gapply'),
                M.util.get_string('withdrawapplicationconfirm', 'enrol_gapply'),
                M.util.get_string('withdraw', 'enrol_gapply'),
                function() {
                    return withdraw();
                }
            );
        }
    });
};