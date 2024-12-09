<?php

namespace packages\domain\model\authConfirmation;

use DateTimeImmutable;
use InvalidArgumentException;
use packages\domain\model\authenticationInformation\UserId;
use packages\domain\service\authConfirmation\AuthConfirmationService;

class AuthConfirmation
{
    readonly UserId $userId;
    private OneTimeToken $oneTimeToken;
    private OneTimePassword $oneTimePassword;

    private function __construct(
        UserId $userId, 
        OneTimeToken $oneTimeToken, 
        OneTimePassword $oneTimePassword
    )
    {
        $this->userId = $userId;
        $this->oneTimeToken = $oneTimeToken;
        $this->oneTimePassword = $oneTimePassword;
    }

    public static function create(
        UserId $userId, 
        OneTimeToken $oneTimeToken,
        AuthConfirmationService $authConfirmationService
    ): self
    {
        if ($authConfirmationService->isExistsOneTimeToken($oneTimeToken->tokenValue())) {
            throw new InvalidArgumentException('OneTimeToken is already exists.');
        }

        return new self(
            $userId,
            $oneTimeToken,
            OneTimePassword::create()
        );
    }

    public static function reconstruct(
        UserId $userId, 
        OneTimeToken $oneTimeToken, 
        OneTimePassword $oneTimePassword
    ): self
    {
        return new self($userId, $oneTimeToken, $oneTimePassword);
    }

    public function oneTimeToken(): OneTimeToken
    {
        return $this->oneTimeToken;
    }

    public function oneTimePassword(): OneTimePassword
    {
        return $this->oneTimePassword;
    }

    /**
     * 認証確認の再取得を行う
     * ワンタイムトークンとワンタイムパスワードを再生成する
     */
    public function reObtain(): void
    {
        $this->oneTimeToken = OneTimeToken::create();
        $this->oneTimePassword = OneTimePassword::create();
    }

    /**
     * 有効期限切れかどうかを判定
     */
    public function isExpired(DateTimeImmutable $currentDateTime): bool
    {
        return $this->oneTimeToken->isExpired($currentDateTime);
    }
}