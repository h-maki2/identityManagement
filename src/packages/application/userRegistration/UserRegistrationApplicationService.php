<?php

namespace packages\application\userRegistration;

use Exception;
use packages\domain\model\authConfirmation\AuthConfirmation;
use packages\domain\model\authConfirmation\IAuthConfirmationRepository;
use packages\domain\model\authenticationInformaion\AuthenticationInformaion;
use packages\domain\model\authenticationInformaion\IAuthenticationInformaionRepository;
use packages\domain\model\authenticationInformaion\UserEmail;
use packages\domain\model\authenticationInformaion\UserPassword;
use packages\domain\model\authenticationInformaion\validation\UserEmailValidation;
use packages\domain\model\authenticationInformaion\validation\UserPasswordValidation;
use packages\domain\model\common\unitOfWork\UnitOfWork;
use packages\domain\model\common\validator\ValidationHandler;
use packages\domain\service\authenticationInformaion\AuthenticationInformaionService;

class UserRegistrationApplicationService
{
    private IAuthConfirmationRepository $authConfirmationRepository;
    private IAuthenticationInformaionRepository $authenticationInformaionRepository;
    private AuthenticationInformaionService $authenticationInformaionService;
    private UnitOfWork $unitOfWork;
    private IUserRegistrationCompletionEmail $userRegistrationCompletionEmail;

    public function __construct(
        IAuthConfirmationRepository $authConfirmationRepository,
        IAuthenticationInformaionRepository $authenticationInformaionRepository,
        UnitOfWork $unitOfWork,
        IUserRegistrationCompletionEmail $userRegistrationCompletionEmail
    )
    {
        $this->authConfirmationRepository = $authConfirmationRepository;
        $this->authenticationInformaionRepository = $authenticationInformaionRepository;
        $this->unitOfWork = $unitOfWork;
        $this->authenticationInformaionService = new AuthenticationInformaionService($authenticationInformaionRepository);
        $this->userRegistrationCompletionEmail = $userRegistrationCompletionEmail;
    }

    public function userRegister(
        string $inputedEmail, 
        string $inputedPassword
    ): UserRegistrationResult
    {
        $validationHandler = new ValidationHandler();
        $validationHandler->addValidator(new UserEmailValidation($inputedEmail, $this->authenticationInformaionRepository));
        $validationHandler->addValidator(new UserPasswordValidation($inputedPassword));
        if (!$validationHandler->validate()) {
            return UserRegistrationResult::createWhenValidationError($validationHandler->errorMessages());
        }

        $userEmail = new UserEmail($inputedEmail);
        $userPassword = new UserPassword($inputedPassword);
        $authInformation = AuthenticationInformaion::create(
            $this->authenticationInformaionRepository->nextUserId(),
            $userEmail,
            $userPassword,
            $this->authenticationInformaionService
        );

        $authConfirmation = AuthConfirmation::create($authInformation->id());

        try {
            $this->unitOfWork->performTransaction(function () use ($authInformation, $authConfirmation) {
                $this->authenticationInformaionRepository->save($authInformation);
                $this->authConfirmationRepository->save($authConfirmation);
            });
        } catch (Exception $e) {
            return UserRegistrationResult::createWhenTransactionError();
        }

        

        return UserRegistrationResult::createWhenSuccess($authConfirmation->oneTimeToken()->value);
    }
}