<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get global color palettes for Demo Engine presets.
 *
 * @return array
 */
function hmde_get_global_palettes() {
    $palettes = array(
        'lily'    => array(
            'label'  => 'Lily',
            'colors' => array(
                'primary'    => '#D4A24C',
                'dark'       => '#0B1F3A',
                'background' => '#FFF6E5',
                'footer'     => '#1A1A1A',
                'link'       => '#C28B2C',
            ),
        ),
        'style_2' => array(
            'label'  => 'Style 2',
            'colors' => array(
                'primary'    => '#2563EB',
                'dark'       => '#0F172A',
                'background' => '#EFF6FF',
                'footer'     => '#020617',
                'link'       => '#1D4ED8',
            ),
        ),
        'style_3' => array(
            'label'  => 'Style 3',
            'colors' => array(
                'primary'    => '#0284C7',
                'dark'       => '#0C4A6E',
                'background' => '#F0F9FF',
                'footer'     => '#082F49',
                'link'       => '#0369A1',
            ),
        ),
        'dark'    => array(
            'label'  => 'Dark',
            'colors' => array(
                'primary'    => '#0EA5E9',
                'dark'       => '#020617',
                'background' => '#020617',
                'footer'     => '#000000',
                'link'       => '#38BDF8',
            ),
        ),
    );

    /**
     * Allow themes/plugins to filter global palettes.
     */
    return apply_filters( 'hmde_global_palettes', $palettes );
}
