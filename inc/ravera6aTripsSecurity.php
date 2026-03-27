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
                echo '<p>Définis ici le mot de passe global appliqué à toute la section sorties.</p>';
                echo '<p>Il protégera à la fois la page archive /sorties et toutes les fiches sorties.</p>';
                echo '<p>Si le champ est vide, la section sorties ne sera plus protégée.</p>';
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
            Ce mot de passe protégera la page /sorties ainsi que toutes les fiches du CPT Trips.
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

        if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && isset($_POST['ravera6a_trips_access_password'])
        ) {
            $submittedPassword = sanitize_text_field(
                wp_unslash($_POST['ravera6a_trips_access_password'])
            );

            if (hash_equals($password, $submittedPassword)) {
                $this->setAccessCookie($password);

                $redirectUrl = $this->getCurrentRequestUrl();
                if ($redirectUrl === '') {
                    $redirectUrl = get_post_type_archive_link(ravera6aTripsPostType::POST_TYPE);
                }

                wp_safe_redirect($redirectUrl);
                exit;
            }

            $this->renderPasswordForm(true);
            exit;
        }

        if ($this->hasValidAccessCookie($password)) {
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
            <title>Accès réservé</title>
            <?php wp_head(); ?>
            <style>
                .ravera6a-trips-protected-page {
                    min-height: 100vh;
                }

                .ravera6a-trips-protected-main {
                    padding-top: var(--wp--preset--spacing--xl, 64px);
                    padding-bottom: var(--wp--preset--spacing--xl, 64px);
                }

                .ravera6a-trips-protected-box {
                    max-width: 640px;
                    margin: 0 auto;
                    background: var(--wp--preset--color--white, #FFFAF2);
                    border: 1px solid var(--wp--preset--color--form-border-input, #CFCFCF);
                    border-radius: var(--wp--custom--radius--m, 16px);
                    padding: var(--wp--preset--spacing--l, 32px);
                    box-sizing: border-box;
                    box-shadow: var(--wp--preset--shadow--medium, 0px 30px 60px 0 rgba(138, 149, 158, 0.2));
                }

                .ravera6a-trips-protected-text {
                    margin-top: 0;
                    margin-bottom: var(--wp--preset--spacing--m, 24px);
                }

                .ravera6a-trips-protected-error {
                    margin-top: 0;
                    margin-bottom: var(--wp--preset--spacing--m, 24px);
                    padding: 12px 14px;
                    border-radius: var(--wp--custom--radius--s, 8px);
                    background: #fdeaea;
                    color: #8f1111;
                }

                .ravera6a-trips-protected-label {
                    display: block;
                    margin-bottom: 8px;
                    font-size: var(--wp--preset--font-size--s, 16px);
                    font-weight: var(--wp--custom--font-weight--bold, 700);
                    color: var(--wp--preset--color--primary-accent, #4A0605);
                }

                .ravera6a-trips-protected-input {
                    width: 100%;
                    min-height: 48px;
                    padding: 12px 14px;
                    margin-bottom: var(--wp--preset--spacing--m, 24px);
                    border: 1px solid var(--wp--preset--color--form-border-input, #CFCFCF);
                    border-radius: var(--wp--custom--radius--s, 8px);
                    background: var(--wp--preset--color--form-background-input, #EBE7E2);
                    color: var(--wp--preset--color--black, #1A1A1A);
                    font-family: inherit;
                    font-size: var(--wp--preset--font-size--s, 16px);
                    box-sizing: border-box;
                }

                .ravera6a-trips-protected-input::placeholder {
                    color: var(--wp--preset--color--grey, #3D3D3D);
                    opacity: 1;
                }

                .ravera6a-trips-protected-actions {
                    margin-top: 0;
                    margin-bottom: 0;
                }

                .ravera6a-trips-protected-actions .wp-block-button {
                    margin: 0;
                }

                .ravera6a-trips-protected-actions .wp-block-button__link {
                    border: 0;
                    cursor: pointer;
                }

                @media (max-width: 782px) {
                    .ravera6a-trips-protected-main {
                        padding-top: var(--wp--preset--spacing--l, 32px);
                        padding-bottom: var(--wp--preset--spacing--l, 32px);
                    }

                    .ravera6a-trips-protected-box {
                        padding: var(--wp--preset--spacing--m, 24px);
                    }
                }
            </style>
        </head>
        <body <?php body_class('ravera6a-trips-protected-page'); ?>>
            <?php wp_body_open(); ?>

            <?php
            echo do_blocks('<!-- wp:template-part {"slug":"header","theme":"ravera6a","area":"uncategorized"} /-->');
            ?>

            <main class="wp-block-group ravera6a-trips-protected-main alignfull">
                <div class="wp-block-group alignwide" style="max-width:var(--wp--style--global--wide-size, 1400px);margin-left:auto;margin-right:auto;padding-left:var(--wp--preset--spacing--s,16px);padding-right:var(--wp--preset--spacing--s,16px);">
                    <div class="ravera6a-trips-protected-box">
                        <?php
                        echo do_blocks('<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Accès réservé</h1><!-- /wp:heading -->');
                        ?>

                        <?php
                        echo do_blocks('<!-- wp:paragraph --><p>Veuillez entrer le code d\'accès pour voir cette page :</p><!-- /wp:paragraph -->');
                        ?>

                        <?php if ($hasError): ?>
                            <p class="ravera6a-trips-protected-error">Code d'accès incorrect.</p>
                        <?php endif; ?>

                        <form method="post" action="<?php echo esc_url($targetUrl); ?>">
                            <label class="ravera6a-trips-protected-label" for="ravera6a-trips-access-password">
                                Code d'accès
                            </label>

                            <input
                                class="ravera6a-trips-protected-input"
                                id="ravera6a-trips-access-password"
                                name="ravera6a_trips_access_password"
                                type="password"
                                required
                                autocomplete="current-password"
                                placeholder="Code d'accès" />

                            <div class="wp-block-buttons ravera6a-trips-protected-actions">
                                <div class="wp-block-button">
                                    <button class="wp-block-button__link wp-element-button" type="submit">
                                        Validé
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </main>

            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }
}