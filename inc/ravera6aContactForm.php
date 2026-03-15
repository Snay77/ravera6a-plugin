<?php

namespace Ravera6a;

class ContactForm
{
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'registerAssets']);
        add_shortcode('ravera_contact_form', [$this, 'renderShortcode']);
    }

    public function registerAssets(): void
    {
        wp_register_style(
            'ravera-contact-form',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/contact-form.css',
            [],
            filemtime(plugin_dir_path(dirname(__FILE__)) . 'assets/css/contact-form.css')
        );
    }

    public function renderShortcode(): string
    {
        wp_enqueue_style('ravera-contact-form');

        $output = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact_form'])) {
            $output .= $this->handleFormSubmission();
        }

        if (! isset($_POST['submit_contact_form']) || ! empty($this->hasFormError())) {
            $output .= $this->getFormHtml();
        }

        return $output;
    }

    private function handleFormSubmission(): string
    {
        if (
            ! isset($_POST['ravera_contact_nonce']) ||
            ! wp_verify_nonce($_POST['ravera_contact_nonce'], 'ravera_contact_form_action')
        ) {
            return '<p class="error-message">La vérification de sécurité a échoué.</p>';
        }

        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name  = sanitize_text_field($_POST['last_name'] ?? '');
        $phone      = sanitize_text_field($_POST['phone'] ?? '');
        $email      = sanitize_email($_POST['email'] ?? '');
        $message    = sanitize_textarea_field($_POST['message'] ?? '');
        $privacy    = isset($_POST['privacy_policy']) ? 1 : 0;

        if (! $first_name || ! $last_name || ! $phone || ! $email || ! $message || ! $privacy) {
            return '<p class="error-message">Merci de remplir tous les champs obligatoires.</p>';
        }

        if (! is_email($email)) {
            return '<p class="error-message">L’adresse email est invalide.</p>';
        }

        $to      = 'contact@ravera-6a.com';
        $subject = 'Nouveau message de contact de ' . $first_name . ' ' . $last_name;

        $body  = "Nom: {$first_name} {$last_name}\n";
        $body .= "Téléphone: {$phone}\n";
        $body .= "Email: {$email}\n\n";
        $body .= "Message:\n{$message}\n";

        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'Reply-To: ' . $email,
        ];

        $mail_sent = wp_mail($to, $subject, $body, $headers);

        if ($mail_sent) {
            return '<p class="confirmation-message">Merci pour votre message. Nous vous répondrons dès que possible.</p>';
        }

        return '<p class="error-message">Désolé, une erreur est survenue lors de l’envoi du message. Veuillez réessayer plus tard.</p>';
    }

    private function getFormHtml(): string
    {
        ob_start();
        ?>
        <div class="contact-container">
            <div class="contact-container-bloc">
                <h2>Nous contacter</h2>

                <form action="" method="POST">
                    <?php wp_nonce_field('ravera_contact_form_action', 'ravera_contact_nonce'); ?>
                    <input type="hidden" name="submit_contact_form" value="1">

                    <div class="nom-prenom">
                        <div class="form-flex">
                            <label for="first_name">Prénom <span class="required-red">*</span></label>
                            <input type="text" name="first_name" id="first_name" required>
                        </div>

                        <div class="form-flex">
                            <label for="last_name">Nom <span class="required-red">*</span></label>
                            <input type="text" name="last_name" id="last_name" required>
                        </div>
                    </div>

                    <div class="form-flex">
                        <label for="phone">Téléphone <span class="required-red">*</span></label>
                        <input type="tel" name="phone" id="phone" required>
                    </div>

                    <div class="form-flex">
                        <label for="email">Email <span class="required-red">*</span></label>
                        <input type="email" name="email" id="email" required>
                    </div>

                    <div class="form-flex">
                        <label for="message">Message <span class="required-red">*</span></label>
                        <textarea name="message" id="message" required></textarea>
                    </div>

                    <div class="contact-container-envoie">
                        <p>* champs obligatoires</p>

                        <div>
                            <input class="checkbox-pointer" type="checkbox" name="privacy_policy" id="privacy_policy" value="1" required>
                            <label for="privacy_policy" class="privacy-policy-text">
                                J'accepte la
                                <a href="<?php echo esc_url(home_url('/politique-de-confidentialite/')); ?>" target="_blank" rel="noopener noreferrer">
                                    politique de confidentialité
                                </a>
                            </label>
                        </div>

                        <div>
                            <input class="envoie-input" type="submit" value="Envoyer">
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function hasFormError(): bool
    {
        return false;
    }
}