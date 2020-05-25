<?php

require_once __DIR__ . '/ConfigKeys.php';

class CaptchaEnabled {

    public static function registration (): bool {
        return Configuration::get(ConfigKeys::CAPTCHA_REGISTER_ENABLED) == 1;
    }

    public static function contact (): bool {
        return Configuration::get(ConfigKeys::CAPTCHA_CONTACT_ENABLED) == 1;
    }

}