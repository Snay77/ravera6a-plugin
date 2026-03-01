<?php
namespace Ravera6a;

class ravera6aBoursesTaxonomy
{
    public const TAXONOMY = 'bourses_type';
    public const SLUG     = 'type-bourse';

    public function defineTaxonomy(): void
    {
        $labels = [
            'name' => __('Types de bourses', 'ravera6a'),
            'singular_name' => __('Type de bourse', 'ravera6a'),
            'search_items' => __('Rechercher des types de bourses', 'ravera6a'),
            'all_items' => __('Tous les types de bourses', 'ravera6a'),
            'parent_item' => __('Type de bourse parent', 'ravera6a'),
            'parent_item_colon' => __('Type de bourse parent :', 'ravera6a'),
            'edit_item' => __('Modifier le type de bourse', 'ravera6a'),
            'update_item' => __('Mettre à jour le type de bourse', 'ravera6a'),
            'add_new_item' => __('Ajouter un nouveau type de bourse', 'ravera6a'),
            'new_item_name' => __('Nom du nouveau type de bourse', 'ravera6a'),
            'menu_name' => __('Types de bourses', 'ravera6a'),
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

        register_taxonomy(self::TAXONOMY, [ravera6aBoursesPostType::POST_TYPE], $args);
    }

    public function register(): void
    {
        add_action('init', [$this, 'defineTaxonomy']);
    }
}