<?php

require_once __DIR__ . '/RecaptchaHelper.php';
require_once __DIR__ . '/CaptchaEnabled.php';
require_once __DIR__ . '/ConfigKeys.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class ReCaptcha extends Module {
    
    public function __construct () {
        $this->author = 'Trzebu';
        $this->name = 'recaptcha';
        $this->tab = 'front_office_features';
        $this->version = '0.3';
        $this->ps_versions_compliancy = ['min' => '1.7.0', 'max' => '1.7.6.5'];
        $this->need_instance = 1;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = 'reCaptcha';
        $this->description = $this->l('Adds a Google recaptcha v2 to contact and register forms.');
    }

    public function install (): bool {
        if (
            !parent::install() ||
            !$this->registerHook('displayCustomerAccountForm') ||
            !Configuration::updateValue(ConfigKeys::CAPTCHA_CONTACT_ENABLED, 0) ||
            !Configuration::updateValue(ConfigKeys::CAPTCHA_REGISTER_ENABLED, 0)
        ) {
            return false;
        }

        return true;
    }

    public function uninstall (): bool {
        if (!parent::uninstall()) {
            return false;
        }

        if (
            !Configuration::deleteByName(ConfigKeys::CAPTCHA_CONTACT_ENABLED) ||
            !Configuration::deleteByName(ConfigKeys::CAPTCHA_REGISTER_ENABLED) ||
            !Configuration::deleteByName(ConfigKeys::CAPTCHA_PRIVATE_KEY) ||
            !Configuration::deleteByName(ConfigKeys::CAPTCHA_SITE_KEY)
        ) {
            return false;
        }

        return true;
    }

    public function hookDisplayCustomerAccountForm ($params): string {
        if ($this->context->customer->isLogged()) {
            return '';
        }

        if (CaptchaEnabled::registration()) {
            return RecaptchaHelper::getHtml();
        }

        return '';
    }

    public function getContent () {
        $html = $this->postProcess();
        $html .= $this->renderForm();

        return $html;
    }

    /**
     * Admin Form for module Configuration
     */
    private function renderForm () {
        $fields = [
            'form' => [
                'legend' => [
                    'title' => $this->l('ReCaptcha configuration'),
                    'icon' => 'icon-cogs'
                ],
                'description' => $this->l('To get your own public and private keys please click on the folowing link').'<br /><a href="https://www.google.com/recaptcha/intro/index.html" target="_blank">https://www.google.com/recaptcha/intro/index.html</a>',
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Captcha public key (Site key)'),
                        'name' => ConfigKeys::CAPTCHA_SITE_KEY,
                        'size'=> 70,
                        'required' => true,
                        'empty_message' => $this->l('Please fill the captcha public key')
                    ], [
                        'type' => 'text',
                        'label' => $this->l('Captcha private key (Secret key)'),
                        'name' => ConfigKeys::CAPTCHA_PRIVATE_KEY,
                        'size'=> 70,
                        'required' => true,
                        'empty_message' => $this->l('Please fill the captcha private key')
                    ], [
                        'type' => 'radio',
                        'label' => $this->l('Enable Captcha for contact form'),
                        'name' => ConfigKeys::CAPTCHA_CONTACT_ENABLED,
                        'required' => true,
                        'class' => 't',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value'=> 1,
                                'label'=> $this->l('Enabled')
                            ], [
                                'id' => 'active_off',
                                'value'=> 0,
                                'label'=> $this->l('Disabled')
                            ]
                        ]
                    ], [
                        'type' => 'radio',
                        'label' => $this->l('Enable Captcha for account creation'),
                        'name' => ConfigKeys::CAPTCHA_REGISTER_ENABLED,
                        'required' => true,
                        'class' => 't',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value'=> 1,
                                'label'=> $this->l('Enabled')
                            ], [
                                'id' => 'active_off',
                                'value'=> 0,
                                'label'=> $this->l('Disabled')
                            ]
                        ]
                    ]
                ],
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'button btn btn-default pull-right',
                )
            ]
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table =  $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->id = (int) Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'SubmitCaptchaConfiguration';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
			'fields_value' => [
                ConfigKeys::CAPTCHA_CONTACT_ENABLED => Configuration::get(ConfigKeys::CAPTCHA_CONTACT_ENABLED),
                ConfigKeys::CAPTCHA_REGISTER_ENABLED => Configuration::get(ConfigKeys::CAPTCHA_REGISTER_ENABLED),
                ConfigKeys::CAPTCHA_PRIVATE_KEY => Tools::getValue(
                    ConfigKeys::CAPTCHA_PRIVATE_KEY, 
                    Configuration::get(ConfigKeys::CAPTCHA_PRIVATE_KEY)
                ),
                ConfigKeys::CAPTCHA_SITE_KEY => Tools::getValue(
                    ConfigKeys::CAPTCHA_SITE_KEY, 
                    Configuration::get(ConfigKeys::CAPTCHA_SITE_KEY)
                ),
            ],
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields));
    }

    /**
     * Post Process in back office
     */
    private function postProcess (): string {
        if (!Tools::isSubmit('SubmitCaptchaConfiguration')) {
            return '';
        }

        if ($this->checkIfAnyKeyIsNotSet()) {
            if ($this->checkIfAnyCaptchaIsEnabled()) {
                $this->context->controller->errors[] = $this->l('You cant enable captcha if you dont have settled any captcha key.');
                return '';
            }
        }

        Configuration::updateValue(
            ConfigKeys::CAPTCHA_CONTACT_ENABLED, 
            Tools::getValue(ConfigKeys::CAPTCHA_CONTACT_ENABLED)
        );
        Configuration::updateValue(
            ConfigKeys::CAPTCHA_REGISTER_ENABLED, 
            Tools::getValue(ConfigKeys::CAPTCHA_REGISTER_ENABLED)
        );
        Configuration::updateValue(
            ConfigKeys::CAPTCHA_PRIVATE_KEY, 
            Tools::getValue(ConfigKeys::CAPTCHA_PRIVATE_KEY)
        );
        Configuration::updateValue(
            ConfigKeys::CAPTCHA_SITE_KEY, 
            Tools::getValue(ConfigKeys::CAPTCHA_SITE_KEY)
        );

        return $this->displayConfirmation($this->l('Settings updated'));
    }

    private function checkIfAnyKeyIsNotSet (): bool {
        if (strlen(
            Tools::getValue(ConfigKeys::CAPTCHA_PRIVATE_KEY)
        ) === 0) return true;

        if (strlen(
            Tools::getValue(ConfigKeys::CAPTCHA_SITE_KEY)
        ) === 0) return true;

        return false;
    }

    private function checkIfAnyCaptchaIsEnabled (): bool {
        if (Tools::getValue(ConfigKeys::CAPTCHA_CONTACT_ENABLED) == 1) {
            return true;
        }

        if (Tools::getValue(ConfigKeys::CAPTCHA_REGISTER_ENABLED) == 1) {
            return true;
        }

        return false;
    }

    public function getChallengeFailText (): string {
        return $this->l('Incorrect response to CAPTCHA challenge. Please try again.');
    }

}