<?php

declare(strict_types=1);

namespace Concrete\Package\InnoFrontend\Controller\SinglePage\Members;

use Concrete\Controller\SinglePage\Members\Profile as CoreProfile;
use Concrete\Core\User\User;

class Profile extends CoreProfile
{
    public function view($userID = 0): void
    {
        $u = $this->app->make(User::class);

        if (!$u->isRegistered()) {
            $this->set('intro_msg', t('You must sign in order to access this page!'));
            $this->replace('/login');

            return;
        }

        $this->requireAsset('javascript', 'spa-util-mailAddr');

        parent::view($userID);
    }
}
