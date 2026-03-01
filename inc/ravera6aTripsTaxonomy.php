<?php
namespace Ravera6a;

class ravera6aTripsTaxonomy
{
    public const TAXONOMY = 'trips_type';
    public const SLUG     = 'type-sortie';

    public function defineTaxonomy(): void
    {
        $labels = [
            'name' => __('Types de sorties', 'ravera6a'),
            'singular_name' => __('Type de sortie', 'ravera6a'),
            'search_items' => __('Rechercher des types de sorties', 'ravera6a'),
            'all_items' => __('Tous les types de sorties', 'ravera6a'),
            'parent_item' => __('Type de sortie parent', 'ravera6a'),
            'parent_item_colon' => __('Type de sortie parent :', 'ravera6a'),
            'edit_item' => __('Modifier le type de sortie', 'ravera6a'),
            'update_item' => __('Mettre à jour le type de sortie', 'ravera6a'),
            'add_new_item' => __('Ajouter un nouveau type de sortie', 'ravera6a'),
            'new_item_name' => __('Nom du nouveau type de sortie', 'ravera6a'),
            'menu_name' => __('Types de sorties', 'ravera6a'),
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'show_ui' => true,
            'hierarchical' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'query_var' => true,
            'rewrite' => ['slug' => self::SLUG],
        ];

        register_taxonomy(self::TAXONOMY, [ravera6aTripsPostType::POST_TYPE], $args);
    }

    public function register(): void
    {
        add_action('init', [$this, 'defineTaxonomy']);
    }
}