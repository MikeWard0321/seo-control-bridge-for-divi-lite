=== SEO Control Bridge - Lite ===
Contributors: mikeward0321
Tags: seo, divi, rank math, metadata, visual builder
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Requires Plugins: seo-by-rank-math
Stable tag: 1.1.10
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bridge Divi Visual Builder workflows with Rank Math SEO controls for metadata, social fields, and canonical URLs.

== Description ==

SEO Control Bridge - Lite is built for WordPress sites that use both the Divi Theme and Rank Math SEO.

Divi gives developers, agencies, and site admins a powerful visual editing workflow. Rank Math gives those same sites a strong SEO output layer. The gap is that SEO controls often live outside the builder experience, forcing users to jump between the visual page, the WordPress editor, and SEO plugin panels.

SEO Control Bridge - Lite bridges that gap. It gives editors a focused SEO overlay while working on the current Divi-built page, so page-level SEO decisions can happen in the same workflow as page design.

With SEO Control Bridge - Lite, developers and admins can manage core Rank Math-compatible metadata while staying close to the visual layout they are optimizing.

Lite includes:

* SEO title, meta description, focus keyword, and canonical URL fields.
* OpenGraph and X/Twitter title, description, and image URL fields.
* Rank Math SEO-compatible metadata saves.
* Divi Visual Builder/front-end overlay for editing the current page without leaving the builder.
* Draggable floating launcher that remembers its position per page.
* Admin list SEO completion column for posts and pages.
* Getting Started screen after activation.

Required:

* Divi Theme.
* Rank Math SEO.

The Lite version does not include Pro licensing, private updates, bulk SEO tools, schema templates, advanced social previews, import/export, white-labeling, or agency workflow features.

SEO Control Bridge - Lite is not affiliated with, endorsed by, or sponsored by Rank Math, Elegant Themes, or Divi. Rank Math and Divi are trademarks of their respective owners.

== Installation ==

1. Install and activate the Divi Theme.
2. Install and activate Rank Math SEO.
3. Upload the SEO Control Bridge - Lite plugin folder to `/wp-content/plugins/`, or install the ZIP from Plugins > Add New.
4. Activate SEO Control Bridge - Lite.
5. Open Settings > SEO Bridge - Lite and confirm both requirements are detected.
6. Open a page in the Divi Visual Builder and click the floating SEO Bridge - Lite button.
7. Edit SEO title, meta description, focus keyword, canonical URL, and social metadata without leaving the builder.

== Getting Started ==

After activation, SEO Control Bridge - Lite opens its Getting Started screen. You can return to it later from Settings > SEO Bridge - Lite or from the Getting Started link on the Installed Plugins screen.

Recommended first steps:

1. Confirm Divi Theme is active.
2. Confirm Rank Math SEO is active.
3. Edit a page or post and complete the SEO title, meta description, and focus keyword fields.
4. Open the same page in Divi Visual Builder and test the floating SEO Bridge - Lite overlay.
5. Drag the floating launcher to your preferred location. The position is remembered for that page.
6. Review the SEO completion column on the Pages or Posts list.

== Frequently Asked Questions ==

= Does this replace Rank Math SEO? =

No. SEO Control Bridge - Lite requires Rank Math SEO. It saves metadata into Rank Math-compatible fields so Rank Math can handle SEO output.

= Does this require Divi? =

Yes. SEO Control Bridge - Lite is intentionally designed for Divi Theme sites. Its primary purpose is to bridge Divi Visual Builder editing with Rank Math SEO controls.

= Why bridge Divi and Rank Math? =

The visual content experience and SEO metadata workflow are often separated. This plugin gives developers and admins more control by keeping essential SEO metadata editing close to the Divi page they are actively building or reviewing.

= Does Lite include Pro license activation? =

No. Lite is public and does not contain any EECONS License Manager code, private updater code, license endpoints, or protected download logic.

= Can I upgrade to Pro? =

Yes. The settings screen includes a Pro upgrade link for users who need bulk SEO tools, schema templates, social previews, imports/exports, white-labeling, and agency workflow features.

== Screenshots ==

1. SEO Bridge - Lite Getting Started screen with requirement checks.
2. Divi Visual Builder floating launcher.
3. SEO metadata overlay panel inside the builder workflow.
4. SEO completion column for pages and posts.

== Changelog ==

= 1.1.10 =
* Corrected the Installed Plugins GitHub row link to point to the active GitHub repository.

= 1.1.9 =
* Replaced the settings screen View Details button with an internal View Guide link so local and review installs do not open a WordPress.org plugin-information page before directory approval.

= 1.1.8 =
* Updated internal menu slug, transient, nonce, form, column, script handle, and REST namespace identifiers to use the full seo_control_bridge_lite / seo-control-bridge-lite prefix for WordPress.org review compliance.

= 1.1.7 =
* Normalized Divi Visual Builder preview post IDs so SEO fields load and save against the real positive WordPress post ID.

= 1.1.4 =
* Fixed Visual Builder SEO field saving by moving builder overlay load/save requests to a nonce-protected WordPress AJAX endpoint.

= 1.1.2 =
* Prevented duplicate Visual Builder launch buttons by suppressing the parent Divi Builder shell launcher.
* Removed manual translation loading for WordPress.org-hosted translation compatibility.
* Removed the unused Domain Path header.
* Improved form input handling for Plugin Check compliance.
* Removed hidden development files from the WordPress.org package.

= 1.1.0 =
* Renamed the public plugin to SEO Control Bridge - Lite.
* Converted the WordPress.org build to require Rank Math SEO through the Requires Plugins header.
* Added Divi Theme requirement detection and admin notices.
* Rewrote onboarding and plugin copy around bridging Divi Visual Builder workflows with Rank Math SEO metadata controls.
* Removed the GitHub updater from the WordPress.org-safe build.
* Prepared the plugin for WordPress.org native update handling.

= 1.0.9 =
* Improved GitHub-distributed update detection in the public GitHub build.
