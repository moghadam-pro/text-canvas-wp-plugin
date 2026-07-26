# MPRO Text Canvas — Codex Handoff for v1.1.0

This document is the continuation context for a future Codex session. Read it before modifying the plugin.

## Project identity

- Plugin: **MPRO Text Canvas**
- Repository: `https://github.com/moghadam-pro/text-canvas-wp-plugin`
- WordPress/Elementor plugin family: **MPRO**
- Current released version: `v1.1.0`
- Release commit: `8d2daa830a35bfa574fb3f1e0d4b181e24c9f890`
- Release page: `https://github.com/moghadam-pro/text-canvas-wp-plugin/releases/tag/v1.1.0`
- Installable ZIP: `https://github.com/moghadam-pro/text-canvas-wp-plugin/releases/download/v1.1.0/mpro-text-canvas.zip`
- Updated Figma file: `https://www.figma.com/design/clm8JfD5Zh8JpOqUxzEDpo/MPRO---wordpress-plugin?node-id=1-13`
- Full online tool destination: `https://moghadam.pro/online-story-font`

## Original product intent

Build an Elementor widget that behaves like the text-editing stage in Instagram Stories, TikTok, and similar apps:

- The canvas always fills the width of its Elementor container.
- Text is edited inline after a click or tap.
- Clicking or tapping outside exits editing mode.
- Text layers can be moved freely.
- Text size can be changed with a desktop handle or a two-finger mobile gesture.
- Visitors can change font, text color, and canvas background color.
- The canvas can export a transparent PNG or a JPG with the background.
- Export dimensions must exactly match the rendered canvas.
- Exported files must be rectangular and must not include the visible border radius.
- A maximum of three text layers is supported.
- Settings must be available in Elementor and in the MPRO WordPress administration area.
- Administrators may add Google Fonts or upload WOFF2, WOFF, TTF, and OTF files.

## User requests for the v1.1.0 revision

The user requested the following changes:

1. Reinspect the updated Figma design, including desktop and mobile sizes.
2. Replace the incorrect arrow vector with the supplied/updated SVG.
3. Remove the old left-side action buttons.
4. Add a three-dot icon to the top control icons.
5. Put these actions inside the three-dot menu:
   - Reset
   - Add Text
   - Export Transparent / Export PNG
   - Export With BG / Export JPG
6. Add a final menu item linking to:
   - `https://moghadam.pro/online-story-font`
7. Remove WordPress and Elementor Global Color integration.
8. Use unrestricted color pickers without an inherited preset palette.
9. Add free text rotation:
   - Desktop: rotation handle.
   - Mobile: two-finger twist while pinch-resizing.
10. Limit the built-in font list to:
    - Roboto
    - Vazirmatn
    - Any additional fonts explicitly added by an administrator.
11. Make all three initial Figma text layers use Roboto by default.
12. Fix mobile export controls.
13. Investigate mobile browser automatic-download restrictions.
14. Preserve Elementor compatibility and coexistence with the MPRO plugin suite.
15. Test the result, commit it, push it to GitHub, and publish a versioned release.

## Figma facts verified during implementation

The Figma MCP metadata and screenshot were inspected.

### Desktop

- Artboard: `1280 × 874`
- Main component container: `1100 × 500`
- Control column: `120 × 500`
- Gap between controls and canvas: `24`
- Canvas: `956 × 500`
- Arrow asset: `120 × 120`
- Desktop icons are a vertical group.

### Mobile

- Artboard: `402 × 874`
- Inner width: `370`
- Top controls: `370 × 80`
- Gap below controls: `16`
- Canvas: `370 × 746`
- Arrow container: `80 × 80`
- Visible arrow asset: `64 × 64`
- Controls appear above the canvas.

### Initial Figma text layers

- `Curiosity`
- `NEW Products.`
- `Exploration`

All three are initialized with Roboto in the plugin. Responsive starting positions, widths, sizes, and rotations were added to approximate the supplied Figma composition. The second layer rotates vertically on mobile.

## Exact arrow asset

The vector was fetched directly from Figma node `1:13` and committed as:

`assets/images/arrow.svg`

Its source dimensions and `viewBox` are `120 × 120`. CSS renders it at:

- Desktop: `120 × 120`
- Mobile: `64 × 64`

Do not redraw or replace this file with a hand-authored arrow unless the user supplies a newer asset.

## Implementation changes in v1.1.0

### Interface and responsive layout

- Rebuilt the control layout to match the updated Figma structure.
- Changed the default desktop panel width from `308px` to `120px`.
- Changed the desktop gap from `48px` to `24px`.
- Changed the mobile canvas height from `610px` to `746px`.
- Changed the desktop canvas produced by the `1100px` test fixture from `744 × 500` to `956 × 500`.
- Moved mobile controls above the canvas.
- Added a responsive three-dot menu.
- Moved all four actions into the menu.
- Added the full online tool link as the final item.
- Updated README preview images.

### Rotation

- Added `data-rotation` state to every text layer.
- Added a visible desktop rotation handle.
- Added keyboard rotation support:
  - Left/Right Arrow: one degree.
  - Shift + Left/Right Arrow: fifteen degrees.
- Extended two-pointer gestures to calculate both:
  - Distance ratio for font resizing.
  - Angle delta for rotation.
- Normalized rotations to the `-180..180` range.
- Updated the Canvas exporter to rotate its drawing context around each layer center.

