<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

$PATH_TO_MODULE = $_SERVER['DOCUMENT_ROOT'] . '/modules/recaptcha';

require_once $PATH_TO_MODULE . '/RecaptchaHelper.php';
require_once $PATH_TO_MODULE . '/CaptchaEnabled.php';

/**
 * To refactor when presta fix problem with hook to actions before account register.
 */
class AuthController extends AuthControllerCore {

    const URL_TO_REDIRECT_AFTER_CAPTCHA_FAIL = 'index.php?controller=authentication&create_account=1';

    public function initContent () {
        if (Tools::isSubmit('submitCreate')) {
            if (CaptchaEnabled::registration() && !RecaptchaHelper::check()) {
                $this->context->cookie->__set('captcha_error', 1);

                return Tools::redirect(self::URL_TO_REDIRECT_AFTER_CAPTCHA_FAIL);
            }
        } else if (Tools::isSubmit('create_account')) {
            $this->tryDisplayErrors();
        }

        parent::initContent();
    }
    
    private function tryDisplayErrors (): void {
        if (!isset($this->context->cookie->captcha_error)) {
            return;
        }
        
        $this->context->controller->errors[] = Module::getInstanceByName('recaptcha')->getChallengeFailText();
        unset($this->context->cookie->captcha_error);
    }

}