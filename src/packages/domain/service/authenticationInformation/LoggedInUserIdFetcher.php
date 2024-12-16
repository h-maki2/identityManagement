<?php

namespace packages\domain\service\authenticationInformation;

use packages\domain\model\authenticationInformation\AuthenticationService;
use packages\domain\model\authenticationInformation\UserId;
use packages\domain\model\common\exception\AuthenticationException;
use packages\domain\model\oauth\scope\IScopeAuthorizationChecker;
use packages\domain\model\oauth\scope\Scope;

/**
 * ログイン済みのユーザーIDを取得する
 */
class LoggedInUserIdFetcher
{
    private AuthenticationService $authService;
    private IScopeAuthorizationChecker $scopeAuthorizationChecker;

    public function __construct(
        AuthenticationService $authService,
        IScopeAuthorizationChecker $scopeAuthorizationChecker
    )
    {
        $this->authService = $authService;
        $this->scopeAuthorizationChecker = $scopeAuthorizationChecker;
    }

    public function fetch(Scope $scope): UserId
    {
        if (!$this->scopeAuthorizationChecker->isAuthorized($scope)) {
            throw new AuthenticationException('許可されていないリクエストです。');
        }

        $userId = $this->authService->loggedInUserId();
        if ($userId === null) {
            throw new AuthenticationException('ユーザーがログインしていません');
        }

        return $userId;
    }
}