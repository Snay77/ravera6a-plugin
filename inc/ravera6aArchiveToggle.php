<?php

namespace Ravera6a;

class ravera6aArchiveToggle
{
    public const META_KEY = '_ravera6a_archived';

    private array $post_types = [
        ravera6aNewsPostType::POST_TYPE,
        ravera6aTripsPostType::POST_TYPE,
        ravera6aBoursesPostType::POST_TYPE,
    ];

    public function register(): void
    {
        add_action('init', [$this, 'registerMeta']);
        add_action('add_meta_boxes', [$this, 'addMetaBox']);
        add_action('save_post', [$this, 'saveArchivedState'], 10, 2);

        add_action('pre_get_posts', [$this, 'excludeArchivedFromFrontQueries']);

        foreach ($this->post_types as $post_type) {
            add_filter("manage_{$post_type}_posts_columns", [$this, 'addAdminColumn']);
            add_action("manage_{$post_type}_posts_custom_column", [$this, 'renderAdminColumn'], 10, 2);
        }

        add_action('quick_edit_custom_box', [$this, 'renderQuickEditField'], 10, 2);
        add_action('admin_footer-edit.php', [$this, 'printQuickEditScript']);
    }

    public function registerMeta(): void
    {
        foreach ($this->post_types as $post_type) {
            register_post_meta($post_type, self::META_KEY, [
                'type'              => 'boolean',
                'single'            => true,
                'default'           => false,
                'show_in_rest'      => true,
                'sanitize_callback' => function ($value) {
                    return (bool) $value;
                },
                'auth_callback'     => function ($allowed, $meta_key, $post_id) {
                    return current_user_can('edit_post', $post_id);
                },
            ]);
        }
    }

    public function addMetaBox(): void
    {
        foreach ($this->post_types as $post_type) {
            add_meta_box(
                'ravera6a_archive_toggle',
                __('Archivage', 'ravera6a'),
                [$this, 'renderMetaBox'],
                $post_type,
                'side',
                'high'
            );
        }
    }

    public function renderMetaBox(\WP_Post $post): void
    {
        wp_nonce_field('ravera6a_save_archive_toggle', 'ravera6a_archive_toggle_nonce');

        $is_archived = $this->isArchived($post->ID);
        ?>
        <p>
            <label>
                <input
                    type="checkbox"
                    name="ravera6a_archived"
                    value="1"
                    <?php checked($is_archived); ?>
                >
                <?php esc_html_e('Archiver cet article', 'ravera6a'); ?>
            </label>
        </p>

        <p style="color:#646970;">
            <?php esc_html_e('Un article archivé disparaît des listes et boucles de requêtes, mais reste accessible avec son lien direct.', 'ravera6a'); ?>
        </p>
        <?php
    }

    public function saveArchivedState(int $post_id, \WP_Post $post): void
    {
        if (!in_array($post->post_type, $this->post_types, true)) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post_id)) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $is_quick_edit = isset($_POST['ravera6a_archived_quick']);

        if ($is_quick_edit) {
            if (
                !isset($_POST['_inline_edit'])
                || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_inline_edit'])), 'inlineeditnonce')
            ) {
                return;
            }

            $is_archived = sanitize_text_field(wp_unslash($_POST['ravera6a_archived_quick'])) === '1';
        } else {
            if (
                !isset($_POST['ravera6a_archive_toggle_nonce'])
                || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ravera6a_archive_toggle_nonce'])), 'ravera6a_save_archive_toggle')
            ) {
                return;
            }

            $is_archived = isset($_POST['ravera6a_archived']);
        }

        if ($is_archived) {
            update_post_meta($post_id, self::META_KEY, '1');
        } else {
            delete_post_meta($post_id, self::META_KEY);
        }
    }

    public function excludeArchivedFromFrontQueries(\WP_Query $query): void
    {
        if (is_admin()) {
            return;
        }

        if ($query->is_singular()) {
            return;
        }

        $post_type = $query->get('post_type');

        if (empty($post_type)) {
            return;
        }

        $post_types = is_array($post_type) ? $post_type : [$post_type];
        $has_target_post_type = (bool) array_intersect($post_types, $this->post_types);

        if (!$has_target_post_type) {
            return;
        }

        $meta_query = $query->get('meta_query');

        if (!is_array($meta_query)) {
            $meta_query = [];
        }

        $meta_query[] = [
            'relation' => 'OR',
            [
                'key'     => self::META_KEY,
                'compare' => 'NOT EXISTS',
            ],
            [
                'key'     => self::META_KEY,
                'value'   => '1',
                'compare' => '!=',
            ],
        ];

        $query->set('meta_query', $meta_query);
    }

    public function addAdminColumn(array $columns): array
    {
        $new_columns = [];

        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;

            if ($key === 'title') {
                $new_columns['ravera6a_archived'] = __('Archivé', 'ravera6a');
            }
        }

        return $new_columns;
    }

    public function renderAdminColumn(string $column, int $post_id): void
    {
        if ($column !== 'ravera6a_archived') {
            return;
        }

        $is_archived = $this->isArchived($post_id);

        echo '<span class="ravera6a-archived-value" data-archived="' . esc_attr($is_archived ? '1' : '0') . '">';
        echo $is_archived ? esc_html__('Oui', 'ravera6a') : '—';
        echo '</span>';
    }

    public function renderQuickEditField(string $column_name, string $post_type): void
    {
        if ($column_name !== 'ravera6a_archived') {
            return;
        }

        if (!in_array($post_type, $this->post_types, true)) {
            return;
        }

        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <label class="alignleft">
                    <input type="hidden" name="ravera6a_archived_quick" value="0">
                    <input type="checkbox" name="ravera6a_archived_quick" value="1">
                    <span class="checkbox-title"><?php esc_html_e('Archiver cet article', 'ravera6a'); ?></span>
                </label>
            </div>
        </fieldset>
        <?php
    }

    public function printQuickEditScript(): void
    {
        $screen = get_current_screen();

        if (!$screen || !in_array($screen->post_type, $this->post_types, true)) {
            return;
        }

        ?>
        <script>
            jQuery(function ($) {
                const originalEdit = inlineEditPost.edit;

                inlineEditPost.edit = function (id) {
                    originalEdit.apply(this, arguments);

                    let postId = 0;

                    if (typeof id === 'object') {
                        postId = parseInt(this.getId(id), 10);
                    } else {
                        postId = parseInt(id, 10);
                    }

                    if (!postId) {
                        return;
                    }

                    const $row = $('#post-' + postId);
                    const archived = $row.find('.ravera6a-archived-value').data('archived') == 1;

                    const $editRow = $('#edit-' + postId);
                    $editRow.find('input[name="ravera6a_archived_quick"][type="checkbox"]').prop('checked', archived);
                };
            });
        </script>
        <?php
    }

    private function isArchived(int $post_id): bool
    {
        return get_post_meta($post_id, self::META_KEY, true) === '1';
    }
}