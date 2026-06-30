=== Brizy Fix ===
Contributors: arielhsite
Donate link: https://justanothertech.online
Tags: brizy, builder, layout, fix, recompile
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Recompiles all Brizy Builder pages to fix broken styling, caching, and layout mismatches.

== Description ==

If your website layout looks broken after a WordPress migration, host transfer, or plugin update, the cause is often that Brizy's compiled HTML/CSS caches have fallen out of sync. 

**Brizy Fix** solves this by triggering a clean recompilation of all pages built using Brizy.

=== Key Features ===
* **AJAX-Based Execution**: Runs incrementally, compiling one page at a time. This keeps memory usage low and prevents common "500 Internal Server Error" or execution timeouts on low-memory servers (below 256MB).
* **Safe Re-generation**: The plugin reads your design templates in a read-only fashion (using JSON layouts stored in the database) and only updates the output HTML wrappers. Your database files and design structures are completely safe.
* **Real-time Logging**: Displays a detailed progress bar and status feed in your admin area to show successful compilations or skipped layout assets.

=== 🚫 What This Plugin Does NOT Fix (Blocksy Companion Issue) ===
This plugin is designed **only** to resolve compiled HTML/CSS mismatch glitches in the Brizy Builder. 
* It does not resolve layout width issues caused by Blocksy Companion.
* If your site is experiencing an issue where full-width pages have collapsed to default container width, this is a known conflict within the Blocksy Companion plugin stylesheet settings. That is an entirely separate issue.
* Test screencast of compilation and layout test: https://youtu.be/8ra4pF9fMIQ

== Installation ==

1. Upload the `brizy-fix` folder to your `/wp-content/plugins/` directory.
2. Activate **Brizy Fix** from your WordPress Plugins menu.
3. Navigate to **Tools > Brizy Fix** in your WordPress Admin Sidebar.
4. Click **Start Recompilation** and let the queue progress to 100%.

== Frequently Asked Questions ==

= Is it safe to use? =
Yes. It only updates the output caches and HTML wrappers, without deleting or altering your design JSON templates or content. However, we always recommend making a backup first.

= What should I do after compiling? =
Once the recompilation process is complete and you have verified that your layouts are fixed, you can safely deactivate and delete the plugin. It does not need to run continuously.

== Upgrade Notice ==

= 1.3.0 =
Enqueues JavaScript externally and maps internal hex UID titles to clear descriptions.
