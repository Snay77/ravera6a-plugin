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
};

require_once plugin_dir_path(__FILE__) . 'inc/ravera6aNewsPostType.php';
require_once plugin_dir_path(__FILE__) . 'inc/ravera6aTripsPostType.php';
require_once plugin_dir_path(__FILE__) . 'inc/ravera6aBoursesPostType.php';

// require_once plugin_dir_path(__FILE__) . 'inc/ravera6aNewsTaxonomy.php';
// require_once plugin_dir_path(__FILE__) . 'inc/ravera6aTripsTaxonomy.php';
// require_once plugin_dir_path(__FILE__) . 'inc/ravera6aBoursesTaxonomy.php';

use Ravera6a\ravera6aNewsPostType;
use Ravera6a\ravera6aTripsPostType;
use Ravera6a\ravera6aBoursesPostType;

// use Ravera6a\ravera6aNewsTaxonomy;
// use Ravera6a\ravera6aTripsTaxonomy;
// use Ravera6a\ravera6aBoursesTaxonomy;

(new ravera6aNewsPostType())->register();
(new ravera6aTripsPostType())->register();
(new ravera6aBoursesPostType())->register();

// (new ravera6aNewsTaxonomy())->register();
// (new ravera6aTripsTaxonomy())->register();
// (new ravera6aBoursesTaxonomy())->register();