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

        add_filter('the_password_form', [$this, 'customizeTripsPasswordForm']);
        add_filter('post_password_expires', [$this, 'setTripsPasswordCookieAsSession']);
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
                echo '<p>Le cookie d\'accès sera supprimé à la fermeture du navigateur.</p>';
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

    public function customizeTripsPasswordForm(string $output): string
    {
        $post = get_post();

        if (! $post instanceof \WP_Post) {
            return $output;
        }

        if ($post->post_type !== ravera6aTripsPostType::POST_TYPE) {
            return $output;
        }

        $labelId = 'pwbox-' . (int) $post->ID;
        $action  = esc_url(site_url('wp-login.php?action=postpass', 'login_post'));

        ob_start();
        ?>
        <div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--xl);padding-bottom:var(--wp--preset--spacing--xl);">
            <div class="wp-block-group" style="max-width:640px;margin-left:auto;margin-right:auto;padding:var(--wp--preset--spacing--l);border:1px solid var(--wp--preset--color--form-border-input);border-radius:var(--wp--custom--radius--m);background:var(--wp--preset--color--white);box-shadow:var(--wp--preset--shadow--medium);">
                <h1 class="wp-block-heading">Accès réservé</h1>

                <p>Veuillez entrer le code d'accès pour voir cette page :</p>

                <form class="post-password-form" action="<?php echo $action; ?>" method="post">
                    <label for="<?php echo esc_attr($labelId); ?>" style="display:block;margin-bottom:8px;font-weight:700;color:var(--wp--preset--color--primary-accent);">
                        Code d'accès
                    </label>

                    <input
                        name="post_password"
                        id="<?php echo esc_attr($labelId); ?>"
                        type="password"
                        spellcheck="false"
                        size="20"
                        placeholder="Code d'accès"
                        style="width:100%;min-height:48px;padding:12px 14px;margin-bottom:var(--wp--preset--spacing--m);border:1px solid var(--wp--preset--color--form-border-input);border-radius:var(--wp--custom--radius--s);background:var(--wp--preset--color--form-background-input);color:var(--wp--preset--color--black);box-sizing:border-box;font:inherit;" />

                    <div class="wp-block-buttons">
                        <div class="wp-block-button">
                            <button class="wp-block-button__link wp-element-button" type="submit" name="Submit">
                                Valider
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    public function setTripsPasswordCookieAsSession(int $expires): int
    {
        if (! $this->isTripsPasswordRequest()) {
            return $expires;
        }

        return 0;
    }

    private function isTripsPasswordRequest(): bool
    {
        if (! isset($_REQUEST['action']) || $_REQUEST['action'] !== 'postpass') {
            return false;
        }

        $referer = wp_get_referer();

        if (! $referer) {
            $referer = isset($_SERVER['HTTP_REFERER']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_REFERER'])) : '';
        }

        if (! $referer) {
            return false;
        }

        $postId = url_to_postid($referer);

        if (! $postId) {
            return false;
        }

        $post = get_post($postId);

        if (! $post instanceof \WP_Post) {
            return false;
        }

        return $post->post_type === ravera6aTripsPostType::POST_TYPE;
    }
}