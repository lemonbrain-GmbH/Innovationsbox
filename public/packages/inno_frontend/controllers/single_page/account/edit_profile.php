<?php

declare(strict_types=1);

namespace Concrete\Package\InnoFrontend\Controller\SinglePage\Account;

use Concrete\Core\Attribute\Category\CategoryService;
use Concrete\Core\Attribute\Context\FrontendFormContext;
use Concrete\Core\Attribute\Form\Renderer;
use Concrete\Core\Attribute\Key\UserKey as UserAttributeKey;
use Concrete\Core\Authentication\AuthenticationType;
use Concrete\Core\Authentication\AuthenticationTypeFailureException;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Localization\Localization;
use Concrete\Core\Page\Controller\PageController;
use Concrete\Core\User\User;
use Exception;
use Loader;

class EditProfile extends PageController
{
    public $helpers = ['form', 'date'];

    protected $displayUserName = true;

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

    public function view()
    {
        $this->requireAsset('inno_frontend/edit-profile');

        $profile = $this->get('profile');
        if (!is_object($profile)) {
            throw new UserMessageException(t('You must be logged in to access this page.'));
        }

        $displayUserName = $this->displayUserName
            ? $this->app->make(Repository::class)->get('concrete.user.edit_profile.display_username_field')
            : false;
        $this->set('displayUserName', $displayUserName);

        $locales = [];
        $languages = Localization::getAvailableInterfaceLanguages();
        if (count($languages) > 0) {
            array_unshift($languages, Localization::BASE_LOCALE);
        }
        if (count($languages) > 0) {
            foreach ($languages as $lang) {
                $locales[$lang] = \Punic\Language::getName($lang, $lang);
            }
            asort($locales);
            $locales = array_merge(['' => tc('Default locale', '** Default')], $locales);
        }
        $this->set('locales', $locales);

        $service = $this->app->make(CategoryService::class);
        $categoryEntity = $service->getByHandle('user');
        $category = $categoryEntity->getController();
        $setManager = $category->getSetManager();
        $attributeSets = [];
        foreach ($setManager->getAttributeSets() as $set) {
            foreach ($set->getAttributeKeys() as $ak) {
                if ($ak->isAttributeKeyEditableOnProfile()) {
                    $attributeSets[$set->getAttributeSetDisplayName()][] = $ak;
                }
            }
        }
        $this->set('attributeSets', $attributeSets);
        $unassignedAttributes = [];
        foreach ($setManager->getUnassignedAttributeKeys() as $ak) {
            if ($ak->isAttributeKeyEditableOnProfile()) {
                $unassignedAttributes[] = $ak;
            }
        }
        $this->set('unassignedAttributes', $unassignedAttributes);
    }

    public function save_complete()
    {
        $this->set('success', t('Profile updated successfully.'));
        $this->view();
    }

    public function callback($type, $method = 'callback')
    {
        $at = AuthenticationType::getByHandle($type);
        $this->view();
        if (!method_exists($at->controller, $method)) {
            throw new UserMessageException('Invalid method.');
        }
        if ($method != 'callback') {
            if (!is_array($at->controller->apiMethods) || !in_array($method, $at->controller->apiMethods)) {
                throw new UserMessageException('Invalid method.');
            }
        }
        try {
            $message = call_user_func([$at->controller, $method]);
            if (trim($message)) {
                $this->set('message', $message);
            }
        } catch (Exception $e) {
            if ($e instanceof AuthenticationTypeFailureException) {
                throw $e;
            }
            $this->error->add($e->getMessage());
        }
    }

    public function save()
    {
        $this->view();
        $ui = $this->get('profile');

        $app = $this->app;
        $valt = $app->make('token');
        $data = $this->post();

        if (!$valt->validate('profile_edit')) {
            $this->error->add($valt->getErrorMessage());
        }

        $email = $this->post('uEmail');
        $app->make('validator/user/email')->isValidFor($email, $ui, $this->error);

        $username = $this->post('uName');
        if (array_key_exists('uName', $data)) {
            $data['uName'] = (string) $data['uName'];
            $app->make('validator/user/name')->isValidFor($username, $ui, $this->error);
        }

        if (strlen($data['uPasswordNew'])) {
            $passwordCurrent = (string) $data['uPasswordCurrent'];
            $passwordNew = $data['uPasswordNew'];
            $passwordNewConfirm = $data['uPasswordNewConfirm'];

            $app->make('validator/password')->isValidFor($passwordNew, $ui, $this->error);

            if ($passwordNew) {
                if (!$ui->passwordMatches($passwordCurrent)) {
                    $this->error->add(t('Invalid existing password provided.'));
                }
                if ($passwordNew != $passwordNewConfirm) {
                    $this->error->add(t('The two passwords provided do not match.'));
                }
            }
            $data['uPasswordConfirm'] = $passwordNew;
            $data['uPassword'] = $passwordNew;
        }

        $aks = UserAttributeKey::getEditableInProfileList();

        foreach ($aks as $uak) {
            $controller = $uak->getController();
            $validator = $controller->getValidator();
            $response = $validator->validateSaveValueRequest($controller, $this->request, $uak->isAttributeKeyRequiredOnProfile());
            if (!$response->isValid()) {
                $error = $response->getErrorObject();
                $this->error->add($error);
            }
        }

        if (!$this->error->has()) {
            $data['uEmail'] = $email;
            $config = $this->app->make('config');
            if ($config->get('concrete.misc.user_timezones')) {
                $data['uTimezone'] = $this->post('uTimezone');
            }

            $ui->saveUserAttributesForm($aks);
            $ui->update($data);
            $this->redirect('/account/edit_profile', 'save_complete');
        }
    }
}
