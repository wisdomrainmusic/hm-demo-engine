jQuery(document).ready(function ($) {
    // Init WP Color Picker (if fields exist)
    if ($.fn.wpColorPicker) {
        $('.hmde-color-field').wpColorPicker();
    }

    // Input mapping (adjust IDs if needed)
    var INPUT_MAP = {
        primary: '#hmde_primary',
        dark: '#hmde_dark',
        background: '#hmde_bg',
        footer: '#hmde_footer',
        link: '#hmde_link'
    };

    var STORAGE_KEY = 'hmde_selected_global_palette';

    function isValidHex(val) {
        if (!val) {
            return false;
        }
        val = val.toString().trim();
        return /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(val);
    }

    function setPickerValue($input, value) {
        if (!$input || !$input.length) {
            return;
        }

        value = (value || '').toString().trim();
        if (!isValidHex(value)) {
            return;
        }

        $input.val(value);

        if ($input.hasClass('wp-color-picker')) {
            try {
                $input.wpColorPicker('color', value);
            } catch (e) {
                $input.trigger('change');
            }
        } else {
            $input.trigger('change');
        }
    }

    function getInputValue(key) {
        if (!INPUT_MAP[key]) {
            return '';
        }
        var $input = $(INPUT_MAP[key]);
        if (!$input.length) {
            return '';
        }
        return ($input.val() || '').toString().trim();
    }

    function applySelectedState(paletteKey) {
        $('.hmde-palette-card').removeClass('is-selected');
        if (!paletteKey) {
            return;
        }
        $('.hmde-palette-card[data-palette="' + paletteKey + '"]').addClass('is-selected');
    }

    /**
     * Update current palette preview swatches
     */
    function updateCurrentPalettePreview() {
        $('.hmde-current-swatch').each(function () {
            var $swatch = $(this);
            var key = $swatch.data('color-key');
            var val = getInputValue(key);

            if (isValidHex(val)) {
                $swatch.css('background', val);
            } else {
                $swatch.css('background', '#e5e7eb');
            }
        });
    }

    // Initial preview
    updateCurrentPalettePreview();

    // Update preview on change (manual or programmatic)
    $(document).on('change', '.hmde-color-field', function () {
        updateCurrentPalettePreview();
    });

    /**
     * Palette click-to-apply (safe: only overwrites valid colors provided by palette)
     */
    $(document).on('click', '.hmde-palette-card', function (e) {
        e.preventDefault();

        var $card = $(this);
        var paletteKey = $card.attr('data-palette') || '';
        var raw = $card.attr('data-colors');

        if (!raw) {
            return;
        }

        var colors = {};
        try {
            colors = JSON.parse(raw);
        } catch (err) {
            return;
        }

        // Apply only valid palette colors; ignore missing/invalid keys so we don't wipe fields
        Object.keys(INPUT_MAP).forEach(function (key) {
            var val = colors[key];
            if (!isValidHex(val)) {
                return;
            }
            setPickerValue($(INPUT_MAP[key]), val);
        });

        // Selected state + pulse
        applySelectedState(paletteKey);
        $card.addClass('is-applied');
        window.setTimeout(function () {
            $card.removeClass('is-applied');
        }, 250);

        // Persist selection
        try {
            window.localStorage.setItem(STORAGE_KEY, paletteKey);
        } catch (err2) {}
    });

    /**
     * Restore selected palette state (visual only)
     */
    (function restoreSelectedPalette() {
        var saved = '';
        try {
            saved = window.localStorage.getItem(STORAGE_KEY) || '';
        } catch (err) {}

        if (saved) {
            applySelectedState(saved);
        }
    })();
});
