<?php

namespace Ravera6a;

class ravera6aTripsSecurity
{
    public const OPTION_NAME = 'ravera6a_trips_password';
    public const COOKIE_NAME = 'ravera6a_trips_access';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addSubmenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_init', [$this, 'maybeSyncAfterSettingsSave']);
        add_action('save_post', [$this, 'applyPasswordToTrip'], 20, 3);

        add_action('template_redirect', [$this, 'handleTripsProtection'], 1);
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
                echo '<p>Ce même mot de passe protégera aussi la page archive /sorties.</p>';
                echo '<p>Un seul déverrouillage donnera accès à /sorties et à toutes les fiches sorties.</p>';
                echo '<p>Si le champ est vide, les articles Trips et la page /sorties ne seront plus protégés.</p>';
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
            Ce mot de passe sera appliqué automatiquement à tous les articles Trips existants et futurs, ainsi qu’à la page archive /sorties.
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

    public function handleTripsProtection(): void
    {
        if (is_admin()) {
            return;
        }

        $isTripsArchive = is_post_type_archive(ravera6aTripsPostType::POST_TYPE);
        $isTripsSingle  = is_singular(ravera6aTripsPostType::POST_TYPE);

        if (! $isTripsArchive && ! $isTripsSingle) {
            return;
        }

        $password = get_option(self::OPTION_NAME, '');
        $password = is_string($password) ? $password : '';

        if ($password === '') {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ravera6a_trips_access_password'])) {
            $submittedPassword = sanitize_text_field(wp_unslash($_POST['ravera6a_trips_access_password']));

            if (hash_equals($password, $submittedPassword)) {
                $this->setAccessCookie($password);

                $redirectUrl = $this->getCurrentRequestUrl();
                if (! $redirectUrl) {
                    $redirectUrl = get_post_type_archive_link(ravera6aTripsPostType::POST_TYPE);
                }

                wp_safe_redirect($redirectUrl);
                exit;
            }

            $this->renderPasswordForm(true);
            exit;
        }

        if ($this->hasValidAccessCookie($password)) {
            if ($isTripsSingle) {
                $this->maybeBypassCorePostPassword();
            }
            return;
        }

        $this->renderPasswordForm(false);
        exit;
    }

    protected function hasValidAccessCookie(string $password): bool
    {
        if (! isset($_COOKIE[self::COOKIE_NAME])) {
            return false;
        }

        $cookieValue = sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE_NAME]));
        $expected    = wp_hash($password);

        return hash_equals($expected, $cookieValue);
    }

    protected function setAccessCookie(string $password): void
    {
        $value    = wp_hash($password);
        $expire   = time() + DAY_IN_SECONDS;
        $secure   = is_ssl();
        $httponly = true;
        $path     = defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/';

        setcookie(
            self::COOKIE_NAME,
            $value,
            [
                'expires'  => $expire,
                'path'     => $path,
                'secure'   => $secure,
                'httponly' => $httponly,
                'samesite' => 'Lax',
            ]
        );

        $_COOKIE[self::COOKIE_NAME] = $value;
    }

    protected function maybeBypassCorePostPassword(): void
    {
        add_filter('post_password_required', [$this, 'disablePostPasswordRequirementForTrips'], 10, 2);
    }

    public function disablePostPasswordRequirementForTrips(bool $required, \WP_Post $post): bool
    {
        if ($post->post_type !== ravera6aTripsPostType::POST_TYPE) {
            return $required;
        }

        $password = get_option(self::OPTION_NAME, '');
        $password = is_string($password) ? $password : '';

        if ($password === '') {
            return $required;
        }

        if ($this->hasValidAccessCookie($password)) {
            return false;
        }

        return $required;
    }

    protected function getCurrentRequestUrl(): string
    {
        $requestUri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';

        if (! is_string($requestUri) || $requestUri === '') {
            return '';
        }

        return home_url($requestUri);
    }

    protected function renderPasswordForm(bool $hasError = false): void
    {
        status_header(200);
        nocache_headers();

        $targetUrl = $this->getCurrentRequestUrl();
        if ($targetUrl === '') {
            $targetUrl = get_post_type_archive_link(ravera6aTripsPostType::POST_TYPE);
        }

        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Sorties protégées</title>
            <?php wp_head(); ?>
            <style>
                body.ravera6a-trips-protected {
                    margin: 0;
                    font-family: inherit;
                    background: #f7f3ee;
                    color: #1a1a1a;
                }

                .ravera6a-trips-protected__wrap {
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 24px;
                    box-sizing: border-box;
                }

                .ravera6a-trips-protected__box {
                    width: 100%;
                    max-width: 520px;
                    background: #ffffff;
                    border: 1px solid #ddd;
                    border-radius: 16px;
                    padding: 32px;
                    box-sizing: border-box;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
                }

                .ravera6a-trips-protected__title {
                    margin: 0 0 12px;
                    font-size: 32px;
                    line-height: 1.1;
                }

                .ravera6a-trips-protected__text {
                    margin: 0 0 20px;
                    line-height: 1.6;
                }

                .ravera6a-trips-protected__error {
                    margin: 0 0 16px;
                    padding: 12px 14px;
                    border-radius: 10px;
                    background: #fdeaea;
                    color: #8f1111;
                    font-size: 14px;
                }

                .ravera6a-trips-protected__label {
                    display: block;
                    margin-bottom: 8px;
                    font-weight: 600;
                }

                .ravera6a-trips-protected__input {
                    width: 100%;
                    min-height: 48px;
                    padding: 12px 14px;
                    border: 1px solid #ccc;
                    border-radius: 10px;
                    box-sizing: border-box;
                    font: inherit;
                    margin-bottom: 16px;
                }

                .ravera6a-trips-protected__button {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 48px;
                    padding: 0 20px;
                    border: 0;
                    border-radius: 999px;
                    background: #1a1a1a;
                    color: #fff;
                    font: inherit;
                    cursor: pointer;
                }
            </style>
        </head>
        <body <?php body_class('ravera6a-trips-protected'); ?>>
            <?php wp_body_open(); ?>

            <div class="ravera6a-trips-protected__wrap">
                <div class="ravera6a-trips-protected__box">
                    <h1 class="ravera6a-trips-protected__title">Sorties protégées</h1>
                    <p class="ravera6a-trips-protected__text">
                        Cette section est protégée par mot de passe. Entre le mot de passe pour accéder aux sorties.
                    </p>

                    <?php if ($hasError): ?>
                        <p class="ravera6a-trips-protected__error">
                            Mot de passe incorrect.
                        </p>
                    <?php endif; ?>

                    <form method="post" action="<?php echo esc_url($targetUrl); ?>">
                        <label class="ravera6a-trips-protected__label" for="ravera6a-trips-access-password">
                            Mot de passe
                        </label>

                        <input
                            class="ravera6a-trips-protected__input"
                            id="ravera6a-trips-access-password"
                            name="ravera6a_trips_access_password"
                            type="password"
                            required
                            autocomplete="current-password" />

                        <button class="ravera6a-trips-protected__button" type="submit">
                            Entrer
                        </button>
                    </form>
                </div>
            </div>

            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }
}