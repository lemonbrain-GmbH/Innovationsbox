<?php

declare(strict_types=1);

namespace Concrete\Package\InnoFrontend\Controller\SinglePage\Account;

use Concrete\Core\Feature\Features;
use Concrete\Core\Feature\UsesFeatureInterface;
use Concrete\Core\Page\Controller\PageController;
use Concrete\Core\User\Command\UpdateUserAvatarCommand;
use Concrete\Core\User\Component\AvatarCropperInstanceFactory;
use Concrete\Core\User\User;
use Concrete\Core\User\UserInfo;
use Loader;
use Symfony\Component\HttpFoundation\JsonResponse;

class Avatar extends PageController implements UsesFeatureInterface
{
    public function getRequiredFeatures(): array
    {
        return [Features::ACCOUNT];
    }
    public function on_start()
    {
        $u = $this->app->make(User::class);
        if (!$u->isRegistered()) {
            return $this->replace('/login');
        }

        $profile = \UserInfo::getByID($u->getUserID());

        $this->error = Loader::helper('validation/error');
        $this->token = Loader::helper('validation/token');
        $this->set('profile', $profile);
        $this->set('hasAvatar', $profile->hasAvatar());
    }

    public function on_before_render()
    {
        $this->set('error', $this->error);
    }

    public function view()
    {
        // The CMS avatar cropper (feature/account/frontend.js) uses jQuery for
        // document-ready and Concrete.Vue (from cms.js) to register the Vue component.
        // Without these, the <avatar-cropper> tag renders as an empty custom element.
        $this->requireAsset('javascript', 'jquery');
        $this->requireAsset('javascript', 'vue');
        $this->requireAsset('javascript', 'core/cms');

        $profile = $this->get('profile');
        $instanceFactory = $this->app->make(AvatarCropperInstanceFactory::class);
        $instance = $instanceFactory->createInstance();
        $instance->setUploadUrl($this->action('save_avatar'));
        $this->set('avatarCropperInstance', $instance);
        $this->set('width', $this->app->make('config')->get('concrete.icons.user_avatar.width'));
        $this->set('height', $this->app->make('config')->get('concrete.icons.user_avatar.height'));
    }

    public function save_avatar()
    {
        $result = [
            'success' => false,
            'avatar' => null,
        ];

        $instanceFactory = $this->app->make(AvatarCropperInstanceFactory::class);
        $instance = $instanceFactory->createInstanceFromRequest($this->request);
        if (!$instanceFactory->instanceMatchesAccessToken($instance, $this->request->get('accessToken') ?? '')) {
            $result['error'] = true;
            $result['message'] = app('token')->getErrorMessage();
            return new JsonResponse($result, 400);
        }

        /** @var UserInfo */
        $profile = $this->get('profile');

        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile */
        $file = $this->request->files->get('file');

        if ($file) {
            try {
                $command = new UpdateUserAvatarCommand($profile, $file);
                $this->app->executeCommand($command);

                $result['success'] = true;
                $result['avatar'] = $profile->getUserAvatar()->getPath() . '?' . time();
            } catch (\Throwable $e) {
                $result['error'] = true;
                $result['message'] = t('Error saving avatar: %s', $e->getMessage());
            }
        } else {
            $result['error'] = true;
            $result['message'] = t('Error while uploading file.');
        }

        return new JsonResponse($result, $result['success'] ? 200 : 400);
    }

    public function saved()
    {
        $this->set('success', 'Avatar updated!');
        $this->view();
    }

    public function deleted()
    {
        $this->set('success', 'Avatar removed.');
        $this->view();
    }

    public function delete()
    {
        $this->view();
        if (!$this->token->validate('delete_avatar')) {
            $this->error->add($this->token->getErrorMessage());
        }
        if (!$this->error->has()) {
            $profile = $this->get('profile');
            $service = $this->app->make('user/avatar');
            $service->removeAvatar($profile);
            $this->redirect('/account/avatar', 'deleted');
        }
    }
}
