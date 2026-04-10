<?php
/**
 * Shortcode, AJAX image, Contact Form 7 integration.
 *
 * @package WenstonCaptcha
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class WenstonCaptcha_Plugin
{
    private WenstonCaptcha_Service $service;

    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->service = new WenstonCaptcha_Service();
    }

    public function service(): WenstonCaptcha_Service
    {
        return $this->service;
    }

    public function init(): void
    {
        add_shortcode('wenstoncaptcha', [ $this, 'shortcode_wenstoncaptcha' ]);
        add_action('wp_ajax_wenstoncaptcha_image', [ $this, 'ajax_serve_image' ]);
        add_action('wp_ajax_nopriv_wenstoncaptcha_image', [ $this, 'ajax_serve_image' ]);

        add_action('wpcf7_init', [ $this, 'cf7_register_tag' ]);
        add_filter('wpcf7_validate_wenstoncaptcha', [ $this, 'cf7_validate' ], 10, 2);
    }

    /**
     * Shortcode: [wenstoncaptcha label="..." placeholder="..."]
     */
    public function shortcode_wenstoncaptcha(array $atts = [], ?string $content = null): string
    {
        $atts = shortcode_atts(
            [
                'label'       => __('Security code', 'wenstoncaptcha'),
                'placeholder' => __('Characters from the image', 'wenstoncaptcha'),
                'name'        => 'wenstoncaptcha_answer',
            ],
            $atts,
            'wenstoncaptcha'
        );

        $challenge = $this->service->create_challenge();
        $token     = $challenge['token'];
        $img_url   = $this->service->get_image_url($token);
        $field     = sanitize_key($atts['name']);

        ob_start();
        ?>
        <div class="wenstoncaptcha wenstoncaptcha--shortcode">
            <input type="hidden" name="wenstoncaptcha_token" value="<?php echo esc_attr($token); ?>" />
            <p class="wenstoncaptcha__image-wrap">
                <img src="<?php echo esc_url($img_url); ?>" alt="<?php esc_attr_e('CAPTCHA image', 'wenstoncaptcha'); ?>" class="wenstoncaptcha__image" width="<?php echo esc_attr((string) 140); ?>" height="<?php echo esc_attr((string) 50); ?>" />
            </p>
            <p class="wenstoncaptcha__field">
                <label class="wenstoncaptcha__label">
                    <span class="wenstoncaptcha__label-text"><?php echo esc_html($atts['label']); ?></span>
                    <input type="text" name="<?php echo esc_attr($field); ?>" class="wenstoncaptcha__input" autocomplete="off" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" />
                </label>
            </p>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    /**
     * Output CAPTCHA PNG for admin-ajax action `wenstoncaptcha_image`.
     *
     * Verifies `_wpnonce` (see WenstonCaptcha_Service::get_image_url). The challenge `token` is an additional secret.
     * No `current_user_can()` check: CAPTCHA images must load for anonymous visitors on public forms.
     */
    public function ajax_serve_image(): void
    {
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash((string) $_GET['_wpnonce'])) : '';
        if ($nonce === '' || ! wp_verify_nonce($nonce, WenstonCaptcha_Service::IMAGE_NONCE_ACTION)) {
            status_header(403);
            exit;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified above; `token` is the per-challenge secret from the same URL.
        $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash((string) $_GET['token'])) : '';
        if ($token === '') {
            status_header(400);
            exit;
        }
        $png = $this->service->get_image_bytes_for_token($token);
        nocache_headers();
        header('Content-Type: image/png');
        header('X-Robots-Tag: noindex, nofollow');
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw PNG binary, not HTML.
        echo $png;
        exit;
    }

    public function cf7_register_tag(): void
    {
        if (! function_exists('wpcf7_add_form_tag')) {
            return;
        }
        wpcf7_add_form_tag(
            [ 'wenstoncaptcha' ],
            [ $this, 'cf7_form_tag_handler' ],
            [
                'name-attr' => true,
            ]
        );
    }

    /**
     * @param WPCF7_FormTag $tag
     */
    public function cf7_form_tag_handler($tag): string
    {
        $challenge = $this->service->create_challenge();
        $token     = $challenge['token'];
        $img_url   = $this->service->get_image_url($token);
        $name      = $tag->name !== '' ? $tag->name : 'wenstoncaptcha-answer';

        ob_start();
        ?>
        <span class="wpcf7-form-control-wrap <?php echo esc_attr($name); ?>" data-name="<?php echo esc_attr($name); ?>">
            <input type="hidden" name="wenstoncaptcha_token" value="<?php echo esc_attr($token); ?>" />
            <span class="wenstoncaptcha wenstoncaptcha--cf7">
                <span class="wenstoncaptcha__image-wrap">
                    <img src="<?php echo esc_url($img_url); ?>" alt="" class="wenstoncaptcha__image" width="140" height="50" />
                </span>
                <span class="wenstoncaptcha__field">
                    <input type="text" name="<?php echo esc_attr($name); ?>" class="wpcf7-form-control wpcf7-text wenstoncaptcha__input" autocomplete="off" aria-required="true" />
                </span>
            </span>
        </span>
        <?php

        return (string) ob_get_clean();
    }

    /**
     * @param WPCF7_Validation $result
     * @param WPCF7_FormTag    $tag
     */
    public function cf7_validate($result, $tag)
    {
        $name = $tag->name;
        if ($name === '') {
            return $result;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Contact Form 7 validates the form submission before validation filters run.
        $token = isset($_POST['wenstoncaptcha_token'])
            ? sanitize_text_field(wp_unslash((string) $_POST['wenstoncaptcha_token']))
            : '';
        $answer = isset($_POST[ $name ])
            ? sanitize_text_field(wp_unslash((string) $_POST[ $name ]))
            : '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        if (! $this->service->verify($token, $answer)) {
            $result->invalidate(
                $tag,
                apply_filters('wenstoncaptcha_cf7_invalid_message', __('The security code is invalid or has expired.', 'wenstoncaptcha'))
            );
        }

        return $result;
    }
}
