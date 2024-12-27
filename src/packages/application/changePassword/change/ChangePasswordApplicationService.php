<?php

namespace packages\application\changePassword\change;

use DateTimeImmutable;
use packages\domain\model\authenticationAccount\IAuthenticationAccountRepository;
use packages\domain\model\authenticationAccount\UnsubscribeStatus;
use packages\domain\model\authenticationAccount\UserPassword;
use packages\domain\model\authenticationAccount\validation\UserPasswordValidation;
use packages\domain\model\oauth\authToken\AccessToken;
use packages\domain\model\oauth\client\ClientId;
use packages\domain\model\oauth\client\IClientFetcher;
use packages\domain\model\oauth\client\RedirectUrl;
use packages\domain\model\oauth\scope\IScopeAuthorizationChecker;
use packages\domain\model\oauth\scope\Scope;
use packages\domain\model\oauth\scope\ScopeList;
use packages\domain\service\authenticationAccount\AuthenticationService;
use packages\domain\service\oauth\ClientService;
use packages\domain\service\oauth\ILoggedInUserIdFetcher;
use RuntimeException;

class ChangePasswordApplicationService implements ChangePasswordApplicationInputBoundary
{
    private IAuthenticationAccountRepository $authenticationAccountRepository;
    private ILoggedInUserIdFetcher $loggedInUserIdFetcher;
    private ClientService $clientService;

    public function __construct(
        IAuthenticationAccountRepository $authenticationAccountRepository,
        IClientFetcher $clientFetcher,
        ILoggedInUserIdFetcher $loggedInUserIdFetcher
    ) {
        $this->authenticationAccountRepository = $authenticationAccountRepository;
        $this->clientService = new ClientService($clientFetcher);
        $this->loggedInUserIdFetcher = $loggedInUserIdFetcher;
    }

    public function changePassword(
        string $scopeString,
        string $passwordString,
        string $clientId,
        string $redirectUrl
    ): ChangePasswordResult
    {
        $scope = Scope::from($scopeString);
        $userId = $this->loggedInUserIdFetcher->fetch($scope);

        if (!$this->clientService->isCorrectRedirectUrl(
            new ClientId($clientId),
            new RedirectUrl($redirectUrl)
        )) {
            throw new RuntimeException('リダイレクトURLが正しくありません');
        }

        $passwordValidation = new UserPasswordValidation($passwordString);
        if (!$passwordValidation->validate()) {
            return ChangePasswordResult::createWhenFaild($passwordValidation->errorMessageList());
        }

        $authAccount = $this->authenticationAccountRepository->findById($userId, UnsubscribeStatus::Subscribed);
        if ($authAccount === null) {
            throw new RuntimeException('ユーザーが見つかりません');
        }

        $password = UserPassword::create($passwordString);
        $authAccount->changePassword($password, new DateTimeImmutable());
        $this->authenticationAccountRepository->save($authAccount);

        // 後でメール送信する処理を追加する

        return ChangePasswordResult::createWhenSuccess($redirectUrl);
    }
}