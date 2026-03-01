<?php
namespace Ravera6a;

class ravera6aNewsTaxonomy
{
    public const TAXONOMY = 'news_type';
    public const SLUG     = 'type-actualite';

    public function defineTaxonomy(): void
    {
        $labels = [
            'name' => __('Types d\'actualités', 'ravera6a'),
            'singular_name' => __('Type d\'actualité', 'ravera6a'),
            'search_items' => __('Rechercher des types d\'actualités', 'ravera6a'),
            'all_items' => __('Tous les types d\'actualités', 'ravera6a'),
            'parent_item' => __('Type d\'actualité parent', 'ravera6a'),
            'parent_item_colon' => __('Type d\'actualité parent :', 'ravera6a'),
            'edit_item' => __('Modifier le type d\'actualité', 'ravera6a'),
            'update_item' => __('Mettre à jour le type d\'actualité', 'ravera6a'),
            'add_new_item' => __('Ajouter un nouveau type d\'actualité', 'ravera6a'),
            'new_item_name' => __('Nom du nouveau type d\'actualité', 'ravera6a'),
            'menu_name' => __('Types d\'actualités', 'ravera6a'),
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

        register_taxonomy(self::TAXONOMY, [ravera6aNewsPostType::POST_TYPE], $args);
    }

    public function register(): void
    {
        add_action('init', [$this, 'defineTaxonomy']);
    }
}