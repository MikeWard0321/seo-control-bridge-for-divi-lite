# SEO Control Bridge for Divi Lite

Free SEO workflow bridge for Divi and Rank Math by Electronic Enterprises, Inc.

SEO Control Bridge for Divi Lite gives site owners a lightweight way to edit Rank Math-compatible SEO metadata from a clean WordPress workflow. It is intentionally separate from the Pro product and does not include licensing, protected updates, bulk tools, schema templates, or agency features.

## Lite features

- SEO title, meta description, focus keyword, and canonical URL fields.
- OpenGraph and X/Twitter title, description, and image URL fields.
- Rank Math-compatible metadata saves.
- Page/post SEO completion column.
- Visual Builder/front-end overlay for logged-in editors.
- Pro upgrade link to EECONS.
- Getting Started and View Details links from the Installed Plugins screen.
- First-run Getting Started screen after activation.

## Pro features not included

- License activation or EECONS private updater.
- Bulk SEO Manager.
- Schema templates and custom JSON-LD workflow.
- Pro SEO Control Center modal with schema, social preview, bulk, import/export, and agency tools.
- Social previews.
- Import/export.
- White-labeling.
- Agency workflow tools.

## Requirements

- WordPress 6.4+
- PHP 8.0+
- Rank Math recommended but not strictly required
- Divi recommended but not strictly required

## Getting Started

1. Install and activate the plugin ZIP.
2. Open the automatic Getting Started screen, or use Settings > SEO Bridge Lite.
3. Confirm Rank Math is active if you want Rank Math to output the saved metadata.
4. Edit a page or post and complete the SEO Bridge Lite meta box.
5. In Divi Visual Builder, use the draggable SEO Bridge Lite button to open the overlay without leaving the builder.
6. Check the SEO completion column on Posts or Pages for a quick 0/3 to 3/3 metadata status.

## Development

```bash
composer install
npm install
```

This public Lite release is dependency-free and can be zipped directly.

## License

GPLv2 or later.


## Release 1.0.7

- Stops displaying the custom SCBD Lite update notice on the native WordPress Updates screen.
- Prevents stale orange notice output above successful update results.
- Keeps update detection available through Dashboard, Installed Plugins, and native WordPress update transients.

## Release 1.0.5

- Improves GitHub updater transient cleanup after one-click updates.
- Prevents stale Installed Plugins update banners after successful updates.
- Changes the custom dashboard notice to open the native WordPress Updates screen.

## GitHub Updates

Lite checks the public GitHub Releases API for newer tagged releases. When a release newer than the installed version is available, WordPress shows the update in Dashboard > Updates and Plugins > Installed Plugins.

For best results, each GitHub release should include this asset:

```text
seo-control-bridge-for-divi-lite.zip
```

The included GitHub Actions workflow builds that ZIP and attaches it to tagged GitHub releases.
