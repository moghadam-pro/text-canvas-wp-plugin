(function ($) {
	'use strict';

	function escapeHtml(value) {
		return String(value).replace(/[&<>'"]/g, function (char) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char];
		});
	}

	function googleRow(index) {
		return '<div class="mpro-tc-font-row" data-font-row>' +
			'<label><span>Family</span><input type="text" list="mpro-tc-google-font-list" name="mpro_tc_settings[google_fonts][' + index + '][family]" value="" placeholder="Inter"></label>' +
			'<label><span>Weights</span><input type="text" name="mpro_tc_settings[google_fonts][' + index + '][weights]" value="400;700" placeholder="400;700"></label>' +
			'<button type="button" class="button-link-delete" data-mpro-remove>Remove</button>' +
		'</div>';
	}

	function uploadedRow(index) {
		var weights = '';
		for (var weight = 100; weight <= 900; weight += 100) {
			weights += '<option value="' + weight + '"' + (weight === 400 ? ' selected' : '') + '>' + weight + '</option>';
		}
		return '<div class="mpro-tc-font-row mpro-tc-font-row--upload" data-font-row>' +
			'<label><span>Family</span><input type="text" name="mpro_tc_settings[uploaded_fonts][' + index + '][family]" value=""></label>' +
			'<label class="mpro-tc-font-url"><span>File URL</span><span class="mpro-tc-font-url__control"><input type="url" name="mpro_tc_settings[uploaded_fonts][' + index + '][url]" value=""><button type="button" class="button" data-mpro-media>Choose</button></span></label>' +
			'<label><span>Weight</span><select name="mpro_tc_settings[uploaded_fonts][' + index + '][weight]">' + weights + '</select></label>' +
			'<label><span>Style</span><select name="mpro_tc_settings[uploaded_fonts][' + index + '][style]"><option value="normal">Normal</option><option value="italic">Italic</option></select></label>' +
			'<label><span>Format</span><select name="mpro_tc_settings[uploaded_fonts][' + index + '][format]"><option value="woff2">woff2</option><option value="woff">woff</option><option value="truetype">truetype</option><option value="opentype">opentype</option></select></label>' +
			'<button type="button" class="button-link-delete" data-mpro-remove>Remove</button>' +
		'</div>';
	}

	$(document).on('click', '[data-mpro-add-google]', function () {
		var $list = $('[data-mpro-google-list]');
		var index = $list.find('[data-font-row]').length ? Date.now() : 0;
		$list.append(googleRow(index));
	});

	$(document).on('click', '[data-mpro-add-upload]', function () {
		var $list = $('[data-mpro-upload-list]');
		var index = $list.find('[data-font-row]').length ? Date.now() : 0;
		$list.append(uploadedRow(index));
	});

	$(document).on('click', '[data-mpro-remove]', function () {
		$(this).closest('[data-font-row]').remove();
	});

	$(document).on('click', '[data-mpro-media]', function () {
		var $button = $(this);
		var $input = $button.closest('.mpro-tc-font-url__control').find('input[type="url"]');
		var frame = wp.media({
			title: window.mproTCAdmin.mediaTitle || 'Choose a font file',
			button: { text: window.mproTCAdmin.mediaButton || 'Use this font' },
			multiple: false
		});
		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			$input.val(attachment.url).trigger('change');
			var filename = attachment.filename || attachment.url || '';
			var extension = filename.split('.').pop().toLowerCase();
			var format = extension === 'ttf' ? 'truetype' : (extension === 'otf' ? 'opentype' : extension);
			$button.closest('[data-font-row]').find('select[name$="[format]"]').val(format);
		});
		frame.open();
	});
})(jQuery);
