# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.5] - 2025-12-16

### Added

- **Automatic Event Listener Registration**: Event listeners are now automatically registered when the package is installed
  - Created `A2CommerceEventServiceProvider` to handle all event-to-listener mappings
  - No manual configuration required in `AppServiceProvider` - package handles registration automatically
  - Supports all cart, wishlist, comparison, order, and payment events
  - Fully compatible with Laravel's `php artisan event:cache` command
  - Clean separation of concerns - event registration is separate from other package functionality

### Changed

- **Service Provider**: Updated `A2CommerceServiceProvider` to automatically register `A2CommerceEventServiceProvider`
  - Event listeners work immediately after package installation
  - Users no longer need to manually add event mappings to their `AppServiceProvider`

### Fixed

- **Migration Removal During Uninstall**: Fixed critical bug in uninstallation process where migrations were not properly removed
  - Implemented direct database table removal using SQL DROP statements for A2Commerce-prefixed tables
  - Enhanced reliability by dropping tables directly before attempting migration rollback
  - Improved user feedback with clearer messages regarding migration rollback status
  - Fixed foreign key constraint handling during table removal
  - Uninstallation now properly cleans up all database tables and migration files
- **Foreign Key Constraint**: Fixed migration for `a2_ec_orders` table to properly handle nullable `user_id`
  - Updated foreign key constraint to allow nullable `user_id` while maintaining cascade delete behavior
  - Ensures guest checkout functionality works correctly

### Technical

- **EventServiceProvider**: New provider class at `src/Providers/A2CommerceEventServiceProvider.php`
  - Extends Laravel's `EventServiceProvider`
  - Defines all event-to-listener mappings for the package
  - Uses correct namespaces: `App\Events\A2\Commerce\*` and `App\Listeners\A2\Commerce\*`

### Release Notes

- **Official Release Ready**: This version includes critical bug fixes and is ready for production use
  - All known issues with uninstallation have been resolved
  - Migration handling is now reliable and robust
  - Package is stable and ready for official release

## [0.1.4] - 2025-12-16

### Changed

- **Installer Class**: Refactored for improved code readability and maintainability
  - Simplified constructor syntax
  - Enhanced environment file handling to mirror Vormia behavior
  - Ensures env files are only modified if they already exist
- **Path Handling**: Improved `pathJoin` method in Installer class
  - Better handling of absolute and relative paths
  - Enhanced filtering of input parts
  - Proper trimming of slashes based on path type
  - Normalized blank lines in environment file processing for better consistency

### Enhanced

- **Installation Command**: Streamlined file handling and improved user feedback
  - Unified installer method for copying and removing files
  - Enhanced environment variable management
  - Detailed output for copied and removed files
  - Consistent handling of existing files during operations
  - Appends A2 configuration to .env and .env.example files only if they do not already exist
- **Uninstallation Command**: Enhanced user interaction and clarity
  - Introduced prompts for rolling back migrations and removing environment variables
  - Clearer warnings about data loss
  - Completion messages reflect user choices regarding migrations and environment variables
  - Streamlined process to only remove files originally installed by the package
  - Better feedback throughout the uninstallation steps
  - Enhanced environment variable handling

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
[0.1.4]: https://github.com/a2-atu/a2commerce/releases/tag/v0.1.4
[0.1.5]: https://github.com/a2-atu/a2commerce/releases/tag/v0.1.5
