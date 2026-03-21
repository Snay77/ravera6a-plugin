<?php

namespace Ravera6a;

class ravera6aFields
{
    public function defineFields(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_ravera6a_date',
            'title' => 'Informations',
            'fields' => [
                [
                    'key' => 'field_ravera6a_date',
                    'label' => 'Date',
                    'name' => 'date',
                    'aria-label' => '',
                    'type' => 'date_picker',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'display_format' => 'd/m/Y',
                    'return_format' => 'Y-m-d',
                    'first_day' => 1,
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => ravera6aNewsPostType::POST_TYPE,
                    ],
                ],
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => ravera6aTripsPostType::POST_TYPE,
                    ],
                ],
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => ravera6aBoursesPostType::POST_TYPE,
                    ],
                ],
            ],
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'description' => '',
            'show_in_rest' => 1,
        ]);
    }

    public function defineFieldsPostMeta(): void
    {
        $post_types = [
            ravera6aNewsPostType::POST_TYPE,
            ravera6aTripsPostType::POST_TYPE,
            ravera6aBoursesPostType::POST_TYPE,
        ];

        foreach ($post_types as $post_type) {
            register_post_meta($post_type, 'date', [
                'single' => true,
                'type' => 'string',
                'show_in_rest' => true,
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback' => static fn() => current_user_can('edit_posts'),
            ]);
        }
    }

    public function handleArchiveQuery(): void
    {
        add_action('pre_get_posts', function ($query) {
            if (
                is_admin()
                || ! $query->is_main_query()
                || ! is_post_type_archive(ravera6aBoursesPostType::POST_TYPE)
            ) {
                return;
            }

            $query->set('post_type', ravera6aBoursesPostType::POST_TYPE);
            $query->set('posts_per_page', 6);
            $query->set('meta_key', 'date');
            $query->set('orderby', 'meta_value');
            $query->set('meta_type', 'DATE');
            $query->set('order', 'DESC');
        });
    }
    
    public function register(): void
    {
        add_action('acf/init', [$this, 'defineFields']);
        add_action('init', [$this, 'defineFieldsPostMeta']);
        $this->handleArchiveQuery();
    }
}
