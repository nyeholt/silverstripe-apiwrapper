<?php

namespace Symbiote\ApiWrapper;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\RandomGenerator;

class TokenAccessible extends DataExtension
{
    private $authToken = null;

    private static $db = [
        'Token'                 => 'Varchar(128)',
        'RegenerateTokens'      => 'Boolean',
    ];

    public function onBeforeWrite()
    {
        if (!$this->owner->Token) {
            $this->owner->RegenerateTokens = true;
        }

        if ($this->owner->RegenerateTokens) {
            $this->owner->RegenerateTokens = false;
            $this->generateTokens();
        }
    }

    public function updateCMSFields(FieldList $fields)
    {
        parent::updateCMSFields($fields);

        $token = $this->userToken();

        if (!$token) {
            $token = "This user token can no longer be displayed - if you do not know this value, regenerate tokens by selecting Regenerate below";
        } else {
            $token = $this->owner->ID . ':' . $token;
        }

        $readOnly = ReadonlyField::create('DisplayToken', 'Token', $token);
        $fields->removeByName('Token');
        $fields->addFieldToTab('Root.Main', $readOnly, 'AuthPrivateKey');

        $fields->insertAfter('AuthPrivateKey', $fields->dataFieldByName('RegenerateTokens'));

        $fields->removeByName('Token');
    }

    public function onAfterWrite()
    {
        if ($this->authToken) {
            // store the new token so it can be displayed later
            Controller::curr()->getRequest()->getSession()->set('member_auth_token_' . $this->owner->ID, $this->authToken);
        }
    }

    /**
     * Generate and store the authentication tokens required
     *
     * @TODO Rework this, it's not really any better than storing text passwords
     */
    public function generateTokens()
    {
        $generator = new RandomGenerator();
        $token = $generator->randomToken('sha1');
        $this->owner->Token = $this->owner->encryptWithUserSettings($token);
        $this->authToken = $token;
    }

    public function userToken()
    {
        return Controller::has_curr() ? Controller::curr()->getRequest()->getSession()->get('member_auth_token_' . $this->owner->ID) : null;
    }
}
