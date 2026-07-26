# Changelog

All notable changes to **MPRO Text Canvas** are documented in this file.

The project follows [Semantic Versioning](https://semver.org/).

## [1.1.0] - 2026-07-26

### Added

- Three-dot actions menu with a link to the full online tool.
- Free rotation with a desktop handle and two-finger touch gestures.
- Rotation-aware PNG and JPG rendering.

### Changed

- Moved Reset, Add Text, and both export actions into the top toolbar menu.
- Limited built-in fonts to Roboto and Vazirmatn; administrator-added fonts remain available.
- Made all three initial text layers use Roboto.
- Removed WordPress and Elementor color-palette presets from the visitor color picker.
- Kept mobile export generation inside the initiating tap to avoid browser automatic-download restrictions.

## [1.0.0] - 2026-07-26

### Added

- Initial public release.
- Dedicated **MPRO Text Canvas** Elementor widget.
- Responsive desktop and mobile layouts matching the supplied design.
- Full-width canvas behavior inside any Elementor container.
- Default 500 px desktop/tablet and 610 px mobile frame heights.
- Inline click/tap text editing.
- Click/tap-outside editing exit.
- Pointer-based layer dragging.
- Two-finger pinch text resizing.
- Mouse/touch resize handle and keyboard resize controls.
- Maximum of three text layers with automatic Add Text disabling.
- Layer deletion control and Delete/Backspace support.
- Full reset to widget defaults.
- Font-selection modal.
- Text and background color-picker modals.
- Elementor active-kit system and custom color presets.
- Transparent PNG export.
- JPG export with solid background.
- Exact live-frame export dimensions.
- Rectangular export output without border radius.
- WordPress administration settings for default text and colors.
- Arbitrary Google Fonts family configuration with weight selection.
- Uploaded WOFF2, WOFF, TTF, and OTF font support.
- Elementor controls for content, canvas, typography, initial placement, toolbar, and buttons.
- Shared MPRO administration menu integration.
- Accessible labels, keyboard focus styles, live status messages, and reduced-motion handling.
- Dependency-free frontend JavaScript.
- Browser smoke tests for interaction and export behavior.
- Automated lint, packaging, and GitHub Release workflows.
