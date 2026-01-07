<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Astra Mapping Layer (Commit 8.1)
 * Fixes:
 * - Astra Footer Builder selectors (primary footer wrap)
 * - Astra Woo Header Cart selectors (icon + total + svg)
 */

function hmde_print_astra_mapping_css() {
    if ( is_admin() ) {
        return;
    }

    if ( ! function_exists( 'hmde_current_group_body_selector' ) ) {
        return;
    }

    $selector = hmde_current_group_body_selector();
    if ( ! $selector ) {
        return;
    }

    $css = "
{$selector}{
  background: var(--hm-bg, inherit);
  font-family: var(--hm-font-body, inherit);
}

{$selector} h1,
{$selector} h2,
{$selector} h3,
{$selector} h4,
{$selector} h5,
{$selector} h6{
  font-family: var(--hm-font-heading, inherit);
  color: var(--hm-dark, inherit);
}

/* ==========================================
   PAGE TITLE / ENTRY HEADER FIX (Astra)
   Higher specificity for Astra title rules
   ========================================== */

{$selector} header.entry-header .entry-title,
{$selector} header.entry-header h1.entry-title,
{$selector} h1.entry-title,
{$selector} h1.ass.entry-title,
{$selector} .ast-single-post header.entry-header .entry-title,
{$selector} .ast-page-title,
{$selector} .page-title,
{$selector} .archive-title{
  color: var(--hm-dark, inherit);
}

/* Links */
{$selector} a{
  color: var(--hm-link, inherit);
}
{$selector} a:hover{
  opacity: .9;
}

/* Buttons + common controls */
{$selector} .button,
{$selector} button,
{$selector} input[type='button'],
{$selector} input[type='submit'],
{$selector} .wp-element-button,
{$selector} .ast-button,
{$selector} .ast-custom-button{
  background-color: var(--hm-primary, inherit);
  border-color: var(--hm-primary, inherit);
  color: #fff;
}
{$selector} .button:hover,
{$selector} button:hover,
{$selector} input[type='button']:hover,
{$selector} input[type='submit']:hover,
{$selector} .wp-element-button:hover,
{$selector} .ast-button:hover,
{$selector} .ast-custom-button:hover{
  filter: brightness(0.95);
}

/* ==========================================
   FOOTER FIX (Astra Footer Builder)
   Based on your working snippet selectors
   ========================================== */

/* Primary Footer Builder wrap + inner rows */
{$selector} .site-primary-footer-wrap[data-section='section-primary-footer-builder'],
{$selector} .site-primary-footer-wrap[data-section='section-primary-footer-builder'] .ast-builder-grid-row-container,
{$selector} .site-primary-footer-wrap[data-section='section-primary-footer-builder'] .ast-builder-grid-row{
  background-color: var(--hm-footer, inherit);
}

/* Make footer text readable */
{$selector} .site-primary-footer-wrap[data-section='section-primary-footer-builder'],
{$selector} .site-primary-footer-wrap[data-section='section-primary-footer-builder'] *{
  color: #fff;
}

/* Footer links (use hm-link but keep contrast) */
{$selector} .site-primary-footer-wrap[data-section='section-primary-footer-builder'] a{
  color: var(--hm-link, #93c5fd);
}
{$selector} .site-primary-footer-wrap[data-section='section-primary-footer-builder'] a:hover{
  color: #fff;
  text-decoration: underline;
}

/* Also cover Astra small footer / fallback footer containers */
{$selector} .site-footer,
{$selector} .ast-footer-wrap,
{$selector} .ast-small-footer,
{$selector} .ast-footer-overlay{
  background-color: var(--hm-footer, inherit);
}

/* ==========================================
   CART FIX (Astra Woo Header Cart)
   ========================================== */

/* Total price */
{$selector} .ast-woo-header-cart-total,
{$selector} .ast-woo-header-cart-total .woocommerce-Price-amount,
{$selector} .ast-woo-header-cart-total .woocommerce-Price-amount bdi,
{$selector} .ast-woo-header-cart-total .woocommerce-Price-currencySymbol{
  color: var(--hm-primary, inherit);
  font-weight: 600;
}

/* Icon as <i> */
{$selector} i.astra-icon.ast-icon-shopping-bag{
  color: var(--hm-primary, inherit);
}

/* Icon as SVG */
{$selector} .ast-site-header-cart svg,
{$selector} .ast-site-header-cart svg *{
  fill: var(--hm-primary, inherit);
}

/* Item count badge (real element variants) */
{$selector} .ast-site-header-cart .count,
{$selector} a.ast-cart-menu-wrap .count,
{$selector} .ast-site-header-cart-data .count{
  background-color: var(--hm-primary, inherit);
  color: #fff;
  border-radius: 999px;
  font-weight: 700;
  font-size: 12px;
  min-width: 18px;
  height: 18px;
  line-height: 18px;
  padding: 0 5px;
}

/* If theme uses pseudo badges (rare but seen) */
{$selector} i.astra-icon.ast-icon-shopping-bag::after,
{$selector} i.astra-icon.ast-icon-shopping-bag::before{
  background: var(--hm-primary, inherit);
  color: #fff;
  border-radius: 999px;
}

/* ==========================================
   MOBILE FIXES
   - Hamburger icon color (Astra SVG variants)
   - Prevent horizontal overflow (mobile width issue)
   ========================================== */

/* Prevent horizontal scroll / page widening (scoped) */
{$selector},
{$selector} #page,
{$selector} .site,
{$selector} .site-content,
{$selector} .ast-container{
  overflow-x: hidden;
  max-width: 100%;
}

/* Common overflow offenders */
{$selector} img,
{$selector} svg,
{$selector} iframe,
{$selector} video{
  max-width: 100%;
  height: auto;
}

/* Hamburger / Mobile menu trigger (Astra icon variants) */
{$selector} .ast-mobile-menu-trigger-minimal,
{$selector} .ast-mobile-menu-trigger-minimal button{
  color: var(--hm-primary, inherit);
}

/* If rendered as SVG iconset */
{$selector} .ast-mobile-menu-trigger-minimal svg,
{$selector} .ast-mobile-menu-trigger-minimal svg *{
  fill: var(--hm-primary, inherit);
  stroke: var(--hm-primary, inherit);
}

/* Some Astra builds use these */
{$selector} .ast-mobile-menu-trigger-minimal .ahfb-svg-iconset svg,
{$selector} .ast-mobile-menu-trigger-minimal .ahfb-svg-iconset svg *{
  fill: var(--hm-primary, inherit);
  stroke: var(--hm-primary, inherit);
}

/* Fallback: icon element */
{$selector} .ast-mobile-menu-trigger-minimal i,
{$selector} .ast-mobile-menu-trigger-minimal .astra-icon{
  color: var(--hm-primary, inherit);
}
";

    echo "\n<!-- HM Demo Engine (Commit 8.1) -->\n";
    echo "<style id=\"hmde-astra-mapping\">\n" . trim( $css ) . "\n</style>\n";
}
add_action( 'wp_head', 'hmde_print_astra_mapping_css', 40 );
