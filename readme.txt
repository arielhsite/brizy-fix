=== Layout Recompiler for Brizy ===
Contributors: arielhsite, ahabawel
Donate link: https://justanothertech.online
Tags: brizy, builder, layout, fix, recompile
Requires at least: 5.0
Tested up to: 7.0
Stable tag: 1.3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Recompiles all Brizy Builder pages to fix broken styling, caching, and layout mismatches.

== Description ==

If your website layout looks broken after a WordPress migration, host transfer, or plugin update, the cause is often that Brizy's compiled HTML/CSS caches have fallen out of sync. 

**Layout Recompiler for Brizy** solves this by triggering a clean recompilation of all pages built using Brizy.

=== Key Features ===
* **AJAX-Based Execution**: Runs incrementally, compiling one page at a time. This keeps memory usage low and prevents common "500 Internal Server Error" or execution timeouts on low-memory servers (below 256MB).
* **Safe Re-generation**: The plugin reads your design templates in a read-only fashion (using JSON layouts stored in the database) and only updates the output HTML wrappers. Your database files and design structures are completely safe.
* **Real-time Logging**: Displays a detailed progress bar and status feed in your admin area to show successful compilations or skipped layout assets.

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

1. Upload the `layout-recompiler-for-brizy` folder to your `/wp-content/plugins/` directory.
2. Activate **Layout Recompiler for Brizy** from your WordPress Plugins menu.
3. Navigate to **Tools > Layout Recompiler** in your WordPress Admin Sidebar.
4. Click **Start Recompilation** and let the queue progress to 100%.

You can watch the screencast here: https://youtu.be/aOkBbAWAcWI

== Frequently Asked Questions ==

= Is it safe to use? =
Yes. It only updates the output caches and HTML wrappers, without deleting or altering your design JSON templates or content. However, we always recommend making a backup first.

= What should I do after compiling? =
Once the recompilation process is complete and you have verified that your layouts are fixed, you can safely deactivate and delete the plugin. It does not need to run continuously.

== Upgrade Notice ==

= 1.3.0 =
Enqueues JavaScript externally and maps internal hex UID titles to clear descriptions.
