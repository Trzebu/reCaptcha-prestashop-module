<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/ConfigKeys.php';

class RecaptchaHelper {

    const PATH_TO_RECAPTCHA_JS = __DIR__ . '/views/js/recaptchaClient.js';

    public static function check (): bool {
        $captcha = new \ReCaptcha\ReCaptcha(
            Configuration::get(ConfigKeys::CAPTCHA_PRIVATE_KEY)
        );

        return $captcha->verify(
            Tools::getValue('g-recaptcha-response'),
            Tools::getRemoteAddr()
        )->isSuccess();
    }

    public static function getHtml (): string {
        $js = str_replace(
            'publicKeyReplacement', 
            Configuration::get(ConfigKeys::CAPTCHA_SITE_KEY),
            self::loadRecaptchaJS()
        );

        return "<script>{$js}</script>";
    }

    private static function loadRecaptchaJS (): string {
        return file_get_contents(self::PATH_TO_RECAPTCHA_JS);
    }

}