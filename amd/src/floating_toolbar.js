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
 * Floating bulk-action toolbar (shared with manage.php styling).
 *
 * @module     enrol_gapply/floating_toolbar
 * @copyright  2026 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';

/** @type {HTMLElement|null} */
let toolbar = null;

/** @type {HTMLElement|null} Preferred mount context (e.g. MooTube applications panel). */
let mountContext = null;

/** @type {((action: string) => void)|null} */
let actionHandler = null;

/** @type {(() => void)|null} */
let closeHandler = null;

/**
 * Load enrol_gapply styles if not already present.
 */
export const ensureStyles = () => {
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
 * Build action button HTML for the current status tab.
 *
 * @param {string} status Current status filter.
 * @param {Object} strings Localised button titles.
 * @return {string}
 */
const buildActionButtons = (status, strings) => {
    const buttons = [];

    if (status !== 'approved') {
        buttons.push(`
            <button type="button" class="btn-success action-button" data-action="approve"
                title="${strings.approve}">
                <i class="fa fa-check" aria-hidden="true"></i>
                <span class="visually-hidden">${strings.approve}</span>
            </button>`);

        if (status !== 'waitlisted') {
            buttons.push(`
                <button type="button" class="btn-info action-button" data-action="waitlist"
                    title="${strings.waitlist}">
                    <i class="fa fa-clock-o" aria-hidden="true"></i>
                    <span class="visually-hidden">${strings.waitlist}</span>
                </button>`);
        }

        if (status !== 'rejected') {
            buttons.push(`
                <button type="button" class="btn-warning action-button" data-action="reject"
                    title="${strings.reject}">
                    <i class="fa fa-times" aria-hidden="true"></i>
                    <span class="visually-hidden">${strings.reject}</span>
                </button>`);
        }
    }

    buttons.push(`
        <button type="button" class="btn-danger action-button" data-action="delete"
            title="${strings.delete}">
            <i class="fa fa-trash" aria-hidden="true"></i>
            <span class="visually-hidden">${strings.delete}</span>
        </button>`);

    return buttons.join('');
};

/**
 * Create the toolbar DOM once and bind global handlers.
 *
 * @return {Promise<HTMLElement>}
 */
const ensureToolbar = async() => {
    if (toolbar && !toolbar.isConnected) {
        toolbar = null;
    }

    if (toolbar) {
        repositionToolbar(toolbar);
        return toolbar;
    }

    const [closeLabel, approve, waitlist, reject, deleteLabel, item, items] = await Promise.all([
        getString('close', 'enrol_gapply'),
        getString('approve', 'enrol_gapply'),
        getString('waitlist', 'enrol_gapply'),
        getString('reject', 'enrol_gapply'),
        getString('delete', 'core'),
        getString('item', 'enrol_gapply'),
        getString('items', 'enrol_gapply'),
    ]);

    toolbar = document.createElement('div');
    toolbar.id = 'gapply-floating-toolbar';
    toolbar.className = 'gapply-floating-toolbar';
    toolbar.setAttribute('role', 'toolbar');
    toolbar.setAttribute('aria-hidden', 'true');
    toolbar.innerHTML = `
        <div class="selection-info">
            <div class="selection-icon"><i class="fa fa-check" aria-hidden="true"></i></div>
            <span class="selection-count" data-region="gapply-toolbar-count"></span>
        </div>
        <div class="toolbar-actions" data-region="gapply-toolbar-actions"></div>
        <button type="button" class="close-toolbar" data-action="close-toolbar"
            title="${closeLabel}">
            <i class="fa fa-times" aria-hidden="true"></i>
            <span class="visually-hidden">${closeLabel}</span>
        </button>
    `;
    repositionToolbar(toolbar);

    toolbar.dataset.itemLabel = item.toUpperCase();
    toolbar.dataset.itemsLabel = items.toUpperCase();
    toolbar.dataset.strings = JSON.stringify({approve, waitlist, reject, delete: deleteLabel});

    toolbar.addEventListener('click', (event) => {
        const actionButton = event.target.closest('[data-action]');
        if (!actionButton || !toolbar.contains(actionButton)) {
            return;
        }

        const action = actionButton.dataset.action;
        if (action === 'close-toolbar') {
            if (typeof closeHandler === 'function') {
                closeHandler();
            }
            return;
        }

        if (typeof actionHandler === 'function') {
            actionHandler(action);
        }
    });

    return toolbar;
};

/**
 * Set the DOM context used to find the participants modal mount point.
 *
 * @param {HTMLElement|null} root Applications panel root or similar.
 */
export const setMountContext = (root) => {
    mountContext = root ?? null;
    if (toolbar) {
        repositionToolbar(toolbar);
    }
};

/**
 * Resolve where the toolbar should be attached.
 *
 * @return {HTMLElement}
 */
const getMountParent = () => {
    const fromContext = mountContext?.closest?.('.mtube-participants-modal');
    if (fromContext) {
        return fromContext;
    }

    const participantsModal = document.querySelector('.mtube-participants-modal');
    if (participantsModal) {
        return participantsModal;
    }

    return document.body;
};

/**
 * Attach the toolbar to the correct layer and set z-index above modal content.
 *
 * @param {HTMLElement} bar Toolbar element.
 */
const repositionToolbar = (bar) => {
    const mount = getMountParent();
    if (bar.parentElement !== mount) {
        mount.appendChild(bar);
    }

    if (mount.classList.contains('mtube-participants-modal')) {
        // Stacking context of the participants modal; sit above .modal-dialog content.
        bar.style.zIndex = '10';
        return;
    }

    const openModal = document.querySelector('.mtube-participants-modal.show');
    if (openModal) {
        const modalZ = parseInt(window.getComputedStyle(openModal).zIndex, 10);
        bar.style.zIndex = String((Number.isFinite(modalZ) ? modalZ : 1055) + 10);
        return;
    }

    bar.style.removeProperty('z-index');
};

/**
 * Register handlers for toolbar actions.
 *
 * @param {Object} handlers Handlers.
 * @param {Function} handlers.onAction Called with action name when an action button is clicked.
 * @param {Function} handlers.onClose Called when the toolbar close control is clicked.
 */
export const setHandlers = (handlers) => {
    actionHandler = handlers.onAction ?? null;
    closeHandler = handlers.onClose ?? null;
};

/**
 * Update toolbar visibility, selection count, and action buttons.
 *
 * @param {Object} options Options.
 * @param {number} options.count Selected row count.
 * @param {string} options.status Current status tab.
 */
export const updateToolbar = async({count, status}) => {
    ensureStyles();
    const bar = await ensureToolbar();
    repositionToolbar(bar);
    const countEl = bar.querySelector('[data-region="gapply-toolbar-count"]');
    const actionsEl = bar.querySelector('[data-region="gapply-toolbar-actions"]');
    const strings = JSON.parse(bar.dataset.strings || '{}');

    if (!count) {
        bar.classList.remove('show');
        bar.setAttribute('aria-hidden', 'true');
        if (actionsEl) {
            actionsEl.innerHTML = '';
        }
        return;
    }

    const label = count === 1 ? bar.dataset.itemLabel : bar.dataset.itemsLabel;
    if (countEl) {
        countEl.textContent = `${count} ${label}`;
    }
    if (actionsEl) {
        actionsEl.innerHTML = buildActionButtons(status, strings);
    }

    bar.classList.add('show');
    bar.setAttribute('aria-hidden', 'false');
};

/**
 * Hide the floating toolbar without clearing handlers.
 */
export const hideToolbar = () => {
    if (!toolbar) {
        return;
    }
    toolbar.classList.remove('show');
    toolbar.setAttribute('aria-hidden', 'true');
    const actionsEl = toolbar.querySelector('[data-region="gapply-toolbar-actions"]');
    if (actionsEl) {
        actionsEl.innerHTML = '';
    }
};
