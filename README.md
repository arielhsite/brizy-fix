# Layout Recompiler for Brizy

<table border="0" cellpadding="15" cellspacing="0" align="center">
  <tr>
    <td valign="top" width="45%">
      <img src="https://justanothertech.online/wp-content/uploads/2026/07/screenshot-1.jpg" alt="Layout Recompiler Sidebar Menu" width="100%" />
    </td>
    <td valign="top" width="55%">
      <img src="https://justanothertech.online/wp-content/uploads/2026/07/screenshot-2.jpg" alt="Recompilation Interface" width="100%" />
    </td>
  </tr>
</table>

A lightweight, performance-optimized utility plugin for WordPress to fix broken page layouts and styling mismatches caused by **Brizy Builder** cache/compilation sync issues.

## Description

If your website layout looks broken after a WordPress migration, host transfer, or plugin update, the cause is often that Brizy's compiled HTML/CSS caches have fallen out of sync. 

**Layout Recompiler for Brizy** solves this by triggering a clean recompilation of all pages built using Brizy.

This plugin does not include license checks, trials, paid feature locks, usage quotas, or unlockable local features.

### Key Features
* **AJAX-Based Execution**: Runs incrementally, compiling one page at a time. This keeps memory usage low and prevents common "500 Internal Server Error" or execution timeouts on low-memory servers (below 256MB).
* **Safe Re-generation**: The plugin reads your design templates in a read-only fashion (using JSON layouts stored in the database) and only updates the output HTML wrappers. Your database files and design structures are completely safe.
* **Real-time Logging**: Displays a detailed progress bar and status feed in your admin area to show successful compilations or skipped layout assets.

## Brizy Builder Dependency and Service Disclosure

This plugin requires the Brizy Builder plugin to be installed and active. It does not send data to any external service directly and does not make its own remote API requests.

When you run the recompilation tool, this plugin calls Brizy Builder's local WordPress classes to recompile Brizy-enabled pages. Brizy Builder may use its own compiler URLs or download URLs as part of its normal compilation process. Any data handling, remote requests, terms, and privacy details for that process are controlled by Brizy Builder.

Brizy Builder terms: https://www.brizy.io/terms

Brizy Builder privacy policy: https://www.brizy.io/privacy-policy

---

## ⚠️ Important: Backup Your Site Before Use

> [!IMPORTANT]
> **You must create a full database and filesystem backup of your website before running this utility.** While the plugin is designed to run safely, altering database layouts is a major action. Having a complete restore point ensures you can undo changes if any plugin conflict arises. 
> 
> We recommend using a backup plugin like **WPvivid** or taking a manual backup via your hosting dashboard.

---

## Resolving Collapsed Page Widths (Blocksy Companion Conflict)

The Layout Recompiler can fix instances where full-width pages have suddenly collapsed to the default container width. If you are experiencing this layout issue due to a conflict with Blocksy Companion, follow these steps:

1. **Deactivate Blocksy Companion Premium** temporarily.
2. Run the **Layout Recompiler** queue to completion (100%).
3. Once finished, reactivate **Blocksy Companion Premium** and check the front-end of your site to see if the issue is resolved.

### Recommendations for Complex Sites
For highly complex websites, we recommend temporarily disabling non-essential third-party plugins before running the recompilation process to ensure a clean execution. However:
* **WooCommerce Sites**: If your website uses dynamic templates to generate shop pages, cart/checkout areas, or product queries, **WooCommerce** and its essential related add-ons *must* remain active.
* **Dynamic Fields (ACF)**: Similarly, if your layouts rely heavily on **Advanced Custom Fields (ACF)** or other dynamic content engines to fetch database fields, those plugins *must* remain enabled during recompilation.

---

## Installation & Usage

1. Download the plugin zip archive.
2. Make sure the extracted plugin folder is named `layout-recompiler-for-brizy`. If you downloaded GitHub's automatic source archive, rename its `brizy-fix-main` folder before uploading it.
3. Upload the `layout-recompiler-for-brizy` folder to your `/wp-content/plugins/` directory.
4. Activate **Layout Recompiler for Brizy** from your WordPress Plugins menu.
5. Navigate to **Tools > Layout Recompiler** in your WordPress Admin Sidebar.
6. Click **Start Recompilation** and let the queue progress to 100%.

You can watch the screencast here: https://youtu.be/aOkBbAWAcWI

---

## FAQ

### Is it safe to use?
Yes. It only updates the output caches and HTML wrappers, without deleting or altering your design JSON templates or content. However, we always recommend making a backup first.

### What should I do after compiling?
Once the recompilation process is complete and you have verified that your layouts are fixed, you can safely deactivate and delete the plugin. It does not need to run continuously.

---

## After Use

Once the recompilation process is complete and you have verified that your website layouts are restored, you can safely **deactivate and delete** the Layout Recompiler for Brizy plugin. It does not need to remain active on your site and will not affect the compiled pages after deletion.

## License

This project is licensed under the GPLv2 or later License.
