<?php
/**
 * 2017 Thirty Bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    Thirty Bees <modules@thirtybees.com>
 *  @copyright 2017 Thirty Bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/lib/autoload.php';

class MailChimp extends Module
{
    const KEY_API_KEY = 'MAILCHIMP_API_KEY';
    const KEY_CONFIRMATION_EMAIL = 'MAILCHIMP_CONFIRMATION_EMAIL';
    const KEY_UPDATE_EXISTING = 'MAILCHIMP_UPDATE_EXISTING';
    const KEY_IMPORT_ALL = 'MAILCHIMP_IMPORT_ALL';

    private $_html = '';

    public function __construct()
    {
        $this->name = 'mailchimp';
        $this->tab = 'advertising_marketing';
        $this->version = '1.0.0';
        $this->author = 'Thirty Bees';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = array(
            'min' => '1.5',
            'max' => _PS_VERSION_,
        );

        parent::__construct();

        $this->displayName = $this->l('MailChimp');
        $this->description = $this->l('Synchronize with MailChimp');
    }

    public function install()
    {
        if (
            !parent::install()
            || !$this->registerHook('displayHome')
            || !$this->registerHook('displayHeader')
            || !$this->registerHook('displayBackOfficeHeader')
            || !$this->registerHook('displayAdminHomeQuickLinks')
        ) {
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        if (
            !parent::uninstall()
            || !Configuration::deleteByName('KEY_API_KEY')
            || !Configuration::deleteByName('KEY_CONFIRMATION_EMAIL')
            || !Configuration::deleteByName('KEY_UPDATE_EXISTING')
            || !Configuration::deleteByName('KEY_IMPORT_ALL')

        ) {
            return false;
        }
        return true;
    }

    public function getContent()
    {
        $this->_postProcess();
        $this->_displayForm();
        return $this->_html;
    }

    private function _postProcess()
    {
        if (Tools::isSubmit('submitApiKey')) {
            // check if API key is valid
            try {
                $mailchimp = new \ThirtyBees\MailChimp\MailChimp(Tools::getValue('mailchimpApiKey'));
                $update = Configuration::updateValue('KEY_API_KEY', Tools::getValue('mailchimpApiKey'));
                if ($update) {
                    $this->_html .= $this->displayConfirmation($this->l('You have successfully updated your MailChimp API key.'));
                } else {
                    $this->_html .= $this->displayError($this->l('An error occurred while saving API key.'));
                }
            } catch (Exception $e) {
                // remove existing value
                Configuration::deleteByName('KEY_API_KEY');
                $this->_html .= $this->displayError($e->getMessage());
            }
        } else if (Tools::isSubmit('submitSettings')) {
            $update1 = Configuration::updateValue('KEY_CONFIRMATION_EMAIL', Tools::getValue('confirmationEmail'));
            $update2 = Configuration::updateValue('KEY_UPDATE_EXISTING', Tools::getValue('updateExisting'));
            $update3 = Configuration::updateValue('KEY_IMPORT_ALL', Tools::getValue('importAll'));
            if ($update1 && $update2 && $update3) {
                $this->_html .= $this->displayConfirmation($this->l('Settings updated.'));
            } else {
                $this->_html .= $this->displayError($this->l('Some of the settings could not be saved.'));
            }
        }
    }

    private function _displayForm()
    {
        $this->_html .= $this->_generateForm();
    }

    private function _generateForm()
    {
        $fields = array();

        $inputs1 = array();

        $inputs1[] = array(
            'type' => 'text',
            'label' => $this->l('API Key'),
            'name' => 'mailchimpApiKey',
            'desc' => $this->l('Please enter your MailChimp API key. This can be found in your MailChimp Dashboard -> Account -> Extras -> API keys.'),
        );

        $fieldsForm1 = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('API Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => $inputs1,
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submitApiKey',
                ),
            ),
        );

        $fields[] = $fieldsForm1;

        // show settings form only if api key is set and working
        $apiKey = Configuration::get('KEY_API_KEY');
        if (isset($apiKey) && $apiKey != '') {

            $inputs2 = array();

            $inputs2[] = array(
                'type' => 'switch',
                'label' => $this->l('Confirmation Email'),
                'name' => 'confirmationEmail',
                'desc' => $this->l('Mailchimp can send a confirmation email after import to the customers, turn on if you wish to inform your customers about the subscription.'),
                'values' => array(
                    array(
                        'id' => 'switch_on',
                        'value' => 1,
                        'label' => $this->l('Enabled'),
                    ),
                    array(
                        'id' => 'switch_off',
                        'value' => 0,
                        'label' => $this->l('Disabled'),
                    ),
                ),
            );

            $inputs2[] = array(
                'type' => 'switch',
                'label' => $this->l('Update if exists'),
                'name' => 'updateExisting',
                'desc' => $this->l('Do you wish to update the subscribers details if they alredy exists?'),
                'values' => array(
                    array(
                        'id' => 'switch_on',
                        'value' => 1,
                        'label' => $this->l('Enabled'),
                    ),
                    array(
                        'id' => 'switch_off',
                        'value' => 0,
                        'label' => $this->l('Disabled'),
                    ),
                ),
            );

            $inputs2[] = array(
                'type' => 'switch',
                'label' => $this->l('Import All Customers'),
                'name' => 'importAll',
                'desc' => $this->l('Turn this on if you wish to import all of the users. This means that the module ignores the customers newsletter option.'),
                'values' => array(
                    array(
                        'id' => 'switch_on',
                        'value' => 1,
                        'label' => $this->l('Enabled'),
                    ),
                    array(
                        'id' => 'switch_off',
                        'value' => 0,
                        'label' => $this->l('Disabled'),
                    ),
                ),
            );

            $fieldsForm2 = array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Import Settings'),
                        'icon' => 'icon-cogs',
                    ),
                    'input' => $inputs2,
                    'submit' => array(
                        'title' => $this->l('Save'),
                        'class' => 'btn btn-default pull-right',
                        'name' => 'submitSettings',
                    ),
                ),
            );

            $fields[] = $fieldsForm2;
        }

        $helper = new HelperForm();
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->_getConfigFieldsValues(),
        );
        return $helper->generateForm($fields);
    }

    private function _getConfigFieldsValues()
    {
        return array(
            'mailchimpApiKey' => Configuration::get('KEY_API_KEY'),
            'confirmationEmail' => Configuration::get('KEY_CONFIRMATION_EMAIL'),
            'updateExisting' => Configuration::get('KEY_UPDATE_EXISTING'),
            'importAll' => Configuration::get('KEY_IMPORT_ALL'),
        );
    }
}