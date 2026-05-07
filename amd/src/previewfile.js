import Ajax from 'core/ajax';
import $ from "jquery";
import Notification from 'core/notification';
import {add as addToast} from 'core/toast';
import ModalEvents from 'core/modal_events';
import {get_string as getString} from 'core/str';
export const init = () => {
    $(document).on('click', "a[data-type]", async function() {
        let $this = $(this);
        let url = $this.data("url");
        let type = $this.data("type");
        let title = $this.text();

        let html = "";
        let isFlex = true;

        if (type.includes("image")) {
            html = `<img src="${url}" class="img-fluid mx-auto">`;
            isFlex = false;
        } else if (type.includes("video")) {
            html = `<video src="${url}"
                    class="embed-responsive-item text-center m-0" controls width="100%" autoplay></video>`;
        } else if (type.includes("audio")) {
            html = `<audio src="${url}"
                    class="embed-responsive-item text-center m-0" controls width="100%" autoplay></audio>`;
            isFlex = false;
        } else if (type.includes("pdf")) {
            html = `<object data="${url}" type="application/pdf" width="100%">
                        <p>${M.util.get_string('cannotopenpdffile', 'enrol_gapply', url)}</p>
                    </object>`;
        } else if (type.includes("officedocument") || type.includes("msword")
            || type.includes("ms-excel")
            || type.includes("ms-powerpoint") || type.includes("openxmlformats")) {
            html = `<iframe id="fileviewer"
                    src="https://view.officeapps.live.com/op/embed.aspx?src=${url}"
                    class="embed-responsive-item" style="width: 100%;"></iframe>`;
        } else if (type.includes("text") || type.includes("csv")) {
            html = `<iframe id="fileviewer"
                    src="https://docs.google.com/viewer?url=${url}&embedded=true"
                    class="embed-responsive-item" style="width: 100%; border-radius: 0"></iframe>`;
        } else {
            html = `<p class="text-center py-5">${M.util.get_string('cannotopenfile', 'enrol_gapply', url)}</p>`;
            isFlex = false;
        }

        const branch = parseInt($('#moodle-branch').data('branch'));
        const isModern = branch >= 403;
        const modalModule = isModern ? 'core/modal' : 'core/modal_factory';

        let ModalFactory = await import(modalModule);

        // Handle Moodle AMD modules which might wrap the export in .default
        ModalFactory = ModalFactory.default ? ModalFactory.default : ModalFactory;

        const modal = await ModalFactory.create({
            title: title,
            body: html,
            large: true,
            removeOnClose: true,
            isVerticallyCentered: true,
            footer: `<button class="btn btn-primary text-uppercase font-weight-bold" data-action="download">
                        ${M.util.get_string("download", "enrol_gapply")}</button>
                     <button class="btn btn-secondary text-uppercase font-weight-bold" data-action="hide">
                        ${M.util.get_string("close", "enrol_gapply")}</button>`
        });

        const root = modal.getRoot();
        root.attr("id", "applyfile");
        root.find('.modal-lg').toggleClass('modal-lg modal-xl');

        const body = root.find('.modal-body');
        body.addClass('p-0 text-center');
        if (isFlex) {
            body.addClass('d-flex justify-content-center');
        }

        let newURL = new URL(url);
        root.on("click", '[data-action="download"]', function() {
            newURL.searchParams.append("forcedownload", 1);
            window.open(newURL.toString());
        });

        root.on(ModalEvents.hidden, function() {
            modal.destroy();
        });

        modal.show();
    });

    $(document).on('click', "#withdraw", async function(e) {
        e.preventDefault();
        const $btn = $(this);
        const instanceId = $btn.data("instance") || 0;

        const branch = parseInt($('#moodle-branch').data('branch'));
        const isModern = branch >= 403;

        let Modal;
        if (isModern) {
            Modal = await import('core/modal_save_cancel').then(m => m.default || m);
        } else {
            Modal = await import('core/modal_factory').then(m => m.default || m);
        }

        const [reasonStr, titleStr, confirmStr, withdrawStr, successStr] = await Promise.all([
            getString('withdrawalreason', 'enrol_gapply'),
            getString('withdrawapplication', 'enrol_gapply'),
            getString('withdrawapplicationconfirm', 'enrol_gapply'),
            getString('withdraw', 'enrol_gapply'),
            getString('withdrawalsuccess', 'enrol_gapply')
        ]);

        const body = `
            <div class="form-group">
                <label for="withdrawal-reason" class="font-weight-bold">${reasonStr}</label>
                <textarea id="withdrawal-reason" class="form-control" rows="3"></textarea>
            </div>
            <p class="mt-3 text-muted">${confirmStr}</p>
        `;

        const modalConfig = {
            title: titleStr,
            body: body,
            removeOnClose: true
        };

        if (!isModern) {
            modalConfig.type = Modal.types.SAVE_CANCEL;
        }

        const modal = await Modal.create(modalConfig);

        modal.setSaveButtonText(withdrawStr);

        const handleWithdraw = function(e) {
            e.preventDefault();
            const reason = modal.getRoot().find('#withdrawal-reason').val();
            Ajax.call([{
                methodname: "enrol_gapply_manage_applications",
                args: {
                    action: "withdraw",
                    ids: [],
                    instanceid: instanceId,
                    reason: reason
                }
            }])[0].then(() => {
                modal.hide();
                addToast(successStr, {
                    type: 'success'
                });
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }).catch(Notification.exception);
        };

        // Listen for both the Modal's save event and direct button click for maximum compatibility.
        modal.getRoot().on(ModalEvents.save, handleWithdraw);

        modal.show();
    });
};