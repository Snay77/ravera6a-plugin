<?php
namespace Ravera6a;

class ravera6aTripsTaxonomy
{
    public const TAXONOMY      = 'trips_type';
    public const SLUG          = 'type-sortie';

    public const YEAR_TAXONOMY = 'trips_year';
    public const YEAR_SLUG     = 'annee-sortie';

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

    public function defineYearTaxonomy(): void
    {
        $labels = [
            'name' => __('Années des sorties', 'ravera6a'),
            'singular_name' => __('Année de sortie', 'ravera6a'),
            'search_items' => __('Rechercher des années', 'ravera6a'),
            'all_items' => __('Toutes les années', 'ravera6a'),
            'edit_item' => __('Modifier l’année', 'ravera6a'),
            'update_item' => __('Mettre à jour l’année', 'ravera6a'),
            'add_new_item' => __('Ajouter une année', 'ravera6a'),
            'new_item_name' => __('Nom de la nouvelle année', 'ravera6a'),
            'menu_name' => __('Années', 'ravera6a'),
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'show_ui' => false,
            'show_admin_column' => true,
            'hierarchical' => false,
            'show_in_rest' => true,
            'query_var' => true,
            'rewrite' => ['slug' => self::YEAR_SLUG],
        ];

        register_taxonomy(self::YEAR_TAXONOMY, [ravera6aTripsPostType::POST_TYPE], $args);
    }

    public function syncTripYear(int $post_id, \WP_Post $post, bool $update): void
    {
        unset($update);

        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        if ($post->post_type !== ravera6aTripsPostType::POST_TYPE) {
            return;
        }

        $date = get_post_meta($post_id, 'date', true);

        if (!is_string($date) || $date === '') {
            wp_set_object_terms($post_id, [], self::YEAR_TAXONOMY, false);
            return;
        }

        $year = $this->extractYear($date);

        if ($year === null) {
            wp_set_object_terms($post_id, [], self::YEAR_TAXONOMY, false);
            return;
        }

        wp_set_object_terms($post_id, [(string) $year], self::YEAR_TAXONOMY, false);
    }

    private function extractYear(string $date): ?int
    {
        $date = trim($date);

        if (preg_match('/^(\d{4})-\d{2}-\d{2}$/', $date, $matches) === 1) {
            return (int) $matches[1];
        }

        if (preg_match('/^\d{2}\/\d{2}\/(\d{4})$/', $date, $matches) === 1) {
            return (int) $matches[1];
        }

        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return null;
        }

        return (int) date('Y', $timestamp);
    }

    public function register(): void
    {
        add_action('init', [$this, 'defineTaxonomy']);
        add_action('init', [$this, 'defineYearTaxonomy']);
        add_action('save_post_' . ravera6aTripsPostType::POST_TYPE, [$this, 'syncTripYear'], 20, 3);
    }
}