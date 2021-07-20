<?php

namespace Symbiote\ApiWrapper;

use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

/**
 * Used to authenticate tokens and set the user into the session
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class TokenAuthenticator
{

    /**
     * @param String $token
     */
    public function authenticate($token)
    {
        list($uid, $token) = explode(':', $token, 2);
        // done directly against the DB because we don't have a user context yet
        /**
         * @var Member
         */
        $user = Member::get()->byID($uid);
        if ($user && $user->exists()) {
            $hash = $user->encryptWithUserSettings($token);
            // we're not comparing against the RawToken because we want the 'slow' process above to execute
            if ($hash == $user->Token) {
                $this->loginUser($user);
                return $user;
            }
        }
    }

    /**
     * Log a user in for this request
     *
     * @param Member $member
     */
    protected function loginUser($member)
    {
        Security::setCurrentUser($member);
    }
}
