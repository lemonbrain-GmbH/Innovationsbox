<?php

declare(strict_types=1);

namespace Concrete\Package\InnoFrontend\Controller\SinglePage\Members;

use Concrete\Core\Attribute\Key\UserKey as UserAttributeKey;
use Concrete\Core\Page\Controller\PublicProfilePageController;
use Concrete\Core\User\User;
use Concrete\Core\User\UserList;

class Directory extends PublicProfilePageController
{
    protected UserList $userList;

    public function on_start()
    {
        parent::on_start();
        $this->requireAsset('css', 'core/frontend/pagination');

        $this->userList = new UserList();
        $this->userList->sortByUserID();
        $this->userList->filterByGroup('Experte');

        $u = $this->app->make(User::class);
        $this->set('isLoggedIn', $u->isRegistered());
    }

    public function on_before_render()
    {
        $pagination = $this->userList->getPagination();
        $users = $pagination->getCurrentPageResults();

        $this->set('userList', $this->userList);
        $this->set('users', $users);
        $this->set('total', $pagination->getTotalResults());
        $this->set('attribs', UserAttributeKey::getMemberListList());
        $this->set('keywords', isset($_REQUEST['keywords']) ? h($_REQUEST['keywords']) : '');
        $this->set('pagination', $pagination);
    }

    public function search_members()
    {
        $keywords = $this->get('keywords');
        if ($keywords != '') {
            // Build a custom keyword filter that searches username, display_name,
            // and expertise_area. CMS's filterByKeywords() only finds attributes
            // marked as "searchable indexed" — our custom attributes may not be.
            // UserList already LEFT JOINs UserSearchIndexAttributes as 'ua'.
            $query = $this->userList->getQueryObject();
            $expr = $query->expr();
            $query->andWhere($expr->orX(
                $expr->like('u.uName', ':keywords'),
                $expr->like('u.uEmail', ':keywords'),
                $expr->like('ua.ak_display_name', ':keywords'),
                $expr->like('ua.ak_expertise_area', ':keywords'),
            ));
            $query->setParameter('keywords', '%' . $keywords . '%');
        }
    }
}
