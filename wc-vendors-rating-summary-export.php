<?php
/**
 * Plugin Name: Rating Summary Export for WC Vendors
 * Description: Administrator-only, vendor-scoped CSV of fresh product rating counts and means using free WooCommerce and WC Vendors Marketplace.
 * Version: 1.0.0
 * Author: ClosedLoop AI / ClosedLoop Labs LLC
 * Author URI: https://closedloop.sh/
 * Requires at least: 7.0
 * Requires PHP: 8.3
 * Requires Plugins: woocommerce, wc-vendors
 * WC requires at least: 11.3
 * WC tested up to: 11.3
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wc-vendors-rating-summary-export
 *
 * Copyright (C) 2026 ClosedLoop Labs LLC.
 * This original program is free software: you may redistribute and/or modify it
 * under the GNU General Public License, version 3 or any later version.
 * It comes WITHOUT ANY WARRANTY; see LICENSE for the full terms.
 */

defined( 'ABSPATH' ) || exit;

final class CLAI_WCV_Rating_Summary_Export {
    const ACTION = 'clai_wcv_rating_summary_export';
    const CAPABILITY = 'manage_options';

    public static function register() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'download' ) );
        add_action( 'admin_post_nopriv_' . self::ACTION, array( __CLASS__, 'download' ) );
    }

    public static function menu() {
        add_management_page( 'Rating Summary Export', 'Rating Summary Export', self::CAPABILITY,
            'clai-wcv-rating-summary', array( __CLASS__, 'page' ) );
    }

    private static function require_administrator() {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            wp_die( esc_html__( 'Administrator permission is required.', 'wc-vendors-rating-summary-export' ),
                esc_html__( 'Access denied', 'wc-vendors-rating-summary-export' ), array( 'response' => 403 ) );
        }
    }

    private static function require_dependencies() {
        if ( ! class_exists( 'WCV_Vendors' ) || ! class_exists( 'WC_Comments' ) ||
             ! function_exists( 'wc_get_product' ) ||
             ! method_exists( 'WC_Comments', 'get_rating_counts_for_product' ) ) {
            wp_die( esc_html__( 'Activate free WooCommerce and WC Vendors Marketplace first.', 'wc-vendors-rating-summary-export' ),
                esc_html__( 'Dependencies unavailable', 'wc-vendors-rating-summary-export' ), array( 'response' => 503 ) );
        }
    }

    public static function page() {
        self::require_administrator();
        self::require_dependencies();
        echo '<div class="wrap"><h1>Rating Summary Export</h1>';
        echo '<p>Download one vendor’s published product rating summary. No subscription or external connection is used.</p>';
        echo '<p>Includes product IDs, titles, URLs, five rating counts, their total and mean. Excludes review text, reviewer identities, contact details and orders.</p>';
        echo '<form id="clai-wcv-ratings-export" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="' . esc_attr( self::ACTION ) . '">';
        wp_nonce_field( self::ACTION );
        echo '<p><label for="clai-wcv-vendor"><strong>Vendor</strong></label></p>';
        echo '<select id="clai-wcv-vendor" name="vendor_id" required><option value="">Choose a vendor</option>';
        // Resolve eligibility through the native API, including its role/owner filters.
        foreach ( get_users( array( 'fields' => 'ID', 'orderby' => 'ID', 'order' => 'ASC' ) ) as $id ) {
            $id = (int) $id;
            if ( WCV_Vendors::is_vendor( $id ) ) {
                $shop = WCV_Vendors::get_vendor_shop_name( $id );
                $name = is_scalar( $shop ) ? (string) $shop : '';
                echo '<option value="' . esc_attr( $id ) . '">' . esc_html( '#' . $id . ' — ' . $name ) . '</option>';
            }
        }
        echo '</select>';
        submit_button( 'Download rating summary CSV' );
        echo '</form><h2>How to read the file</h2>';
        echo '<p>Counts use fresh approved whole-number ratings from 1 to 5. They count rating entries, not unique people or orders. No ratings means an empty mean.</p>';
        echo '<p>Hidden, password-protected, draft and private products are excluded. Variations do not create duplicate parent rows. Cached storefront statistics are never repaired.</p>';
        echo '<p>The CSV still contains customer-derived aggregates. Keep downloads under your organisation’s data-handling policy. Spreadsheet-shaped text is prefixed with an apostrophe.</p></div>';
    }

    /** CSV quoting handles delimiters; this additionally neutralises formula-shaped text. */
    private static function csv_text( $value ) {
        $value = (string) $value;
        if ( preg_match( '/^[\t\r\n]|^\s*[=+\-@]/u', $value ) ) {
            return "'" . $value;
        }
        return $value;
    }

    private static function rating_summary( $product ) {
        $bins = array_fill( 1, 5, 0 );
        // This public WooCommerce method queries approved rating metadata without using or changing its cache.
        foreach ( WC_Comments::get_rating_counts_for_product( $product ) as $rating => $count ) {
            // Imported fractional/out-of-range metadata must never be coerced into a valid star bin.
            if ( preg_match( '/^[1-5]$/D', (string) $rating ) ) {
                $bins[ (int) $rating ] += max( 0, (int) $count );
            }
        }
        $count = array_sum( $bins );
        $sum = 0;
        foreach ( $bins as $rating => $quantity ) {
            $sum += $rating * $quantity;
        }
        return array( $bins, $count, $count ? number_format( $sum / $count, 2, '.', '' ) : '' );
    }

    public static function download() {
        self::require_administrator();
        if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
            wp_die( 'Use the protected download form.', 'Method not allowed', array( 'response' => 405 ) );
        }
        if ( ! isset( $_POST['_wpnonce'] ) || ! is_string( $_POST['_wpnonce'] ) ) {
            wp_die( 'A valid download nonce is required.', 'Access denied', array( 'response' => 403 ) );
        }
        check_admin_referer( self::ACTION );
        self::require_dependencies();
        $raw = isset( $_POST['vendor_id'] ) ? $_POST['vendor_id'] : null;
        $id = is_string( $raw ) ? filter_var( $raw, FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) ) : false;
        if ( false === $id || ! WCV_Vendors::is_vendor( $id ) ) {
            wp_die( 'Choose a valid WC Vendors vendor.', 'Invalid vendor', array( 'response' => 400 ) );
        }
        $stream = fopen( 'php://output', 'w' );
        if ( false === $stream ) {
            wp_die( 'The CSV stream could not be opened.', 'Download unavailable', array( 'response' => 500 ) );
        }
        nocache_headers();
        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="wc-vendors-rating-summary-vendor-' . $id . '.csv"' );
        fputcsv( $stream, array( 'vendor_id', 'product_id', 'product_title', 'product_url', 'rating_1', 'rating_2',
            'rating_3', 'rating_4', 'rating_5', 'rating_count', 'mean_rating' ), ',', '"', '' );
        $offset = 0;
        do {
            // Do not prefilter author: WC Vendors' ownership filter may map a different stored author.
            $products = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'fields' => 'ids',
                'posts_per_page' => 100, 'offset' => $offset, 'orderby' => 'ID', 'order' => 'ASC',
                'no_found_rows' => true, 'suppress_filters' => false ) );
            foreach ( $products as $product_id ) {
                if ( $id !== (int) WCV_Vendors::get_vendor_from_product( $product_id ) ||
                     '' !== get_post_field( 'post_password', $product_id ) ) {
                    continue;
                }
                $product = wc_get_product( $product_id );
                if ( ! $product || 'hidden' === $product->get_catalog_visibility() ) {
                    continue;
                }
                list( $bins, $count, $mean ) = self::rating_summary( $product );
                fputcsv( $stream, array( $id, $product_id, self::csv_text( $product->get_name( 'edit' ) ),
                    self::csv_text( get_permalink( $product_id ) ), $bins[1], $bins[2], $bins[3], $bins[4], $bins[5],
                    $count, $mean ), ',', '"', '' );
            }
            $offset += 100;
        } while ( count( $products ) === 100 );
        fclose( $stream );
        exit;
    }
}

CLAI_WCV_Rating_Summary_Export::register();
