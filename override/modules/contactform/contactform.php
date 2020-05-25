<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

$PATH_TO_MODULE = $_SERVER['DOCUMENT_ROOT'] . '/modules/recaptcha';

require_once $PATH_TO_MODULE . '/RecaptchaHelper.php';
require_once $PATH_TO_MODULE . '/CaptchaEnabled.php';

class ContactformOverride extends Contactform {

    public function __construct () {
        $this->recaptcha = Module::getInstanceByName('recaptcha');
        parent::__construct();
    }

    public function renderWidget ($hookName = null, array $configuration = []): string {
        if (CaptchaEnabled::contact()) {
            echo(RecaptchaHelper::getHtml());
        }

        return parent::renderWidget($hookName, $configuration);
    }

    public function sendMessage (): void {
        if (!RecaptchaHelper::check() && CaptchaEnabled::contact()) {
            $this->context->controller->errors[] = $this->recaptcha->getChallengeFailText();
        } else {
            parent::sendMessage();
        }
    }

}