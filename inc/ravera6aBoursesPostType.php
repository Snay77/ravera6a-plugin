<?php
namespace Ravera6a;

class ravera6aBoursesPostType
{
    public const POST_TYPE = 'bourses';

    public function definePostType(): void
    {
        $labels = [
            'name' => __('Bourses', 'ravera6a'),
            'singular_name' => __('Bourse', 'ravera6a'),
            'add_new' => __('Ajouter une bourse', 'ravera6a'),
            'add_new_item' => __('Ajouter une nouvelle bourse', 'ravera6a'),
            'edit_item' => __('Modifier la bourse', 'ravera6a'),
            'new_item' => __('Nouvelle bourse', 'ravera6a'),
            'view_item' => __('Voir la bourse', 'ravera6a'),
            'search_items' => __('Rechercher des bourses', 'ravera6a'),
            'not_found' => __('Aucune bourse trouvée', 'ravera6a'),
            'not_found_in_trash' => __('Aucune bourse trouvée dans la corbeille', 'ravera6a'),
            'all_items' => __('Toutes les bourses', 'ravera6a'),
            'menu_name' => __('Bourses', 'ravera6a'),
            'name_admin_bar' => __('Bourse', 'ravera6a'),
        ];

        $args = [
            'labels' => $labels,
            'description' => __('Gérer les bourses.', 'ravera6a'),
            'public' => true,
            'rewrite' => ['slug' => self::POST_TYPE],
            'has_archive' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'menu_position' => 7,
            'menu_icon' => 'dashicons-awards',
            'supports' => ['title', 'editor', 'thumbnail'],
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    public function register(): void
    {
        add_action('init', [$this, 'definePostType']);
    }
}