# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.3] - 2025-01-XX

### Added

- **Enhanced Console Commands**: Improved all Artisan commands to match professional package standards
  - **Install Command**: Step-by-step output showing each directory being copied (Services, Controllers, Models, Events, Listeners, Jobs, Notifications, Migrations, Config, Views)
  - **Update Command**: Creates backups before updating, removes old files, then copies fresh ones with detailed progress
  - **Uninstall Command**: Step-by-step removal of each directory with backup creation and cache clearing
  - **Help Command**: Comprehensive help output with ASCII header, organized sections, usage examples, and formatted environment keys
- **Backup Functionality**: Automatic backup creation before updates and uninstalls
  - Update command creates backups in `storage/app/a2commerce-backups/`
  - Uninstall command creates final backup in `storage/app/a2commerce-final-backup-{timestamp}/`
- **Cache Management**: Automatic cache clearing after uninstall and update operations
  - Clears configuration, route, view, and application caches

### Changed

- **Path Handling**: All commands now use Laravel path helpers directly
  - Uses `app_path()`, `database_path()`, `config_path()`, `resource_path()` for correct directory paths
  - Ensures files are copied to correct locations matching Laravel conventions
- **Command Output**: Enhanced user experience with detailed step-by-step feedback
  - Shows relative paths for better readability
  - Displays file counts and operation status for each step
  - Provides clear success/warning messages
- **README Structure**: Completely restructured to match professional package documentation
  - Added comprehensive introduction and features section
  - Step-by-step installation guide (7 steps)
  - Detailed usage examples for PayPal flow and guest checkout
  - Commands reference section with options
  - Troubleshooting section with common issues
  - Better organization matching Vormia package style

### Fixed

- **File Copying**: Fixed directory path handling to ensure files are copied to correct locations
  - Services now copy to `app/Services/A2/`
  - Controllers copy to `app/Http/Controllers/A2/`
  - Models copy to `app/Models/A2/`
  - All other directories follow Laravel conventions
- **Migration Removal**: Improved migration file detection and removal during uninstall
  - Correctly identifies and removes migrations with `a2_ec_` prefix

### Technical

- **Installer Class**: Made `ensureEnvKeys()`, `ensureRoutes()`, `removeEnvKeys()`, and `removeRoutes()` methods public
  - Allows commands to call these methods directly for better control
  - Maintains backward compatibility with existing install/uninstall flow

### Documentation

- **README Enhancement**: Comprehensive rewrite with professional structure
  - Introduction explaining A2Commerce and its relationship to Vormia
  - Dependencies section with installation requirements
  - Features list with brief descriptions
  - Detailed installation steps with examples
  - Usage examples for PayPal and guest checkout
  - Commands reference with all options
  - Troubleshooting guide
  - Documentation references

## [0.1.2] - 2025-12-15

### Added
- Formalized README with clear install/update/uninstall steps, env keys, PayPal webhook route, and pointers to packageflow docs.
- Summarized architecture highlights (service layer, events, guest checkout, PayPal flow).

### Notes
- Docs-only release; no functional code changes.

[0.1.2]: https://github.com/a2-atu/a2commerce/releases/tag/v0.1.2
[0.1.3]: https://github.com/a2-atu/a2commerce/releases/tag/v0.1.3

