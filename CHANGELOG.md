# Changelog

All notable changes to this project will be documented in this file.

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
