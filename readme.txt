=== SEO Control Bridge for Divi Lite ===
Contributors: eecons
Tags: seo, divi, rank math, metadata, open graph
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Free SEO workflow bridge for Divi and Rank Math. Edit SEO metadata, social metadata, and canonical fields from a clean WordPress workflow.

== Description ==

SEO Control Bridge for Divi Lite helps WordPress site owners using Divi and Rank Math manage essential SEO metadata without jumping between disconnected screens.

Lite includes:

* SEO title, meta description, focus keyword, and canonical URL fields.
* OpenGraph and X/Twitter title, description, and image URL fields.
* Rank Math-compatible metadata saves.
* Admin list SEO completion column.
* Divi Visual Builder/front-end overlay for editing the current page without leaving the builder.
* GitHub-powered update checks that appear in the WordPress Dashboard, Updates screen, and Installed Plugins screen.

The Lite version does not include Pro licensing, private updates, bulk SEO tools, schema templates, advanced social previews, import/export, white-labeling, or agency features.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install the ZIP from Plugins > Add New.
2. Activate SEO Control Bridge for Divi Lite.
3. Open any page or post and use the SEO Bridge Lite panel, or open the Divi Visual Builder and click SEO Bridge Lite to edit metadata in the overlay.
4. Rank Math is recommended for full SEO output handling.

== Frequently Asked Questions ==

= Does this replace Rank Math? =

No. It is a workflow bridge that saves Rank Math-compatible metadata. Rank Math should remain installed and active for complete SEO output.

= Does Lite include Pro license activation? =

No. Lite is public and does not contain any EECONS License Manager code, private updater code, license endpoints, or protected download logic.

= Can I upgrade to Pro? =

Yes. The settings screen includes a Pro upgrade link.

== Screenshots ==

1. SEO Bridge Lite metadata panel.
2. SEO completion column for pages and posts.
3. Lite settings and Pro upgrade screen.

== Changelog ==

= 1.0.3 =
* Added GitHub release update checks for public Lite builds.
* Integrated GitHub releases with the native WordPress plugin update system.
* Added dashboard/admin update notices when a newer GitHub release is available.
* Added release asset support for update packages named `seo-control-bridge-for-divi-lite.zip`.

= 1.0.2 =
* Rebuilt the front-end/Divi launcher as an iframe-local Visual Builder overlay.
* Added draggable floating button positioning constrained to the current iframe viewport.
* Saved the launcher position in browser localStorage and restored it after refreshes.
* Increased overlay stacking and click handling so the Lite panel opens above the builder instead of navigating away.

= 1.0.0 =
* Initial public Lite release.
