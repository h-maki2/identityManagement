<?php

namespace packages\domain\model\userProfile;

use DomainException;
use packages\domain\service\userProfile\UserProfileService;

class UserProfile
{
    private UserId $userId;
    private UserEmail $userEmail;
    private UserName $userName;
    private UserPassword $userPassword;
    private VerificationStatus $verificationStatus;

    private function __construct(
        UserId $userId,
        UserEmail $userEmail,
        UserName $userName,
        UserPassword $userPassword,
        VerificationStatus $verificationStatus
    )
    {
        $this->userId = $userId;
        $this->userEmail = $userEmail;
        $this->userName = $userName;
        $this->userPassword = $userPassword;
        $this->verificationStatus = $verificationStatus;
    }

    public static function create(
        UserId $userId,
        UserEmail $userEmail,
        UserPassword $userPassword,
        UserProfileService $userProfileService
    ): self
    {
        $alreadyExistsEmail = $userProfileService->alreadyExistsEmail($userEmail);
        if ($alreadyExistsEmail) {
            throw new DomainException('すでに存在するメールアドレスです。');
        }
        
        return new self(
            $userId,
            $userEmail,
            UserName::initialization($userEmail),
            $userPassword,
            VerificationStatus::Unverified
        );
    }

    public static function reconstruct(
        UserId $userId,
        UserEmail $userEmail,
        UserName $userName,
        UserPassword $userPassword,
        VerificationStatus $verificationStatus
    ): self
    {
        return new self(
            $userId,
            $userEmail,
            $userName,
            $userPassword,
            $verificationStatus
        );
    }

    public function id(): UserId
    {
        return $this->userId;
    }

    public function email(): UserEmail
    {
        return $this->userEmail;
    }

    public function name(): UserName
    {
        return $this->userName;
    }

    public function password(): UserPassword
    {
        return $this->userPassword;
    }

    public function verificationStatus(): VerificationStatus
    {
        return $this->verificationStatus;
    }

    public function updateVerified(): void
    {
        $this->verificationStatus = VerificationStatus::Verified;
    }

    public function changeName(UserName $name): void
    {
        if (!$this->verificationStatus->isVerified()) {
            throw new DomainException('認証済みのユーザーではありません。');
        }

        $this->userName = $name;
    }

    public function changePassword(UserPassword $password): void
    {
        if (!$this->verificationStatus->isVerified()) {
            throw new DomainException('認証済みのユーザーではありません。');
        }

        $this->userPassword = $password;
    }
}