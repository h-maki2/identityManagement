<?php

namespace packages\test\helpers\authenticationInformaion;

use packages\domain\model\authenticationInformaion\LoginRestriction;
use packages\domain\model\authenticationInformaion\IAuthenticationInformaionRepository;
use packages\domain\model\authenticationInformaion\UserEmail;
use packages\domain\model\authenticationInformaion\UserId;
use packages\domain\model\authenticationInformaion\UserName;
use packages\domain\model\authenticationInformaion\UserPassword;
use packages\domain\model\authenticationInformaion\AuthenticationInformaion;
use packages\domain\model\authenticationInformaion\VerificationStatus;

class AuthenticationInformaionTestDataFactory
{
    private IAuthenticationInformaionRepository $authenticationInformaionRepository;

    public function __construct(IAuthenticationInformaionRepository $authenticationInformaionRepository)
    {
        $this->authenticationInformaionRepository = $authenticationInformaionRepository;
    }

    public function create(
        ?UserEmail $email = null,
        ?UserPassword $password = null,
        ?VerificationStatus $verificationStatus = null,
        ?UserId $id = null,
        ?LoginRestriction $LoginRestriction = null
    ): AuthenticationInformaion
    {
        $authenticationInformaion = TestAuthenticationInformaionFactory::create(
            $email,
            $password,
            $verificationStatus,
            $id,
            $LoginRestriction
        );

        $this->authenticationInformaionRepository->save($authenticationInformaion);

        return $authenticationInformaion;
    }
}