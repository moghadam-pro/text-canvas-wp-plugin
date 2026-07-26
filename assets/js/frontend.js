(function () {
	'use strict';

	var SELECTOR = '.mpro-tc[data-mpro-config]';
	var uid = 0;

	function clamp(value, min, max) {
		return Math.min(max, Math.max(min, value));
	}

	function deepClone(value) {
		return JSON.parse(JSON.stringify(value));
	}

	function distance(a, b) {
		var dx = a.x - b.x;
		var dy = a.y - b.y;
		return Math.sqrt(dx * dx + dy * dy);
	}

	function angle(a, b) {
		return Math.atan2(b.y - a.y, b.x - a.x) * 180 / Math.PI;
	}

	function normalizeAngle(value) {
		value = Number(value) || 0;
		while (value > 180) value -= 360;
		while (value < -180) value += 360;
		return value;
	}

	function rgbToHex(value) {
		if (!value) return '#000000';
		if (value.charAt(0) === '#') {
			if (value.length === 4) {
				return '#' + value.slice(1).split('').map(function (x) { return x + x; }).join('');
			}
			return value.slice(0, 7);
		}
		var match = value.match(/rgba?\(\s*(\d+)[,\s]+(\d+)[,\s]+(\d+)/i);
		if (!match) return '#000000';
		return '#' + [match[1], match[2], match[3]].map(function (part) {
			return Number(part).toString(16).padStart(2, '0');
		}).join('');
	}

	function safeColor(value, fallback) {
		var test = document.createElement('span');
		test.style.color = '';
		test.style.color = value;
		return test.style.color ? value : fallback;
	}

	function setCaretToEnd(element) {
		try {
			var range = document.createRange();
			range.selectNodeContents(element);
			range.collapse(false);
			var selection = window.getSelection();
			selection.removeAllRanges();
			selection.addRange(range);
		} catch (error) {
			// Selection APIs can be unavailable in unusual embedded previews.
		}
	}

	function textFromElement(element) {
		return (element.innerText || element.textContent || '').replace(/\u00a0/g, ' ');
	}

	function MPROTextCanvas(root) {
		this.root = root;
		this.frame = root.querySelector('[data-mpro-frame]');
		this.stage = root.querySelector('[data-mpro-stage]');
		this.modal = root.querySelector('[data-mpro-modal]');
		this.modalTitle = root.querySelector('[data-mpro-modal-title]');
		this.modalBody = root.querySelector('[data-mpro-modal-body]');
		this.status = root.querySelector('[data-mpro-status]');
		this.config = this.readConfig();
		this.initialConfig = deepClone(this.config);
		this.initialFrameBackground = getComputedStyle(this.frame).backgroundColor || this.config.background;
		this.initialTextColor = this.readInitialTextColor();
		this.layers = [];
		this.selectedLayer = null;
		this.activePointers = new Map();
		this.gesture = null;
		this.lastFocusedBeforeModal = null;
		this.boundDocumentPointerDown = this.onDocumentPointerDown.bind(this);
		this.boundDocumentKeyDown = this.onDocumentKeyDown.bind(this);
		this.init();
	}

	MPROTextCanvas.prototype.readConfig = function () {
		var fallback = {
			defaultText: 'YOU CAN EDIT THIS TEXT\nWRITE EVERYTHING YOU WANT\nAND MOVE',
			addedText: 'YOU CAN EDIT THIS TEXT\nWRITE EVERYTHING YOU WANT\nAND MOVE',
			maxLayers: 3,
			initialTexts: ['Curiosity', 'NEW Products.', 'Exploration'],
			background: '#ffb700',
			textColor: '#000000',
			fontFamily: '"Roboto", sans-serif',
			fontWeight: '700',
			lineHeight: 1.45,
			textAlign: 'right',
			desktop: { x: 0.68, y: 0.84, width: 0.59, fontSize: 24 },
			tablet: { x: 0.68, y: 0.84, width: 0.59, fontSize: 24 },
			mobile: { x: 0.52, y: 0.80, width: 0.78, fontSize: 24 },
			fonts: []
		};
		try {
			var parsed = JSON.parse(this.root.getAttribute('data-mpro-config') || '{}');
			return Object.assign(fallback, parsed);
		} catch (error) {
			return fallback;
		}
	};

	MPROTextCanvas.prototype.readInitialTextColor = function () {
		var probe = document.createElement('div');
		probe.className = 'mpro-tc__text';
		probe.style.position = 'absolute';
		probe.style.visibility = 'hidden';
		probe.textContent = 'A';
		this.stage.appendChild(probe);
		var color = getComputedStyle(probe).color || this.config.textColor;
		probe.remove();
		return color;
	};

	MPROTextCanvas.prototype.init = function () {
		this.root.dataset.mproInitialized = 'true';
		this.bindToolbar();
		this.bindModal();
		this.reset();
		document.addEventListener('pointerdown', this.boundDocumentPointerDown, true);
		document.addEventListener('keydown', this.boundDocumentKeyDown);
	};

	MPROTextCanvas.prototype.getResponsiveDefaults = function () {
		var width = this.root.getBoundingClientRect().width || window.innerWidth;
		if (width <= 767) return this.config.mobile || this.config.desktop;
		if (width <= 1024) return this.config.tablet || this.config.desktop;
		return this.config.desktop;
	};

	MPROTextCanvas.prototype.reset = function () {
		this.exitEdit();
		this.layers.forEach(function (layer) { layer.remove(); });
		this.layers = [];
		this.selectedLayer = null;
		this.frame.style.backgroundColor = safeColor(this.initialFrameBackground, this.config.background || '#ffb700');
		var initialTexts = Array.isArray(this.config.initialTexts) && this.config.initialTexts.length
			? this.config.initialTexts.slice(0, Number(this.config.maxLayers || 3))
			: [this.config.defaultText];
		for (var index = 0; index < initialTexts.length; index++) {
			this.createLayer({
				text: initialTexts[index],
				color: this.initialTextColor,
				fontFamily: '"Roboto", sans-serif',
				fontWeight: this.config.fontWeight,
				lineHeight: this.config.lineHeight,
				textAlign: this.config.textAlign
			}, false);
		}
		this.selectLayer(null);
		this.updateToolbar();
		this.announce('Canvas reset.');
	};

	MPROTextCanvas.prototype.createLayer = function (data, editImmediately) {
		if (this.layers.length >= Number(this.config.maxLayers || 3)) return null;

		var defaults = this.getResponsiveDefaults();
		var index = this.layers.length;
		var layer = document.createElement('div');
		var text = document.createElement('div');
		var resize = document.createElement('span');
		var rotate = document.createElement('span');
		var remove = document.createElement('button');
		var id = 'mpro-tc-layer-' + (++uid);

		layer.className = 'mpro-tc__layer';
		layer.dataset.layerId = id;
		var designedLayers = defaults.layers || (this.config.desktop && this.config.desktop.layers) || [];
		var designed = designedLayers[index] || {};
		layer.dataset.x = String(clamp(data.x != null ? data.x : (designed.x != null ? designed.x : defaults.x), 0.05, 0.95));
		layer.dataset.y = String(clamp(data.y != null ? data.y : (designed.y != null ? designed.y : defaults.y), 0.05, 0.95));
		layer.dataset.fontSize = String(data.fontSize || designed.fontSize || defaults.fontSize || 26);
		layer.dataset.rotation = String(normalizeAngle(data.rotation != null ? data.rotation : (designed.rotation || 0)));
		layer.style.left = (Number(layer.dataset.x) * 100) + '%';
		layer.style.top = (Number(layer.dataset.y) * 100) + '%';
		layer.style.width = ((data.width != null ? data.width : (designed.width || defaults.width)) * 100) + '%';
		layer.style.transform = 'translate(-50%, -50%) rotate(' + layer.dataset.rotation + 'deg)';

		text.className = 'mpro-tc__text';
		text.setAttribute('contenteditable', 'false');
		text.setAttribute('spellcheck', 'false');
		text.setAttribute('dir', 'auto');
		text.setAttribute('role', 'textbox');
		text.setAttribute('aria-multiline', 'true');
		text.dataset.placeholder = this.config.defaultText || 'Write something…';
		text.textContent = data.text != null ? data.text : this.config.defaultText;
		text.style.color = safeColor(data.color || this.config.textColor, '#000000');
		text.style.fontFamily = data.fontFamily || this.config.fontFamily;
		text.style.fontWeight = data.fontWeight || this.config.fontWeight || '700';
		text.style.fontSize = Number(layer.dataset.fontSize) + 'px';
		text.style.lineHeight = String(data.lineHeight || this.config.lineHeight || 1.45);
		text.style.textAlign = data.textAlign || this.config.textAlign || 'right';

		resize.className = 'mpro-tc__layer-handle';
		resize.setAttribute('role', 'button');
		resize.setAttribute('aria-label', 'Resize text');
		resize.tabIndex = 0;

		rotate.className = 'mpro-tc__layer-rotate';
		rotate.setAttribute('role', 'button');
		rotate.setAttribute('aria-label', 'Rotate text');
		rotate.tabIndex = 0;

		remove.className = 'mpro-tc__layer-delete';
		remove.type = 'button';
		remove.setAttribute('aria-label', 'Delete text layer');
		remove.textContent = '×';

		layer.appendChild(text);
		layer.appendChild(resize);
		layer.appendChild(rotate);
		layer.appendChild(remove);
		this.stage.appendChild(layer);
		this.layers.push(layer);

		layer.addEventListener('pointerdown', this.onLayerPointerDown.bind(this, layer));
		layer.addEventListener('pointermove', this.onLayerPointerMove.bind(this, layer));
		layer.addEventListener('pointerup', this.onLayerPointerUp.bind(this, layer));
		layer.addEventListener('pointercancel', this.onLayerPointerUp.bind(this, layer));
		remove.addEventListener('click', this.removeLayer.bind(this, layer));
		resize.addEventListener('pointerdown', this.onResizePointerDown.bind(this, layer));
		resize.addEventListener('keydown', this.onResizeKeyDown.bind(this, layer));
		rotate.addEventListener('pointerdown', this.onRotatePointerDown.bind(this, layer));
		rotate.addEventListener('keydown', this.onRotateKeyDown.bind(this, layer));
		text.addEventListener('paste', this.onPastePlainText.bind(this));
		text.addEventListener('input', this.onTextInput.bind(this, layer));
		text.addEventListener('blur', this.onTextBlur.bind(this, layer));

		this.selectLayer(layer);
		this.updateToolbar();
		if (editImmediately) this.enterEdit(layer);
		return layer;
	};

	MPROTextCanvas.prototype.onPastePlainText = function (event) {
		event.preventDefault();
		var text = (event.clipboardData || window.clipboardData).getData('text/plain');
		document.execCommand('insertText', false, text);
	};

	MPROTextCanvas.prototype.onTextInput = function (layer) {
		if (textFromElement(layer.querySelector('.mpro-tc__text')).length > 1000) {
			var text = layer.querySelector('.mpro-tc__text');
			text.textContent = textFromElement(text).slice(0, 1000);
			setCaretToEnd(text);
		}
	};

	MPROTextCanvas.prototype.onTextBlur = function (layer) {
		if (layer.classList.contains('is-editing')) {
			this.exitEdit(layer);
		}
	};

	MPROTextCanvas.prototype.selectLayer = function (layer) {
		this.layers.forEach(function (item) { item.classList.remove('is-selected'); });
		this.selectedLayer = layer || null;
		if (layer) layer.classList.add('is-selected');
		this.updateToolbar();
	};

	MPROTextCanvas.prototype.enterEdit = function (layer) {
		if (!layer) return;
		this.exitEdit();
		this.selectLayer(layer);
		var text = layer.querySelector('.mpro-tc__text');
		layer.classList.add('is-editing');
		text.setAttribute('contenteditable', 'true');
		text.focus({ preventScroll: true });
		setCaretToEnd(text);
	};

	MPROTextCanvas.prototype.exitEdit = function (layer) {
		var target = layer || this.root.querySelector('.mpro-tc__layer.is-editing');
		if (!target) return;
		var text = target.querySelector('.mpro-tc__text');
		target.classList.remove('is-editing');
		text.setAttribute('contenteditable', 'false');
		if (document.activeElement === text) text.blur();
	};

	MPROTextCanvas.prototype.onLayerPointerDown = function (layer, event) {
		if (event.target.closest('.mpro-tc__layer-delete') || event.target.closest('.mpro-tc__layer-handle')) return;
		if (layer.classList.contains('is-editing')) return;

		this.selectLayer(layer);
		this.activePointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
		try { layer.setPointerCapture(event.pointerId); } catch (error) {}

		if (this.activePointers.size === 1) {
			this.gesture = {
				type: 'pending',
				pointerId: event.pointerId,
				startX: event.clientX,
				startY: event.clientY,
				layerX: Number(layer.dataset.x),
				layerY: Number(layer.dataset.y),
				moved: false
			};
		} else if (this.activePointers.size === 2) {
			var points = Array.from(this.activePointers.values());
			this.gesture = {
				type: 'pinch',
				startDistance: Math.max(1, distance(points[0], points[1])),
				startFontSize: Number(layer.dataset.fontSize),
				startAngle: angle(points[0], points[1]),
				startRotation: Number(layer.dataset.rotation || 0)
			};
		}
		event.preventDefault();
	};

	MPROTextCanvas.prototype.onLayerPointerMove = function (layer, event) {
		if (!this.activePointers.has(event.pointerId) || !this.gesture) return;
		this.activePointers.set(event.pointerId, { x: event.clientX, y: event.clientY });

		if (this.activePointers.size >= 2 && this.gesture.type === 'pinch') {
			var points = Array.from(this.activePointers.values()).slice(0, 2);
			var ratio = distance(points[0], points[1]) / this.gesture.startDistance;
			this.setLayerFontSize(layer, this.gesture.startFontSize * ratio);
			this.setLayerRotation(layer, this.gesture.startRotation + angle(points[0], points[1]) - this.gesture.startAngle);
			event.preventDefault();
			return;
		}

		if (this.gesture.pointerId !== event.pointerId) return;
		var dx = event.clientX - this.gesture.startX;
		var dy = event.clientY - this.gesture.startY;
		if (!this.gesture.moved && Math.sqrt(dx * dx + dy * dy) > 4) {
			this.gesture.moved = true;
			this.gesture.type = 'drag';
		}
		if (this.gesture.type === 'drag') {
			var rect = this.stage.getBoundingClientRect();
			var x = this.gesture.layerX + dx / Math.max(1, rect.width);
			var y = this.gesture.layerY + dy / Math.max(1, rect.height);
			this.setLayerPosition(layer, x, y);
			event.preventDefault();
		}
	};

	MPROTextCanvas.prototype.onLayerPointerUp = function (layer, event) {
		if (!this.activePointers.has(event.pointerId)) return;
		var shouldEdit = this.gesture && this.gesture.type === 'pending' && !this.gesture.moved && this.activePointers.size === 1;
		this.activePointers.delete(event.pointerId);
		try { layer.releasePointerCapture(event.pointerId); } catch (error) {}
		if (this.activePointers.size === 0) {
			this.gesture = null;
			if (shouldEdit) this.enterEdit(layer);
		} else if (this.activePointers.size === 1) {
			var remaining = Array.from(this.activePointers.entries())[0];
			this.gesture = {
				type: 'pending',
				pointerId: remaining[0],
				startX: remaining[1].x,
				startY: remaining[1].y,
				layerX: Number(layer.dataset.x),
				layerY: Number(layer.dataset.y),
				moved: true
			};
		}
	};

	MPROTextCanvas.prototype.onResizePointerDown = function (layer, event) {
		event.preventDefault();
		event.stopPropagation();
		this.selectLayer(layer);
		var handle = event.currentTarget;
		var startX = event.clientX;
		var startY = event.clientY;
		var startSize = Number(layer.dataset.fontSize);
		try { handle.setPointerCapture(event.pointerId); } catch (error) {}

		var onMove = function (moveEvent) {
			var delta = ((moveEvent.clientX - startX) + (moveEvent.clientY - startY)) / 2;
			this.setLayerFontSize(layer, startSize + delta * 0.35);
		}.bind(this);
		var onEnd = function (endEvent) {
			handle.removeEventListener('pointermove', onMove);
			handle.removeEventListener('pointerup', onEnd);
			handle.removeEventListener('pointercancel', onEnd);
			try { handle.releasePointerCapture(endEvent.pointerId); } catch (error) {}
		};
		handle.addEventListener('pointermove', onMove);
		handle.addEventListener('pointerup', onEnd);
		handle.addEventListener('pointercancel', onEnd);
	};

	MPROTextCanvas.prototype.onResizeKeyDown = function (layer, event) {
		if (event.key !== 'ArrowUp' && event.key !== 'ArrowRight' && event.key !== 'ArrowDown' && event.key !== 'ArrowLeft') return;
		event.preventDefault();
		var direction = (event.key === 'ArrowUp' || event.key === 'ArrowRight') ? 1 : -1;
		this.setLayerFontSize(layer, Number(layer.dataset.fontSize) + direction * (event.shiftKey ? 5 : 1));
	};

	MPROTextCanvas.prototype.onRotatePointerDown = function (layer, event) {
		event.preventDefault();
		event.stopPropagation();
		this.selectLayer(layer);
		var handle = event.currentTarget;
		var rect = layer.getBoundingClientRect();
		var center = { x: rect.left + rect.width / 2, y: rect.top + rect.height / 2 };
		var startAngle = angle(center, { x: event.clientX, y: event.clientY });
		var startRotation = Number(layer.dataset.rotation || 0);
		try { handle.setPointerCapture(event.pointerId); } catch (error) {}

		var onMove = function (moveEvent) {
			var nextAngle = angle(center, { x: moveEvent.clientX, y: moveEvent.clientY });
			this.setLayerRotation(layer, startRotation + nextAngle - startAngle);
		}.bind(this);
		var onEnd = function (endEvent) {
			handle.removeEventListener('pointermove', onMove);
			handle.removeEventListener('pointerup', onEnd);
			handle.removeEventListener('pointercancel', onEnd);
			try { handle.releasePointerCapture(endEvent.pointerId); } catch (error) {}
		};
		handle.addEventListener('pointermove', onMove);
		handle.addEventListener('pointerup', onEnd);
		handle.addEventListener('pointercancel', onEnd);
	};

	MPROTextCanvas.prototype.onRotateKeyDown = function (layer, event) {
		if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
		event.preventDefault();
		var direction = event.key === 'ArrowRight' ? 1 : -1;
		this.setLayerRotation(layer, Number(layer.dataset.rotation || 0) + direction * (event.shiftKey ? 15 : 1));
	};

	MPROTextCanvas.prototype.setLayerPosition = function (layer, x, y) {
		x = clamp(x, 0.02, 0.98);
		y = clamp(y, 0.02, 0.98);
		layer.dataset.x = String(x);
		layer.dataset.y = String(y);
		layer.style.left = (x * 100) + '%';
		layer.style.top = (y * 100) + '%';
	};

	MPROTextCanvas.prototype.setLayerFontSize = function (layer, size) {
		size = clamp(Number(size) || 10, 10, 200);
		layer.dataset.fontSize = String(size);
		layer.querySelector('.mpro-tc__text').style.fontSize = size + 'px';
	};

	MPROTextCanvas.prototype.setLayerRotation = function (layer, rotation) {
		rotation = normalizeAngle(rotation);
		layer.dataset.rotation = String(rotation);
		layer.style.transform = 'translate(-50%, -50%) rotate(' + rotation + 'deg)';
	};

	MPROTextCanvas.prototype.removeLayer = function (layer, event) {
		if (event) {
			event.preventDefault();
			event.stopPropagation();
		}
		var index = this.layers.indexOf(layer);
		if (index === -1) return;
		this.layers.splice(index, 1);
		layer.remove();
		this.selectedLayer = this.layers.length ? this.layers[this.layers.length - 1] : null;
		if (this.selectedLayer) this.selectLayer(this.selectedLayer);
		this.updateToolbar();
		this.announce('Text layer removed.');
	};

	MPROTextCanvas.prototype.bindToolbar = function () {
		var self = this;
		var menuToggle = this.root.querySelector('[data-mpro-menu-toggle]');
		var menu = this.root.querySelector('[data-mpro-menu]');
		if (menuToggle && menu) {
			menuToggle.addEventListener('click', function (event) {
				event.stopPropagation();
				var willOpen = menu.hidden;
				menu.hidden = !willOpen;
				menuToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
			});
		}
		this.root.querySelectorAll('[data-mpro-action]').forEach(function (button) {
			button.addEventListener('click', function () {
				var action = button.getAttribute('data-mpro-action');
				if (action === 'reset') self.reset();
				if (action === 'add') self.addText();
				if (action === 'export-transparent') self.exportCanvas(false);
				if (action === 'export-background') self.exportCanvas(true);
				if (menu) menu.hidden = true;
				if (menuToggle) menuToggle.setAttribute('aria-expanded', 'false');
			});
		});
		this.root.querySelectorAll('[data-mpro-tool]').forEach(function (button) {
			button.addEventListener('click', function () {
				var tool = button.getAttribute('data-mpro-tool');
				if (tool === 'font') self.openFontModal(button);
				if (tool === 'text-color') self.openColorModal('text', button);
				if (tool === 'background') self.openColorModal('background', button);
			});
		});
	};

	MPROTextCanvas.prototype.addText = function () {
		if (this.layers.length >= Number(this.config.maxLayers || 3)) {
			this.announce((window.mproTextCanvasGlobal && window.mproTextCanvasGlobal.i18n.maxLayers) || 'Maximum three text layers.');
			return;
		}
		this.exitEdit();
		this.createLayer({
			text: this.config.addedText || this.config.defaultText,
			color: this.initialTextColor,
			fontFamily: this.config.fontFamily,
			fontWeight: this.config.fontWeight,
			lineHeight: this.config.lineHeight,
			textAlign: this.config.textAlign
		}, true);
		this.announce('Text layer added.');
	};

	MPROTextCanvas.prototype.updateToolbar = function () {
		var bg = getComputedStyle(this.frame).backgroundColor;
		var bgSwatch = this.root.querySelector('.mpro-tc__tool--background .mpro-tc__swatch');
		if (bgSwatch) bgSwatch.style.backgroundColor = bg;

		var selected = this.selectedLayer || this.layers[this.layers.length - 1];
		var color = selected ? getComputedStyle(selected.querySelector('.mpro-tc__text')).color : this.config.textColor;
		var textSwatch = this.root.querySelector('.mpro-tc__tool--text .mpro-tc__swatch');
		if (textSwatch) textSwatch.style.backgroundColor = color;

		var add = this.root.querySelector('[data-mpro-action="add"]');
		if (add) {
			var disabled = this.layers.length >= Number(this.config.maxLayers || 3);
			add.disabled = disabled;
			add.setAttribute('aria-disabled', disabled ? 'true' : 'false');
		}
	};

	MPROTextCanvas.prototype.bindModal = function () {
		var self = this;
		this.modal.querySelectorAll('[data-mpro-modal-close]').forEach(function (button) {
			button.addEventListener('click', function () { self.closeModal(); });
		});
	};

	MPROTextCanvas.prototype.openModal = function (title, body, trigger) {
		this.lastFocusedBeforeModal = trigger || document.activeElement;
		this.modalTitle.textContent = title;
		this.modalBody.innerHTML = '';
		this.modalBody.appendChild(body);
		this.modal.hidden = false;
		this.modal.setAttribute('aria-hidden', 'false');
		document.documentElement.style.overflow = 'hidden';
		var focusable = this.modal.querySelector('button, input, select, textarea, [tabindex]:not([tabindex="-1"])');
		if (focusable) focusable.focus();
	};

	MPROTextCanvas.prototype.closeModal = function () {
		this.modal.hidden = true;
		this.modal.setAttribute('aria-hidden', 'true');
		document.documentElement.style.overflow = '';
		if (this.lastFocusedBeforeModal && this.lastFocusedBeforeModal.focus) this.lastFocusedBeforeModal.focus();
	};

	MPROTextCanvas.prototype.openFontModal = function (trigger) {
		var self = this;
		var selected = this.selectedLayer || this.layers[this.layers.length - 1];
		if (!selected) return;
		var current = getComputedStyle(selected.querySelector('.mpro-tc__text')).fontFamily;
		var list = document.createElement('div');
		list.className = 'mpro-tc__font-list';
		var fonts = (this.config.fonts && this.config.fonts.length) ? this.config.fonts : ((window.mproTextCanvasGlobal && window.mproTextCanvasGlobal.fonts) || []);
		fonts.forEach(function (font) {
			var button = document.createElement('button');
			button.type = 'button';
			button.className = 'mpro-tc__font-option';
			button.textContent = font.label || font.family;
			button.style.fontFamily = font.family;
			if (current.indexOf(String(font.family).replace(/["']/g, '').split(',')[0]) !== -1) button.classList.add('is-active');
			button.addEventListener('click', function () {
				selected.querySelector('.mpro-tc__text').style.fontFamily = font.family;
				self.closeModal();
				self.announce('Font changed.');
			});
			list.appendChild(button);
		});
		var title = (window.mproTextCanvasGlobal && window.mproTextCanvasGlobal.i18n.fontTitle) || 'Choose a font';
		this.openModal(title, list, trigger);
	};

	MPROTextCanvas.prototype.openColorModal = function (mode, trigger) {
		var self = this;
		var selected = this.selectedLayer || this.layers[this.layers.length - 1];
		if (mode === 'text' && !selected) return;
		var current = mode === 'background'
			? getComputedStyle(this.frame).backgroundColor
			: getComputedStyle(selected.querySelector('.mpro-tc__text')).color;
		var value = rgbToHex(current);
		var wrap = document.createElement('div');
		wrap.className = 'mpro-tc__color-editor';
		var main = document.createElement('div');
		main.className = 'mpro-tc__color-main';
		var picker = document.createElement('input');
		picker.type = 'color';
		picker.value = value;
		picker.setAttribute('aria-label', 'Color picker');
		var hex = document.createElement('input');
		hex.type = 'text';
		hex.value = value;
		hex.setAttribute('aria-label', 'Hex color');
		main.appendChild(picker);
		main.appendChild(hex);
		wrap.appendChild(main);

		picker.addEventListener('input', function () { hex.value = picker.value; });
		hex.addEventListener('input', function () {
			if (/^#[0-9a-f]{6}$/i.test(hex.value)) picker.value = hex.value;
		});

		var actions = document.createElement('div');
		actions.className = 'mpro-tc__modal-actions';
		var cancel = document.createElement('button');
		cancel.type = 'button';
		cancel.className = 'mpro-tc__modal-button mpro-tc__modal-button--secondary';
		cancel.textContent = (window.mproTextCanvasGlobal && window.mproTextCanvasGlobal.i18n.cancel) || 'Cancel';
		cancel.addEventListener('click', function () { self.closeModal(); });
		var apply = document.createElement('button');
		apply.type = 'button';
		apply.className = 'mpro-tc__modal-button';
		apply.textContent = (window.mproTextCanvasGlobal && window.mproTextCanvasGlobal.i18n.apply) || 'Apply';
		apply.addEventListener('click', function () {
			var color = safeColor(hex.value, picker.value);
			if (mode === 'background') {
				self.frame.style.backgroundColor = color;
			} else {
				selected.querySelector('.mpro-tc__text').style.color = color;
			}
			self.updateToolbar();
			self.closeModal();
			self.announce('Color changed.');
		});
		actions.appendChild(cancel);
		actions.appendChild(apply);
		wrap.appendChild(actions);

		var i18n = (window.mproTextCanvasGlobal && window.mproTextCanvasGlobal.i18n) || {};
		this.openModal(mode === 'background' ? (i18n.backgroundTitle || 'Background color') : (i18n.textColorTitle || 'Text color'), wrap, trigger);
	};

	MPROTextCanvas.prototype.destroy = function () {
		document.removeEventListener('pointerdown', this.boundDocumentPointerDown, true);
		document.removeEventListener('keydown', this.boundDocumentKeyDown);
		this.activePointers.clear();
		this.gesture = null;
	};

	MPROTextCanvas.prototype.onDocumentPointerDown = function (event) {
		if (!this.root.isConnected) {
			this.destroy();
			return;
		}
		var editing = this.root.querySelector('.mpro-tc__layer.is-editing');
		if (editing && !editing.contains(event.target)) this.exitEdit(editing);
		if (!this.root.contains(event.target) && !this.modal.contains(event.target)) {
			this.layers.forEach(function (layer) { layer.classList.remove('is-selected'); });
			this.selectedLayer = null;
			this.updateToolbar();
		}
		var menu = this.root.querySelector('[data-mpro-menu]');
		var toggle = this.root.querySelector('[data-mpro-menu-toggle]');
		if (menu && !menu.hidden && !menu.contains(event.target) && event.target !== toggle && !toggle.contains(event.target)) {
			menu.hidden = true;
			toggle.setAttribute('aria-expanded', 'false');
		}
	};

	MPROTextCanvas.prototype.onDocumentKeyDown = function (event) {
		if (!this.root.isConnected) {
			this.destroy();
			return;
		}
		if (!this.modal.hidden && event.key === 'Escape') {
			event.preventDefault();
			this.closeModal();
			return;
		}
		if (!this.root.contains(document.activeElement) && !this.selectedLayer) return;
		if (event.key === 'Escape') {
			this.exitEdit();
			if (this.selectedLayer) this.selectedLayer.classList.remove('is-selected');
			this.selectedLayer = null;
			return;
		}
		if ((event.key === 'Delete' || event.key === 'Backspace') && this.selectedLayer && !this.selectedLayer.classList.contains('is-editing')) {
			event.preventDefault();
			this.removeLayer(this.selectedLayer);
		}
	};

	MPROTextCanvas.prototype.wrapText = function (context, text, maxWidth) {
		var lines = [];
		String(text || '').split(/\n/).forEach(function (paragraph) {
			if (paragraph === '') {
				lines.push('');
				return;
			}
			var words = paragraph.split(/\s+/);
			var line = '';
			words.forEach(function (word) {
				var candidate = line ? line + ' ' + word : word;
				if (context.measureText(candidate).width <= maxWidth || !line) {
					line = candidate;
					if (context.measureText(line).width > maxWidth && word.length > 1) {
						line = '';
						var chunk = '';
						Array.from(word).forEach(function (char) {
							var next = chunk + char;
							if (context.measureText(next).width > maxWidth && chunk) {
								lines.push(chunk);
								chunk = char;
							} else {
								chunk = next;
							}
						});
						line = chunk;
					}
				} else {
					lines.push(line);
					line = word;
				}
			});
			if (line || !lines.length) lines.push(line);
		});
		return lines;
	};

	MPROTextCanvas.prototype.drawTextWithSpacing = function (context, text, x, y, letterSpacing, align) {
		if (!letterSpacing || Math.abs(letterSpacing) < 0.01) {
			context.fillText(text, x, y);
			return;
		}
		var chars = Array.from(text);
		var widths = chars.map(function (char) { return context.measureText(char).width; });
		var total = widths.reduce(function (sum, width) { return sum + width; }, 0) + Math.max(0, chars.length - 1) * letterSpacing;
		var cursor = x;
		if (align === 'center') cursor -= total / 2;
		if (align === 'right' || align === 'end') cursor -= total;
		var originalAlign = context.textAlign;
		context.textAlign = 'left';
		chars.forEach(function (char, index) {
			context.fillText(char, cursor, y);
			cursor += widths[index] + letterSpacing;
		});
		context.textAlign = originalAlign;
	};

	MPROTextCanvas.prototype.exportCanvas = function (withBackground) {
		this.exitEdit();
		try {
			var frameRect = this.frame.getBoundingClientRect();
			var stageRect = this.stage.getBoundingClientRect();
			var width = Math.max(1, Math.round(frameRect.width));
			var height = Math.max(1, Math.round(frameRect.height));
			var canvas = document.createElement('canvas');
			canvas.width = width;
			canvas.height = height;
			var context = canvas.getContext('2d');
			if (!context) throw new Error('Canvas is not supported.');

			if (withBackground) {
				context.fillStyle = getComputedStyle(this.frame).backgroundColor || this.config.background;
				context.fillRect(0, 0, width, height);
			}

			this.layers.forEach(function (layer) {
				var textElement = layer.querySelector('.mpro-tc__text');
				var value = textFromElement(textElement);
				if (!value) return;
				var style = getComputedStyle(textElement);
				var fontSize = parseFloat(style.fontSize) || 16;
				var lineHeight = parseFloat(style.lineHeight);
				if (!lineHeight || isNaN(lineHeight)) lineHeight = fontSize * 1.2;
				var fontStyle = style.fontStyle || 'normal';
				var fontWeight = style.fontWeight || '400';
				var fontFamily = style.fontFamily || 'sans-serif';
				var align = style.textAlign || 'left';
				var direction = style.direction || 'ltr';
				var letterSpacing = parseFloat(style.letterSpacing) || 0;

				context.save();
				context.font = fontStyle + ' ' + fontWeight + ' ' + fontSize + 'px ' + fontFamily;
				context.fillStyle = style.color || '#000000';
				context.textBaseline = 'top';
				context.textAlign = align;
				if ('direction' in context) context.direction = direction;

				var maxWidth = Math.max(1, textElement.offsetWidth);
				var layerCenterX = stageRect.left - frameRect.left + Number(layer.dataset.x) * stageRect.width;
				var layerCenterY = stageRect.top - frameRect.top + Number(layer.dataset.y) * stageRect.height;
				var textHeight = Math.max(lineHeight, textElement.offsetHeight);
				context.translate(layerCenterX, layerCenterY);
				context.rotate(Number(layer.dataset.rotation || 0) * Math.PI / 180);
				var left = -maxWidth / 2;
				var top = -textHeight / 2;
				var x = left;
				if (align === 'center') x = 0;
				if (align === 'right' || align === 'end') x = maxWidth / 2;
				var lines = this.wrapText(context, value, maxWidth);
				lines.forEach(function (line, index) {
					this.drawTextWithSpacing(context, line, x, top + index * lineHeight, letterSpacing, align);
				}.bind(this));
				context.restore();
			}.bind(this));

			var extension = withBackground ? 'jpg' : 'png';
			var mime = withBackground ? 'image/jpeg' : 'image/png';
			var quality = withBackground ? 0.94 : undefined;
			var stamp = new Date().toISOString().replace(/[:.]/g, '-');
			var filename = 'mpro-text-canvas-' + stamp + '.' + extension;
			var link = document.createElement('a');
			link.download = filename;
			link.href = canvas.toDataURL(mime, quality);
			link.rel = 'noopener';
			document.body.appendChild(link);
			link.click();
			link.remove();
			this.announce((withBackground ? 'JPG' : 'PNG') + ' exported at ' + width + ' × ' + height + ' pixels.');
		} catch (error) {
			this.announce('Export failed. ' + (error && error.message ? error.message : ''));
			if (window.console) console.error('MPRO Text Canvas export failed:', error);
		}
	};

	MPROTextCanvas.prototype.announce = function (message) {
		if (!this.status) return;
		this.status.textContent = '';
		window.setTimeout(function () { this.status.textContent = message; }.bind(this), 20);
	};

	function initScope(scope) {
		var container = scope && scope.querySelectorAll ? scope : document;
		var roots = [];
		if (container.matches && container.matches(SELECTOR)) roots.push(container);
		container.querySelectorAll(SELECTOR).forEach(function (root) { roots.push(root); });
		roots.forEach(function (root) {
			if (root.dataset.mproInitialized === 'true') return;
			new MPROTextCanvas(root);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { initScope(document); });
	} else {
		initScope(document);
	}

	window.addEventListener('elementor/frontend/init', function () {
		if (!window.elementorFrontend || !window.elementorFrontend.hooks) return;
		window.elementorFrontend.hooks.addAction('frontend/element_ready/mpro-text-canvas.default', function ($scope) {
			var node = $scope && $scope[0] ? $scope[0] : $scope;
			initScope(node);
		});
	});
})();
