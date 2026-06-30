# Brizy Fix

A lightweight, performance-optimized utility plugin for WordPress to fix broken page layouts and styling mismatches caused by **Brizy Builder** cache/compilation sync issues.

## Description

If your website layout looks broken after a WordPress migration, host transfer, or plugin update, the cause is often that Brizy's compiled HTML/CSS caches have fallen out of sync.

**Brizy Fix** solves this by triggering a clean recompilation of all pages built using Brizy.

### Key Features

* **AJAX-Based Execution**: Runs incrementally, compiling one page at a time. This keeps memory usage low and prevents common "500 Internal Server Error" or execution timeouts on low-memory servers below 256MB.
* **Safe Re-generation**: The plugin reads your design templates in a read-only fashion using JSON layouts stored in the database, and only updates the output HTML wrappers. Your database files and design structures are completely safe.
* **Real-time Logging**: Displays a detailed progress bar and status feed in your admin area to show successful compilations or skipped layout assets.

## Important: Backup Your Site Before Use

> [!IMPORTANT]
> You must create a full database and filesystem backup of your website before running this utility. While the plugin is designed to run safely, altering database layouts is a major action. Having a complete restore point ensures you can undo changes if any plugin conflict arises.
>
> We recommend using a backup plugin like **WPvivid** or taking a manual backup via your hosting dashboard.

## What This Plugin Does NOT Fix (Blocksy Companion Issue)

This plugin is designed **only** to resolve compiled HTML/CSS mismatch glitches in the Brizy Builder.

* **It does not resolve layout width issues caused by Blocksy Companion.**
* If your site is experiencing an issue where full-width pages have suddenly collapsed to default container width, this is a known conflict within the Blocksy Companion plugin stylesheet settings.
* That is an entirely separate issue and will not be fixed by compiling Brizy pages.
* A detailed screencast demonstrating the compilation and layout tests is available here: [Watch Screencast on YouTube](https://youtu.be/8ra4pF9fMIQ)

## Installation & Usage

1. Upload the `brizy-fix` folder to your `/wp-content/plugins/` directory.
2. Activate **Brizy Fix** from your WordPress Plugins menu.
3. Navigate to **Tools > Brizy Fix** in your WordPress Admin Sidebar.
4. Click **Start Recompilation** and let the queue progress to 100%.

## After Use

This plugin is intended as a one-time repair utility. After the recompilation finishes and you have confirmed your Brizy pages are displaying correctly, deactivate and delete **Brizy Fix** from your WordPress site. There is no need to keep it installed after use.

## License

GPL-3.0-or-later
