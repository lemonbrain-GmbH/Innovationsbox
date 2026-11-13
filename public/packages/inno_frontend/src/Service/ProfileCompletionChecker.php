<?php

declare(strict_types=1);

namespace Concrete\Package\InnoFrontend\Service;

use Concrete\Core\Attribute\Key\UserKey;
use Concrete\Core\User\UserInfo;

class ProfileCompletionChecker
{
    private const ALLOWED_PREFIXES = ['/account', '/login', '/register', '/dashboard'];

    public function needsCompletion(UserInfo $userInfo, string $pagePath): bool
    {
        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if ($pagePath !== '' && str_starts_with($pagePath, $prefix)) {
                return false;
            }
        }

        $aks = UserKey::getRegistrationList();
        foreach ($aks as $ak) {
            if (($ak->isAttributeKeyRequiredOnRegister() || $ak->isAttributeKeyRequiredOnProfile())
                && empty($userInfo->getAttribute($ak))) {
                return true;
            }
        }

        return false;
    }
}
