/* eslint-disable no-redeclare */
/* eslint-disable no-undef */
/* eslint-disable no-unused-vars */
/* eslint-disable block-scoped-var */
/* eslint-disable max-len */
import $ from "jquery";

export const init = () => {
    $("a[data-type]").click(function(e) {
        var modal = `<div class="modal fade" id="applyfile" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="applyfileLabel" aria-modal="true" role="dialog">
                                                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="applyfileLabel"></h5>
                                                                <button   class="close" data-dismiss="modal" aria-label="Close">
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
};