# Changelog

All notable changes to the Auto Assign Internal plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024

### Added
- Initial release of Auto Assign Internal plugin
- Hook for intercepting TicketTask updates
- Automatic ticket assignment based on task technician assignment
- Configuration UI for selecting internal RequestType
- CSRF protection for all forms
- Support for GLPI 9.5.x
- English (en_GB) localization
- Portuguese Brazil (pt_BR) localization
- Database table for storing configuration
- Clean install/uninstall process

### Features
- Checks if task has `users_id_tech` assigned
- Retrieves linked ticket via `tickets_id`
- Verifies ticket's RequestType matches configured internal type
- Automatically assigns task user to ticket if conditions met
- Prevents duplicate assignments
- Logging support for debugging

### Security
- CSRF token validation on all forms
- Session rights checking for configuration access
- Follows GLPI security best practices