### Fonts

- Removed the old built-in system-font list.
- The only built-in choices are now:
  - `"Roboto", sans-serif`
  - `"Vazirmatn", sans-serif`
- Administrator-added Google and uploaded fonts remain available.
- Changed the administration default font to Roboto.
- Reset always restores the three initial layers with Roboto.
- Existing sites with an old unsupported saved font fall back to the first valid option, Roboto.

### Colors

- Removed `MPRO_TC_Fonts::get_site_palette()`.
- Removed active Elementor Kit system/custom color lookup.
- Removed the localized frontend palette.
- Removed frontend preset swatches.
- The modal now contains only unrestricted native color and hex inputs.
- Elementor color controls continue to use `global => array()` to disable Global Color binding.

### Mobile export fix

Root cause:

- The old export function awaited `document.fonts.ready`.
- On Safari and some mobile browsers, the delay caused the download to occur after the original tap's user-activation window had expired.
- Browsers could then ignore or block the automatic download.

Fix:

- Export is now synchronous from the initiating click/tap.
- The function no longer awaits fonts before creating the download.
- Canvas rendering, data URL creation, anchor insertion, and `click()` happen inside the same user interaction.
- The link also uses `rel="noopener"`.

### Export rotation support

- Export positions are now derived from the unrotated stage coordinate system.
- Each layer is translated to its center and the export context is rotated by the layer angle.
- Text wrapping, alignment, line height, font properties, background behavior, exact dimensions, and square export corners remain supported.

### Elementor controls

Added:

- Initial text 1
- Initial text 2
- Initial text 3
- New-layer text
- Full-page tool menu label

Updated:

- Mobile frame-height default: `746px`
- Desktop panel-width default: `120px`
- Desktop layout-gap default: `24px`

## Important files

- `mpro-text-canvas.php`
  - Plugin bootstrap and current version.
- `includes/class-mpro-tc-elementor-widget.php`
  - Elementor controls, responsive configuration, and rendered widget markup.
- `includes/class-mpro-tc-fonts.php`
  - Built-in and administrator-added font options.
- `includes/class-mpro-tc-admin.php`
  - WordPress settings and font administration.
- `assets/js/frontend.js`
  - Editing, dragging, resizing, rotation, menu, modals, and export logic.
- `assets/css/frontend.css`
  - Responsive layout, handles, menu, canvas, and modal styling.
- `assets/images/arrow.svg`
  - Exact Figma vector.
- `tests/browser-fixture.html`
  - Standalone browser integration fixture.
- `tests/run_browser_smoke.py`
  - CI browser smoke runner.
- `.github/workflows/ci.yml`
  - CI checks.
- `.github/workflows/release.yml`
  - Release creation triggered by `.release-trigger`.
- `.release-trigger`
  - Currently contains `v1.1.0`.

## Tests completed

The local Chromium/Playwright smoke test passed at both sizes.

### Desktop assertions

- Widget initialization.
- Three initial layers.
- All initial layers use Roboto.
- Tap/click editing.
- Outside interaction exits editing.
- Dragging.
- Pinch resizing.
- Two-finger rotation.
- Desktop rotation handle.
- Three-layer limit.
- Disabled Add Text at the layer limit.
- Reset.
- Three-dot menu and online-tool link.
- Font modal.
- Free color modal.
- No color presets.
- Transparent PNG generation.
- Background JPG generation.
- Exact export dimensions.
- Transparent PNG corner.
- Filled square JPG corner.
- Canvas: `956 × 500`.
- Arrow: `120 × 120`.

### Mobile assertions

The same interaction/export assertions passed with:

- Canvas: `370 × 746`.
- Arrow: `64 × 64`.

### GitHub verification

- CI run for commit `8d2daa8`: successful.
- Publish Release run for commit `8d2daa8`: successful.
- Release `v1.1.0`: published.
- Asset `mpro-text-canvas.zip`: published.

## Version files updated

- Plugin header: `1.1.0`
- `MPRO_TC_VERSION`: `1.1.0`
- `readme.txt` stable tag: `1.1.0`
- `.release-trigger`: `v1.1.0`
- `CHANGELOG.md`
- `RELEASE-NOTES.md`
- `README.md`

## Continuation rules for the next Codex session

1. Pull the latest `main` before editing.
2. Read this handoff and inspect the latest user feedback.
3. Treat Figma as the visual source of truth and call `get_design_context` before design-to-code edits.
4. Preserve the exact committed arrow asset unless a new one is supplied.
5. Keep the built-in font list limited to Roboto and Vazirmatn.
6. Do not restore Elementor/WordPress palette inheritance unless explicitly requested.
7. Preserve synchronous export initiation for mobile browser compatibility.
8. When changing transforms, update both DOM behavior and Canvas export rendering.
9. Run desktop and mobile smoke tests after every interaction or layout change.
10. Bump the version and update all release files together before publishing another release.

## Suggested next validation

The automated tests are green. The next useful step is hands-on validation inside the user's real WordPress/Elementor page, especially:

- iPhone Safari download behavior.
- Android Chrome download behavior.
- Actual administrator-added custom fonts.
- Elementor editor preview refresh behavior.
- Text rotation and selection with real multitouch hardware.
- Whether the default three-layer positions should be adjusted after the user compares them with the production page.

