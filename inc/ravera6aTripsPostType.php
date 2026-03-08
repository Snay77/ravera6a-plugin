<?php
namespace Ravera6a;

class ravera6aTripsPostType
{
    public const POST_TYPE = 'trips';

    public function definePostType(): void
    {
        $labels = [
            'name' => __('Sorties', 'ravera6a'),
            'singular_name' => __('Sortie', 'ravera6a'),
            'add_new' => __('Ajouter une sortie', 'ravera6a'),
            'add_new_item' => __('Ajouter une nouvelle sortie', 'ravera6a'),
            'edit_item' => __('Modifier la sortie', 'ravera6a'),
            'new_item' => __('Nouvelle sortie', 'ravera6a'),
            'view_item' => __('Voir la sortie', 'ravera6a'),
            'search_items' => __('Rechercher des sorties', 'ravera6a'),
            'not_found' => __('Aucune sortie trouvée', 'ravera6a'),
            'not_found_in_trash' => __('Aucune sortie trouvée dans la corbeille', 'ravera6a'),
            'all_items' => __('Toutes les sorties', 'ravera6a'),
            'menu_name' => __('Sorties', 'ravera6a'),
            'name_admin_bar' => __('Sortie', 'ravera6a'),
        ];

        $args = [
            'labels' => $labels,
            'description' => __('Gérer les sorties.', 'ravera6a'),
            'public' => true,
            'rewrite' => ['slug' => self::POST_TYPE],
            'has_archive' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'menu_position' => 6,
            'menu_icon' => 'dashicons-location-alt',
            'supports' => ['title', 'editor', 'thumbnail'],
            'taxonomies' => [
                ravera6aTripsTaxonomy::TAXONOMY,
                ravera6aTripsTaxonomy::YEAR_TAXONOMY,
            ],
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    public function register(): void
    {
        add_action('init', [$this, 'definePostType']);
    }
}