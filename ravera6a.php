<?php
/*
 * Plugin Name: Ravera6a
 * Description: Gérer les articles de presses, de bourses, de sorties
 * Version: 1.0.0
 * Author: Ethan Barlet et Lou-Anne Biet
 * Text Domain: ravera6a
*/

if (!defined('ABSPATH')) {
    exit;
}

if ( ! function_exists('acf_add_local_field_group') ) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo __('Ravera6a nécessite le plugin Advanced Custom Fields (version gratuite) pour fonctionner.', 'ravera6a');
        echo '</p></div>';
    });
}

require_once plugin_dir_path(__FILE__) . 'inc/ravera6aNewsPostType.php';
require_once plugin_dir_path(__FILE__) . 'inc/ravera6aTripsPostType.php';
require_once plugin_dir_path(__FILE__) . 'inc/ravera6aBoursesPostType.php';

require_once plugin_dir_path(__FILE__) . 'inc/ravera6aFields.php';

require_once plugin_dir_path(__FILE__) . 'inc/ravera6aTripsTaxonomy.php';
// require_once plugin_dir_path(__FILE__) . 'inc/ravera6aNewsTaxonomy.php';
// require_once plugin_dir_path(__FILE__) . 'inc/ravera6aBoursesTaxonomy.php';

require_once plugin_dir_path(__FILE__) . 'inc/ravera6aTripsSecurity.php';
require_once plugin_dir_path(__FILE__) . 'inc/ravera6aTripsArchive.php';

require_once plugin_dir_path(__FILE__) . 'inc/ravera6aContactForm.php';

use Ravera6a\ravera6aNewsPostType;
use Ravera6a\ravera6aTripsPostType;
use Ravera6a\ravera6aBoursesPostType;

use Ravera6a\ravera6aFields;

use Ravera6a\ravera6aTripsTaxonomy;
// use Ravera6a\ravera6aNewsTaxonomy;
// use Ravera6a\ravera6aBoursesTaxonomy;

use Ravera6a\ravera6aTripsSecurity;
use Ravera6a\ravera6aTripsArchive;

use Ravera6a\ContactForm;

(new ravera6aNewsPostType())->register();
(new ravera6aTripsPostType())->register();
(new ravera6aBoursesPostType())->register();

(new ravera6aFields())->register();

(new ravera6aTripsTaxonomy())->register();
// (new ravera6aNewsTaxonomy())->register();
// (new ravera6aBoursesTaxonomy())->register();

(new ravera6aTripsSecurity())->register();
(new ravera6aTripsArchive())->register();

(new ContactForm())->register();

/**
 * Charge le prévisualiseur :
 * - sur les singles news / trips / bourses
 * - sur certaines pages statiques ciblées
 * - ou sur toute page contenant le block ravera/gallery
 */
function ravera6a_enqueue_lightbox_assets() {
    if (is_admin()) {
        return;
    }

    $should_load = false;

    if (is_singular(array('news', 'trips', 'bourses'))) {
        $should_load = true;
    } elseif (is_page(array(6951, 7365))) {
        $should_load = true;
    } elseif (is_singular()) {
        global $post;

        if ($post instanceof WP_Post && has_block('ravera/gallery', $post)) {
            $should_load = true;
        }
    }

    if (!$should_load) {
        return;
    }

    $plugin_url  = plugin_dir_url(__FILE__);
    $plugin_path = plugin_dir_path(__FILE__);

    wp_enqueue_style(
        'ravera6a-lightbox',
        $plugin_url . 'assets/css/ravera6a-lightbox.css',
        array(),
        file_exists($plugin_path . 'assets/css/ravera6a-lightbox.css')
            ? filemtime($plugin_path . 'assets/css/ravera6a-lightbox.css')
            : '1.0.0'
    );

    wp_enqueue_script(
        'ravera6a-lightbox',
        $plugin_url . 'assets/js/ravera6a-lightbox.js',
        array(),
        file_exists($plugin_path . 'assets/js/ravera6a-lightbox.js')
            ? filemtime($plugin_path . 'assets/js/ravera6a-lightbox.js')
            : '1.0.0',
        true
    );

    wp_add_inline_script(
        'ravera6a-lightbox',
        'window.ravera6aLightbox = ' . wp_json_encode(array(
            'enableAllPostContentImages' => is_singular(array('news', 'trips', 'bourses')),
        )) . ';',
        'before'
    );
}
add_action('wp_enqueue_scripts', 'ravera6a_enqueue_lightbox_assets');