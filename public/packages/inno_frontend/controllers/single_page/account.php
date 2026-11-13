<?php

declare(strict_types=1);

namespace Concrete\Package\InnoFrontend\Controller\SinglePage;

use Concrete\Core\Attribute\Context\FrontendFormContext;
use Concrete\Core\Attribute\Form\Renderer;
use Concrete\Core\Page\Controller\PageController;
use Concrete\Core\User\User;
use Loader;

class Account extends PageController
{
    public function on_start()
    {
        $u = $this->app->make(User::class);
        if (!$u->isRegistered()) {
            return $this->replace('/login');
        }

        $profile = \UserInfo::getByID($u->getUserID());

        $profileFormRenderer = new Renderer(
            new FrontendFormContext(),
            $profile
        );

        $this->set('profileFormRenderer', $profileFormRenderer);
        $this->error = Loader::helper('validation/error');
        $this->token = Loader::helper('validation/token');
        $this->set('valt', $this->token);
        $this->set('av', Loader::helper('concrete/avatar'));
        $this->set('profile', $profile);
    }

    public function on_before_render()
    {
        $this->set('error', $this->error);
    }
}
