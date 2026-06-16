# Changelog
## [Unreleased] - yyyy-mm-dd

### Added

### Changed
- hook and filter name update

### Fixed

### Updated

## [10.2.3] - 2026-06-15


## [10.2.2] - 2026-06-15


## [10.2.1] - 2026-06-15


## [10.2.0] - 2026-06-15


## [10.1.9] - 2026-06-13


## [10.1.8] - 2026-06-13


### Fixed
- shared code loader
- activation hook
- use correct shortcodes on auto created pages

## [10.1.7] - 2026-06-11


### Added
- placeholder for textdomain
- user, post and rest_meta prefixing

### Changed
- prefixed post metas and shortcodes

### Fixed
- locations.js
- prefix meta_query

## [10.1.6] - 2026-06-09


### Added
- usage of wpdb->prepare for all queries
- shared functionality loader

### Changed
- comply to coding standards
- code layout
- namespaced all constants
- sanitize all posts and get vars
- js to js file
- moved inline style to scss file
- moved inline style to scss file

### Fixed
- spacing problem
- space before dot bug
- use pluginversion

## [10.1.5] - 2026-06-03


### Added
- echo escaping

## [10.1.4] - 2026-06-01


### Changed
- merged hooks.md into readme.md

### Fixed
- added domain to __ function

## [10.1.3] - 2026-05-30


### Changed
- do not store get_plugin_data in global variable

## [10.1.2] - 2026-05-29


### Added
- wp_unslash

## [10.1.1] - 2026-05-28


## [10.1.0] - 2026-05-28


### Fixed
- bug without account page set

## [10.0.9] - 2026-05-28


### Fixed
- empty property bug

## [10.0.8] - 2026-05-24


### Fixed
- bugs

## [10.0.7] - 2026-05-14


### Changed
- date( to gmdate(

## [10.0.6] - 2026-05-11


## [10.0.5] - 2026-05-11


### Added
- locations.js to get route

### Updated
- readme
- js

## [10.0.4] - 2026-05-08


### Fixed
- account page retrieval

## [10.0.3] - 2026-05-06


### Changed
- use formData->slug

### Fixed
- textdomain

## [10.0.1] - 2026-05-03


### Changed
- removed the redirection at activation as it is done by the share plugin
- use shared github workflows

## [10.0.0] - 2026-05-01


### Added
- redirection to settings page on plugin activation

### Changed
- main plugin name from sim-base to tsjippy-shared-functionality
- module to plugin
- PLUGINCONSTANT value
- lib updates
- exclude .vscode from releases
- updated github workflow versions

### Fixed
- settings var in AdminMenu

## [8.1.8] - 2025-12-12


### Changed
- sim_before_saving_formdata filter to sim_before_submitting_formdata

## [8.1.7] - 2025-11-24


### Changed
- formresults to submission

## [8.1.6] - 2025-11-03


### Changed
- stop listening to events if we have a match

### Fixed
- getting family picture

## [8.1.4] - 2025-10-30


### Changed
- new format for frontendcontent
- use upgrade.php not install-helper.php
- use new family class

## [8.1.3] - 2025-10-25


### Changed
- code cleanup

## [8.1.2] - 2025-10-13


### Added
- form settings format

### Changed
- classnames
- data attribute names
- page management

### Fixed
- bugs

## [8.1.1] - 2025-09-26


### Added
- minified user_location js

### Changed
- classnames replace _ with -

## [8.1.0] - 2025-08-07


### Added
- 'sim-theme-archive-page-title' filter

## [8.0.9] - 2025-02-13


### Changed
- sim_module_updated filter to new format
- module hooks now include module slug

## [8.0.8] - 2025-01-31


### Changed
- after update hook
- extra js hook

## [8.0.7] - 2024-11-28


### Fixed
- duplicate function name

## [8.0.6] - 2024-11-22


### Changed
- removed anonymous functions

## [8.0.5] - 2024-11-19


### Changed
- removed anomynous functions

## [8.0.4] - 2024-10-24


### Fixed
- bug in enqueing

## [8.0.3] - 2024-10-17


### Changed
- readme

### Updated
- blocks

## [8.0.2] - 2024-10-11


## [8.0.1] - 2024-10-11


### Changed
- redering of asset urls

### Updated
- hooks

## [8.0.0] - 2024-10-04


## [8.0.0] - 2024-10-03
