# Layout Recompiler for Brizy

A lightweight, performance-optimized utility plugin for WordPress to fix broken page layouts and styling mismatches caused by **Brizy Builder** cache/compilation sync issues.

## Description

If your website layout looks broken after a WordPress migration, host transfer, or plugin update, the cause is often that Brizy's compiled HTML/CSS caches have fallen out of sync. 

**Layout Recompiler for Brizy** solves this by triggering a clean recompilation of all pages built using Brizy.

### Key Features
* **AJAX-Based Execution**: Runs incrementally, compiling one page at a time. This keeps memory usage low and prevents common "500 Internal Server Error" or execution timeouts on low-memory servers (below 256MB).
* **Safe Re-generation**: The plugin reads your design templates in a read-only fashion (using JSON layouts stored in the database) and only updates the output HTML wrappers. Your database files and design structures are completely safe.
* **Real-time Logging**: Displays a detailed progress bar and status feed in your admin area to show successful compilations or skipped layout assets.

---

## ⚠️ Important: Backup Your Site Before Use

> [!IMPORTANT]
> **You must create a full database and filesystem backup of your website before running this utility.** While the plugin is designed to run safely, altering database layouts is a major action. Having a complete restore point ensures you can undo changes if any plugin conflict arises. 
> 
> We recommend using a backup plugin like **WPvivid** or taking a manual backup via your hosting dashboard.

---

## 🚫 What This Plugin Does NOT Fix (Blocksy Companion Issue)

This plugin is designed **only** to resolve compiled HTML/CSS mismatch glitches in the Brizy Builder. 

* **It does not resolve layout width issues caused by Blocksy Companion.**
* If your site is experiencing an issue where full-width pages have suddenly collapsed to default container width, this is a known conflict within the Blocksy Companion plugin stylesheet settings. 
* That is an entirely separate issue and will not be fixed by compiling Brizy pages.
* A detailed screencast demonstrating the compilation and layout tests is available here: [Watch Screencast on YouTube](https://youtu.be/8ra4pF9fMIQ)

---

## Installation & Usage

1. Upload the `layout-recompiler-for-brizy` folder to your `/wp-content/plugins/` directory.
2. Activate **Layout Recompiler for Brizy** from your WordPress Plugins menu.
3. Navigate to **Tools > Layout Recompiler** in your WordPress Admin Sidebar.
4. Click **Start Recompilation** and let the queue progress to 100%.

[![Download Layout Recompiler for Brizy](./download-button.svg)](https://github.com/arielhsite/brizy-fix/archive/refs/heads/main.zip)

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
