# Changelog

All notable changes to this project will be documented in this file.

## [2.0] - 2026-05-07

### 🔄 Architectural Shift: AJAX to Web Services

- Removed `ajax.php`: The legacy AJAX handler has been completely replaced.
- New External API: `classes/external.php` and `db/services.php` have been added, implementing a robust Web Service layer. This handles all application lifecycle actions (approve, reject, waitlist, delete, withdraw) with better security and structured JSON responses.

### 🖼️ UI/UX Overhaul (Native Moodle Modals)

- Migration to `core/modal`: The custom modal logic has been replaced with native Moodle Modals for better accessibility and theme compatibility.
- Dual-Pane Detail View: New Mustache templates (`modal_body.mustache`, `modal_header.mustache`) implement a side-by-side layout for simultaneous document preview and application data management.
- Record Navigation: Added "Next" and "Previous" buttons within the modal, allowing admins to cycle through applications without closing and reopening the view.

### 👥 Participants Page Integration

- Automated Helper: Added `amd/src/participants_helper.js`, which automatically initializes on the course participants page.
- Group Management: Improved "Add to Group" and "Remove from Group" functionality directly within the participants' interface.

### 📂 Critical Fix: File Storage Logic

- Standardized `itemid`: Changed the file storage logic to use the application's unique ID (`appid`) as the `itemid`. Previously, it used a concatenated string of `$instanceid . $userid`, which caused issues with multiple applications.
- Withdrawal Reason: The withdrawal workflow now captures a mandatory reason, which is included in the admin notification.

### 📈 Versioning & Compatibility

- Moodle 5.2 Support: Updated compatibility to support Moodle 5.02 (`$plugin->supported = [401, 502]`).

### 🚀 Optimized Performance

- Batch Database Updates: Refactored bulk actions (approve/reject) to use optimized SQL `JOIN`s and `UPDATE ... WHERE IN (...)` statements, eliminating N+1 query performance bottlenecks.

## [1.0.11] - 2025-04-22

- Support for Moodle 5.0

## [1.0.9] - 2024-10-08

### Added

- Ability for admin to set the default role assignment
- Ability for user with enrol/gapply:config capability to set the default role for new approved users
- Ability for user with enrol/gapply:manage capability to select the specific role for a specific user when approving the application
- Ability for user with enrol/gapply:manage capability to set enrollment dates for a specific user when approving the application
- Ability for applicant to withdraw their application if application is not yet processed

### Changed

- Change can_add_instance method to make sure only one application instance is allowed.
- Remove before_footer callback because it is no longer supported in 4.5.
- Remove $PAGE->set_context() on enrol_form because changing context from 40 to 10 is no longer allowed in 5.0.
- Include all user profile fields in user object before calling to $OUTPUT->user_picture as required in 4.5.

### Fixed

- Relative links caused issues in Moodle instances installed on sub-folder.
