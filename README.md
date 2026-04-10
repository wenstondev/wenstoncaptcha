# wenstonCaptcha (WordPress plugin)

Self-hosted image CAPTCHA for WordPress. **Image generation** follows a typical self-hosted CAPTCHA approach (character set, dimensions, noise lines, GD fallback). **Storage** uses WordPress **transients** and a form token (no PHP sessions).

For more information see **https://wenston.io/**

## Requirements

- WordPress 5.8+
- PHP 7.4+
- PHP **GD** extension

## Installation

1. Copy the `wenstoncaptcha` folder to `wp-content/plugins/`.
2. Activate the plugin in the WordPress admin.

## Usage

### Shortcode

```
[wenstoncaptcha label="Security code" placeholder="Characters from the image"]
```

Output: hidden field `wenstoncaptcha_token`, image (via AJAX), input field `wenstoncaptcha_answer`.

For custom handling in a theme or plugin, after form submit call `wenstoncaptcha_service()->verify( $token, $answer )` with the token and answer from `$_POST`.

### Contact Form 7

In the form editor, for example:

```
<label> Security code
[wenstoncaptcha my-field]
</label>
```

The field name (`my-field`) is up to you. The plugin validates the input automatically on submit.

## API

```php
$service = wenstoncaptcha_service();
$ok = $service->verify( $token_from_post, $user_input );
```

## Translations

Translation files live under `languages/` as `wenstoncaptcha-{locale}.po` / `.mo` (WordPress convention). Included locales:

`de_DE`, `fr_FR`, `es_ES`, `it_IT`, `ru_RU`, `uk`, `sv_SE`, `fi`, `da_DK`, `bg_BG`, `el`, `pl_PL`, `cs_CZ`

Source strings in PHP are English; the template is `languages/wenstoncaptcha.pot`. After changing strings, run `wp i18n make-pot` and/or `msgfmt` to regenerate `.mo` files.


## License

GPL v2 or later
