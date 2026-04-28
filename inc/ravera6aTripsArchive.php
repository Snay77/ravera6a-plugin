<?php

namespace Ravera6a;

class ravera6aTripsArchive
{
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'registerAssets']);
        add_action('pre_get_posts', [$this, 'handleArchiveQuery']);
        add_shortcode('ravera_trips_year_filters', [$this, 'renderYearFilters']);
    }

    public function registerAssets(): void
    {
        wp_register_style(
            'ravera-trips-filters',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/trips-filters.css',
            [],
            filemtime(plugin_dir_path(dirname(__FILE__)) . 'assets/css/trips-filters.css')
        );
    }

    public function handleArchiveQuery($query): void
    {
        if (
            is_admin()
            || ! $query->is_main_query()
            || ! is_post_type_archive(ravera6aTripsPostType::POST_TYPE)
        ) {
            return;
        }

        $selected_year = isset($_GET['annee']) ? sanitize_text_field(wp_unslash($_GET['annee'])) : '';
        $default_year  = $this->getLatestTripYear();

        $year_to_use = $selected_year !== '' ? $selected_year : $default_year;

        $query->set('post_type', ravera6aTripsPostType::POST_TYPE);
        $query->set('posts_per_page', 6);
        $query->set('meta_key', 'date');
        $query->set('orderby', 'meta_value');
        $query->set('meta_type', 'DATE');
        $query->set('order', 'DESC');

        if ($year_to_use !== '') {
            $query->set('tax_query', [
                [
                    'taxonomy' => ravera6aTripsTaxonomy::YEAR_TAXONOMY,
                    'field'    => 'slug',
                    'terms'    => $year_to_use,
                ],
            ]);
        }
    }

    public function renderYearFilters(): string
    {
        if (! is_post_type_archive(ravera6aTripsPostType::POST_TYPE)) {
            return '';
        }

        wp_enqueue_style('ravera-trips-filters');

        $years = $this->getVisibleTripYears();

        if (empty($years)) {
            return '';
        }

        $selected_year = isset($_GET['annee']) ? sanitize_text_field(wp_unslash($_GET['annee'])) : '';
        $default_year  = $this->getLatestTripYear();
        $current_year  = $selected_year !== '' ? $selected_year : $default_year;

        $archive_url = get_post_type_archive_link(ravera6aTripsPostType::POST_TYPE);

        if (! $archive_url) {
            return '';
        }

        $output = '<div class="ravera-trips-filters">';

        foreach ($years as $year_slug => $year_name) {
            $is_active = $year_slug === $current_year;
            $url       = add_query_arg('annee', $year_slug, $archive_url);

            $classes = 'ravera-trips-filter';
            if ($is_active) {
                $classes .= ' is-active';
            }

            $output .= sprintf(
                '<a class="%1$s" href="%2$s">%3$s</a>',
                esc_attr($classes),
                esc_url($url),
                esc_html($year_name)
            );
        }

        $output .= '</div>';

        return $output;
    }

    private function getLatestTripYear(): string
    {
        $years = $this->getVisibleTripYears();

        if (empty($years)) {
            return '';
        }

        $slugs = array_keys($years);

        return (string) $slugs[0];
    }

    private function getVisibleTripYears(): array
    {
        $trips = get_posts([
            'post_type'      => ravera6aTripsPostType::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_key'       => 'date',
            'orderby'        => 'meta_value',
            'meta_type'      => 'DATE',
            'order'          => 'DESC',
            'meta_query'     => [
                [
                    'relation' => 'OR',
                    [
                        'key'     => ravera6aArchiveToggle::META_KEY,
                        'compare' => 'NOT EXISTS',
                    ],
                    [
                        'key'     => ravera6aArchiveToggle::META_KEY,
                        'value'   => '1',
                        'compare' => '!=',
                    ],
                ],
            ],
        ]);

        if (empty($trips)) {
            return [];
        }

        $years = [];

        foreach ($trips as $trip_id) {
            $terms = get_the_terms($trip_id, ravera6aTripsTaxonomy::YEAR_TAXONOMY);

            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }

            foreach ($terms as $term) {
                $years[$term->slug] = $term->name;
            }
        }

        krsort($years, SORT_NATURAL);

        return $years;
    }
}