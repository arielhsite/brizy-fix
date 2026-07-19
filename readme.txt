=== Layout Recompiler for Brizy ===
Contributors: ahabawel
Donate link: https://justanothertech.online
Tags: builder, layout, fix, migration, tools
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.2
Stable tag: 1.5.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Fixes broken Brizy layouts after a plugin update or site migration.

== Description ==

If your website layout looks broken after a WordPress migration, host transfer, or plugin update, the cause is often that Brizy's compiled HTML/CSS caches have fallen out of sync. 

**Layout Recompiler for Brizy** solves this by triggering a clean recompilation of all pages built using Brizy.

This plugin does not include license checks, trials, paid feature locks, usage quotas, or unlockable local features.

=== Key Features ===
* **AJAX-Based Execution**: Runs incrementally, compiling one page at a time. This keeps memory usage low and prevents common "500 Internal Server Error" or execution timeouts on low-memory servers (below 256MB).
* **Safe Re-generation**: The plugin reads your design templates in a read-only fashion (using JSON layouts stored in the database) and only updates the output HTML wrappers. Your database files and design structures are completely safe.
* **Real-time Logging**: Displays a detailed progress bar and status feed in your admin area to show successful compilations or skipped layout assets.

=== Brizy Builder Dependency and Service Disclosure ===

This plugin requires the Brizy Builder plugin to be installed and active. It does not send data to any external service directly and does not make its own remote API requests.

When you run the recompilation tool, this plugin calls Brizy Builder's local WordPress classes to recompile Brizy-enabled pages. Brizy Builder may use its own compiler URLs or download URLs as part of its normal compilation process. Any data handling, remote requests, terms, and privacy details for that process are controlled by Brizy Builder.

Brizy Builder terms: https://www.brizy.io/terms
Brizy Builder privacy policy: https://www.brizy.io/privacy-policy

=== Resolving Collapsed Page Widths (Blocksy Companion Conflict) ===

The Layout Recompiler can fix instances where full-width pages have suddenly collapsed to the default container width. If you are experiencing this layout issue due to a conflict with Blocksy Companion, follow these steps:

1. **Deactivate Blocksy Companion Premium** temporarily.
2. Run the **Layout Recompiler** queue to completion (100%).
3. Once finished, reactivate **Blocksy Companion Premium** and check the front-end of your site to see if the issue is resolved.

*Recommendations for Complex Sites*
For highly complex websites, we recommend temporarily disabling non-essential third-party plugins before running the recompilation process to ensure a clean execution. However:
* **WooCommerce Sites**: If your website uses dynamic templates to generate shop pages, cart/checkout areas, or product queries, **WooCommerce** and its essential related add-ons *must* remain active.
* **Dynamic Fields (ACF)**: Similarly, if your layouts rely heavily on **Advanced Custom Fields (ACF)** or other dynamic content engines to fetch database fields, those plugins *must* remain enabled during recompilation.

== Installation ==

1. Make sure the extracted plugin folder is named `layout-recompiler-for-brizy`. If you downloaded GitHub's automatic source archive, rename its `brizy-fix-main` folder before uploading it.
2. Upload the `layout-recompiler-for-brizy` folder to your `/wp-content/plugins/` directory.
3. Activate **Layout Recompiler for Brizy** from your WordPress Plugins menu.
4. Navigate to **Tools > Layout Recompiler** in your WordPress Admin Sidebar.
5. Click **Start Recompilation** and let the queue progress to 100%.

You can watch the screencast here: https://youtu.be/aOkBbAWAcWI

== Frequently Asked Questions ==

= Is it safe to use? =
Yes. It only updates the output caches and HTML wrappers, without deleting or altering your design JSON templates or content. However, we always recommend making a backup first.

= What should I do after compiling? =
Once the recompilation process is complete and you have verified that your layouts are fixed, you can safely deactivate and delete the plugin. It does not need to run continuously.

== Screenshots ==

1. Layout Recompiler appears under the WordPress Tools menu.
2. The recompilation screen shows the start button, progress bar, and live process log.

== Changelog ==

= 1.5.2 =
* Shows the optional review invitation only after the recompilation queue completes.
* Improves the review invitation spacing and emphasizes the plugin name.

= 1.5.1 =
* Added a non-intrusive invitation on the plugin's own Tools page to leave an honest WordPress.org review.

= 1.5.0 =
* Removed the missing-media scan and placeholder-repair tools so this plugin focuses exclusively on layout recompilation.
* Retained the existing page-by-page AJAX recompilation workflow and Brizy asset URL compatibility handling.

= 1.4.4 =
* Added blank placeholder creation for missing Brizy media when yellow fallback blocks are not desired.
* Blank placeholders can replace only plugin-created yellow placeholders or fill missing upload paths; real media files are never overwritten.
* Improves handling for Brizy background-image sections where removing a yellow placeholder can reveal the section fallback color.

= 1.4.3 =
* Added an option to remove yellow placeholder files created by the plugin.
* Existing yellow placeholders are now detected during the media scan and clearly marked in the report.
* The create and remove placeholder actions run in small batches and never overwrite or delete real media files.

= 1.4.2 =
* Improved Missing Brizy Media Scan so it can detect media references generated in rendered Brizy beta output.
* Fixed cases where the media scan reported no missing files even though the front end showed broken Brizy images.

= 1.4.1 =
* Added compatibility handling for Brizy and Brizy Pro asset URLs on subdirectory WordPress installs.
* Fixed an issue where Brizy beta could load front-end CSS and JavaScript from duplicated paths, causing pages to appear unstyled.

= 1.4.0 =
* Adds a missing Brizy media scanner with a clear report of missing local files, source links, and affected pages.
* Adds yellow placeholder creation for missing upload paths without overwriting existing files or changing database content.

= 1.3.0 =
* Enqueues JavaScript externally and maps internal hex UID titles to clear descriptions.

== Upgrade Notice ==

= 1.5.2 =
Shows the optional review invitation after recompilation completes.

= 1.5.1 =
Adds an optional WordPress.org review link on the plugin's Tools page.

= 1.5.0 =
Removes the media scan and placeholder tools; layout recompilation behavior is unchanged.

= 1.4.4 =
Adds blank placeholders for missing media and background-image sections when yellow placeholders are not desired.

= 1.4.3 =
Adds a safe option to remove yellow placeholders created by the plugin.

= 1.4.2 =
Improves missing media detection for Brizy beta rendered output.

= 1.4.1 =
Fixes Brizy and Brizy Pro front-end asset URLs on subdirectory installs.

= 1.4.0 =
Adds missing Brizy media scanning and safe yellow placeholder creation before recompilation.

= 1.3.0 =
Enqueues JavaScript externally and maps internal hex UID titles to clear descriptions.
