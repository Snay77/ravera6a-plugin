<?php

namespace Ravera6a;

class ravera6aTripsSecurity
{
    public const OPTION_NAME = 'ravera6a_trips_password';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addSubmenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_init', [$this, 'maybeSyncAfterSettingsSave']);
        add_action('save_post', [$this, 'applyPasswordToTrip'], 20, 3);
    }

    public function addSubmenu(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        add_submenu_page(
            'edit.php?post_type=' . ravera6aTripsPostType::POST_TYPE,
            'Sécurité',
            'Sécurité',
            'manage_options',
            'ravera6a-trips-security',
            [$this, 'renderPage']
        );
    }

    public function registerSettings(): void
    {
        register_setting(
            'ravera6a_trips_security_group',
            self::OPTION_NAME,
            [
                'type' => 'string',
                'sanitize_callback' => [$this, 'sanitizePassword'],
                'default' => '',
            ]
        );

        add_settings_section(
            'ravera6a_trips_security_section',
            'Protection des sorties',
            function () {
                echo '<p>Définis ici le mot de passe global appliqué à tous les articles du CPT Trips.</p>';
                echo '<p>Si le champ est vide, les articles Trips ne seront plus protégés par mot de passe.</p>';
            },
            'ravera6a-trips-security'
        );

        add_settings_field(
            'ravera6a_trips_password_field',
            'Mot de passe global',
            [$this, 'renderPasswordField'],
            'ravera6a-trips-security',
            'ravera6a_trips_security_section'
        );
    }

    public function sanitizePassword($value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return sanitize_text_field($value);
    }

    public function renderPasswordField(): void
    {
        $value = get_option(self::OPTION_NAME, '');
?>
        <input
            type="text"
            name="<?php echo esc_attr(self::OPTION_NAME); ?>"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text"
            autocomplete="off" />
        <p class="description">
            Ce mot de passe sera appliqué automatiquement à tous les articles Trips existants et futurs.
        </p>
    <?php
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

    ?>
        <div class="wrap">
            <h1>Sécurité des sorties</h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('ravera6a_trips_security_group');
                do_settings_sections('ravera6a-trips-security');
                submit_button('Enregistrer');
                ?>
            </form>
        </div>
<?php
    }

    public function maybeSyncAfterSettingsSave(): void
    {
        if (! is_admin()) {
            return;
        }

        if (! current_user_can('manage_options')) {
            return;
        }

        if (! isset($_GET['page'], $_GET['settings-updated'])) {
            return;
        }

        if ($_GET['page'] !== 'ravera6a-trips-security') {
            return;
        }

        if ($_GET['settings-updated'] !== 'true') {
            return;
        }

        $this->syncAllTripsPasswords();
    }

    public function syncAllTripsPasswords(): void
    {
        global $wpdb;

        $password = get_option(self::OPTION_NAME, '');
        $password = is_string($password) ? $password : '';

        $tripIds = get_posts([
            'post_type' => ravera6aTripsPostType::POST_TYPE,
            'post_status' => ['publish', 'future', 'draft', 'pending', 'private'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
            'no_found_rows' => true,
            'suppress_filters' => true,
        ]);

        if (empty($tripIds)) {
            return;
        }

        foreach ($tripIds as $tripId) {
            $wpdb->update(
                $wpdb->posts,
                [
                    'post_password' => $password,
                ],
                [
                    'ID' => $tripId,
                ],
                [
                    '%s',
                ],
                [
                    '%d',
                ]
            );

            clean_post_cache($tripId);
        }
    }

    public function applyPasswordToTrip(int $postId, \WP_Post $post, bool $update): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($postId)) {
            return;
        }

        if (wp_is_post_autosave($postId)) {
            return;
        }

        if ($post->post_type !== ravera6aTripsPostType::POST_TYPE) {
            return;
        }

        $globalPassword = get_option(self::OPTION_NAME, '');
        $globalPassword = is_string($globalPassword) ? $globalPassword : '';

        if ($post->post_password === $globalPassword) {
            return;
        }

        remove_action('save_post', [$this, 'applyPasswordToTrip'], 20);

        wp_update_post([
            'ID' => $postId,
            'post_password' => $globalPassword,
        ]);

        add_action('save_post', [$this, 'applyPasswordToTrip'], 20, 3);
    }
}
