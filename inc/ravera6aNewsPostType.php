<?php
namespace Ravera6a;

class ravera6aNewsPostType
{
    public const POST_TYPE = 'actualites';

    public function definePostType(): void
    {
        $labels = [
            'name' => __('Actualités', 'ravera6a'),
            'singular_name' => __('Actualité', 'ravera6a'),
            'add_new' => __('Ajouter une actualité', 'ravera6a'),
            'add_new_item' => __('Ajouter une nouvelle actualité', 'ravera6a'),
            'edit_item' => __('Modifier l\'actualité', 'ravera6a'),
            'new_item' => __('Nouvelle actualité', 'ravera6a'),
            'view_item' => __('Voir l\'actualité', 'ravera6a'),
            'search_items' => __('Rechercher des actualités', 'ravera6a'),
            'not_found' => __('Aucune actualité trouvée', 'ravera6a'),
            'not_found_in_trash' => __('Aucune actualité trouvée dans la corbeille', 'ravera6a'),
            'all_items' => __('Toutes les actualités', 'ravera6a'),
            'menu_name' => __('Actualités', 'ravera6a'),
            'name_admin_bar' => __('Actualité', 'ravera6a'),
        ];

        $args = [
            'labels' => $labels,
            'description' => __('Gérer les actualités.', 'ravera6a'),
            'public' => true,
            'rewrite' => ['slug' => self::POST_TYPE],
            'has_archive' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-megaphone',
            'supports' => ['title', 'editor', 'thumbnail'],
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    public function register(): void
    {
        add_action('init', [$this, 'definePostType']);
    }
}