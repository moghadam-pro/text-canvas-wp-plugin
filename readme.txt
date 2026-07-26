=== MPRO Text Canvas ===
Contributors: moghadam-pro
Tags: elementor, text editor, canvas, image export, typography
Requires at least: 5.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Interactive text-composition canvas for Elementor with inline editing, drag, pinch resizing, custom fonts, colors, and PNG/JPG export.

== Description ==

MPRO Text Canvas adds a responsive visual text workspace to Elementor. Visitors can edit text directly on the canvas, move and resize up to three text layers, choose fonts and colors, reset the composition, and download transparent PNG or background JPG files.

Key capabilities:

* Inline click/tap editing
* Drag positioning
* Two-finger pinch resizing
* Maximum three text layers
* Google Fonts and uploaded fonts
* Elementor Global Color presets
* Transparent PNG export
* JPG export with solid background
* Exact current frame dimensions
* No border radius in exported files
* Responsive Elementor controls

Global defaults and font management are available under MPRO → Text Canvas.

== Installation ==

1. Upload `mpro-text-canvas.zip` from Plugins → Add New → Upload Plugin.
2. Activate MPRO Text Canvas.
3. Ensure Elementor is installed and active.
4. Configure defaults under MPRO → Text Canvas.
5. Add the MPRO Text Canvas widget to an Elementor page.

== Frequently Asked Questions ==

= Does the plugin save visitor compositions? =

No. Version 1.0.0 keeps composition state in the current browser session only and exports directly on the visitor's device.

= Are exported images rounded? =

No. The visible frame can use a corner radius, but exports are always rectangular.

= How many text layers can be added? =

Three total. The Add Text action disables after the third layer is created.

= Can I use my Elementor site colors? =

Yes. Elementor color controls support Global Colors, and the frontend picker includes colors from the active Elementor Kit.

= Can I upload custom fonts? =

Yes. WOFF2, WOFF, TTF, and OTF files are supported through the WordPress Media Library.

== Screenshots ==

1. Desktop Text Canvas layout.
2. Mobile Text Canvas layout.
3. WordPress default and font settings.
4. Elementor widget controls.

== Changelog ==

= 1.0.0 =

* Initial release.
* Inline editing, dragging, pinch resizing, and three-layer support.
* Font and color controls.
* Transparent PNG and background JPG export.
* WordPress and Elementor settings.
