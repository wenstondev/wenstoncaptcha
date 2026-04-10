<?php
/**
 * CAPTCHA image logic (character set and GD rendering), aligned with a self-hosted CaptchaService-style implementation.
 *
 * @package WenstonCaptcha
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Self-hosted image CAPTCHA. Codes stored in transients (token), not PHP sessions.
 */
final class WenstonCaptcha_Service
{
    /** Character pool (no 0/O, 1/l/I for readability). */
    private const CHARS = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    private const LENGTH = 5;

    private const WIDTH = 140;

    private const HEIGHT = 50;

    private const TRANSIENT_PREFIX = 'wenstoncaptcha_t_';

    private const TRANSIENT_TTL = 600;

    /** Nonce action for CAPTCHA image AJAX (verified in WenstonCaptcha_Plugin::ajax_serve_image). */
    public const IMAGE_NONCE_ACTION = 'wenstoncaptcha_image';

    /**
     * @return array{token: string, code: string}
     */
    public function create_challenge(): array
    {
        $max  = strlen(self::CHARS) - 1;
        $code = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::CHARS[ random_int(0, $max) ];
        }
        $token = $this->generate_token();
        $this->store_code($token, $code);

        return [
            'token' => $token,
            'code'  => $code,
        ];
    }

    public function get_image_bytes_for_token(string $token): string
    {
        $token = $this->sanitize_token($token);
        if ($token === '') {
            return $this->minimal_png();
        }
        $code = $this->get_code($token);
        if ($code === null || $code === '') {
            return $this->minimal_png();
        }

        return $this->render_image($code);
    }

    public function verify(string $token, string $user_input): bool
    {
        $token = $this->sanitize_token($token);
        if ($token === '') {
            return false;
        }
        $expected = $this->get_code($token);
        $this->delete_code($token);
        if ($expected === null) {
            return false;
        }
        $trimmed = trim(preg_replace('/\s+/', '', $user_input));

        return $trimmed !== '' && strtoupper($trimmed) === strtoupper($expected);
    }

    /**
     * AJAX image URL. Includes a nonce so only requests originating from generated markup are accepted.
     */
    public function get_image_url(string $token): string
    {
        $ajax_base = add_query_arg(
            [
                'action' => 'wenstoncaptcha_image',
                'token'  => rawurlencode($token),
            ],
            admin_url('admin-ajax.php')
        );

        return wp_nonce_url($ajax_base, self::IMAGE_NONCE_ACTION);
    }

    private function generate_token(): string
    {
        if (function_exists('wp_generate_password')) {
            return wp_generate_password(48, false, false);
        }

        return bin2hex(random_bytes(24));
    }

    private function sanitize_token(string $token): string
    {
        $token = trim($token);

        return preg_match('/^[a-zA-Z0-9]+$/', $token) ? $token : '';
    }

    private function transient_key(string $token): string
    {
        return self::TRANSIENT_PREFIX . md5($token);
    }

    private function store_code(string $token, string $code): void
    {
        set_transient($this->transient_key($token), strtoupper($code), self::TRANSIENT_TTL);
    }

    private function get_code(string $token): ?string
    {
        $v = get_transient($this->transient_key($token));

        return is_string($v) && $v !== '' ? $v : null;
    }

    private function delete_code(string $token): void
    {
        delete_transient($this->transient_key($token));
    }

    private function render_image(string $code): string
    {
        if (! extension_loaded('gd')) {
            return $this->render_image_fallback($code);
        }

        $w   = self::WIDTH;
        $h   = self::HEIGHT;
        $img = imagecreate($w, $h);
        if ($img === false) {
            return $this->render_image_fallback($code);
        }

        $bg        = imagecolorallocate($img, 240, 242, 245);
        $textColor = imagecolorallocate($img, 40, 44, 52);
        $lineColor = imagecolorallocate($img, 180, 185, 195);
        imagefill($img, 0, 0, $bg);

        $len        = strlen($code);
        $font       = 5;
        $charWidth  = imagefontwidth($font);
        $charHeight = imagefontheight($font);
        $totalWidth = $len * $charWidth;
        $x          = (int) (($w - $totalWidth) / 2) + 2;
        $y          = (int) (($h - $charHeight) / 2) - 2;

        for ($i = 0; $i < $len; $i++) {
            $ox = $x + $i * $charWidth;
            $oy = $y + random_int(-3, 3);
            imagestring($img, $font, $ox, $oy, $code[ $i ], $textColor);
        }

        for ($i = 0; $i < 3; $i++) {
            imageline($img, random_int(0, $w), random_int(0, $h), random_int(0, $w), random_int(0, $h), $lineColor);
        }
        for ($i = 0; $i < 80; $i++) {
            imagesetpixel($img, random_int(0, $w - 1), random_int(0, $h - 1), $lineColor);
        }

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        return $png !== false ? $png : $this->render_image_fallback($code);
    }

    private function render_image_fallback(string $code): string
    {
        $w   = self::WIDTH;
        $h   = self::HEIGHT;
        $img = @imagecreate($w, $h);
        if ($img !== false) {
            $bg        = imagecolorallocate($img, 240, 242, 245);
            $textColor = imagecolorallocate($img, 40, 44, 52);
            imagefill($img, 0, 0, $bg);
            imagestring($img, 5, 20, 15, $code, $textColor);
            ob_start();
            imagepng($img);
            $png = ob_get_clean();
            imagedestroy($img);
            if ($png !== false) {
                return $png;
            }
        }

        return $this->minimal_png();
    }

    private function minimal_png(): string
    {
        $raw = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', true);

        return is_string($raw) ? $raw : '';
    }
}
