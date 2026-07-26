# MPRO Text Canvas v1.1.0

This release updates the interface to the revised Figma design and improves touch behavior.

## Highlights

- Edit text directly on the canvas by clicking or tapping it.
- Drag, resize, and freely rotate text layers.
- Rotate with a desktop handle or a two-finger mobile gesture.
- Add up to three independent text layers.
- Open Reset, Add Text, both exports, and the full online tool from the new three-dot menu.
- Select unrestricted text/background colors without WordPress or Elementor palette inheritance.
- Use only Roboto and Vazirmatn by default, plus fonts explicitly added by an administrator.
- Configure Google Fonts and uploaded font files from WordPress Admin.
- Export a transparent PNG or a JPG with the current background.
- Export at the exact live dimensions of the canvas, with no rounded corners in the generated file.
- Export reliably from mobile browsers without losing the initiating tap to an asynchronous font wait.

## Default responsive layout

- Desktop/tablet canvas height: **500 px**
- Mobile canvas height: **746 px**
- Desktop toolbar width: **120 px**
- Desktop gap: **24 px**
- Mobile gap: **16 px**

## Package

Upload `mpro-text-canvas.zip` through **WordPress Admin → Plugins → Add New → Upload Plugin**.

## Compatibility

- WordPress 5.8+
- PHP 7.4+
- Elementor 3.x
- Modern browsers with Pointer Events and Canvas support

## Verification

The release package includes automated browser coverage for editing, drag, pinch resize/rotation, desktop rotation, three-layer defaults, the actions menu, unrestricted color controls, and PNG/JPG export.
