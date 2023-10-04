<?php
/*
 * Copyright (c) 2023 ItexPay
 *
 * Author: Marc Donald AHOURE
 * Email: dmcorporation2014@gmail.com
 *
 * Released under the GNU General Public License
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if ( ! defined('_PS_VERSION_')) {
    exit;
}

class Itexpay extends PaymentModule
{

    const MODULES_ITEXPAY_ADMIN          = 'Modules.Itexpay.Admin';
    const MODULES_CHECKPAYMENT_ADMIN = 'Modules.Checkpayment.Admin';
    private $_postErrors = array();

    public function __construct()
    {
        $this->name        = 'itexpay';
        $this->tab         = 'payments_gateways';
        $this->version     = '1.0.0';
        $this->author      = 'ITEXPAY';
        $this->controllers = array('payment', 'validation');

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName            = $this->trans('ITEXPAY', array(), self::MODULES_ITEXPAY_ADMIN);
        $this->description            = $this->trans(
            'Accept payments with Visa/ MasterCard / Verve, QR Code , Bank Transfer, E-Naira via ITEXPAY Checkout.',
            array(),
            self::MODULES_ITEXPAY_ADMIN
        );
        $this->confirmUninstall       = $this->trans(
            'Are you sure you want to delete your details ?',
            array(),
            self::MODULES_ITEXPAY_ADMIN
        );
        $this->ps_versions_compliancy = array('min' => '1.7.1.0', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        return parent::install()
               && $this->registerHook('paymentOptions')
               && $this->registerHook('paymentReturn');
    }

    public function hookPaymentOptions($params)
    {
        if ( ! $this->active) {
            return [];
        }

        $paymentOption = new PaymentOption();
        $paymentOption->setModuleName($this->name)
                      ->setCallToActionText($this->trans('ITEXPAY', array(), self::MODULES_ITEXPAY_ADMIN))
                      ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/logo3.png'))
                      ->setAction($this->context->link->getModuleLink($this->name, 'payment', array(), true));

        return [$paymentOption];
    }

    public function getContent()
    {
        $this->_html = '';

        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if ( ! count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        }

        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->trans('Settings', array(), self::MODULES_ITEXPAY_ADMIN),
                    'icon'  => 'icon-gear',
                ),
                'input'  => array(
                    array(
                        'type'     => 'text',
                        'label'    => $this->trans('Public Key', array(), self::MODULES_CHECKPAYMENT_ADMIN),
                        'name'     => 'ITEXPAY_PUBLIC_KEY',
                        'required' => true,
                    ),
                   
                    array(
                        'type'   => 'switch',
                        'label'  => $this->trans('Test Mode', array(), self::MODULES_ITEXPAY_ADMIN),
                        'name'   => 'ITEXPAY_ENVIRONMENT',
                        'values' => array(
                            array(
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->trans('Yes', array(), self::MODULES_ITEXPAY_ADMIN),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->trans('No', array(), self::MODULES_ITEXPAY_ADMIN),
                            ),
                        ),
                    ),
                    
                ),
                'submit' => array(
                    'title' => $this->trans('Save', array(), 'Admin.Actions'),
                ),
            ),
        );

        $helper                = new HelperForm();
        $helper->show_toolbar  = false;
        $helper->identifier    = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex  = $this->context->link->getAdminLink(
                'AdminModules',
                false
            ) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token         = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars      = array(
            'fields_value' => $this->getConfigFieldsValues(),
        );

        $this->fields_form = array();

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        return array(
            'ITEXPAY_PUBLIC_KEY' => Tools::getValue('ITEXPAY_PUBLIC_KEY', Configuration::get('ITEXPAY_PUBLIC_KEY')),
            'ITEXPAY_ENVIRONMENT'      => Tools::getValue('ITEXPAY_ENVIRONMENT', Configuration::get('ITEXPAY_ENVIRONMENT')),
            
        );
    }

  

    private function _postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if ( ! Tools::getValue('ITEXPAY_PUBLIC_KEY')) {
                $this->_postErrors[] = $this->trans(
                    'The "Public Key" field is required.',
                    array(),
                    self::MODULES_CHECKPAYMENT_ADMIN
                );
            } 
        }
    }

    private function _postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('ITEXPAY_PUBLIC_KEY', Tools::getValue('ITEXPAY_PUBLIC_KEY'));
            Configuration::updateValue('ITEXPAY_ENVIRONMENT', Tools::getValue('ITEXPAY_ENVIRONMENT'));
            
        }
        $this->_html .= $this->displayConfirmation(
            $this->trans('Settings updated', array(), 'Admin.Notifications.Success')
        );
    }

}
