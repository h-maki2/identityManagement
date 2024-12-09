<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use packages\adapter\persistence\eloquent\EloquentAuthConfirmationRepository;
use packages\adapter\persistence\eloquent\EloquentAuthenticationInformationRepository;
use packages\domain\model\authConfirmation\OneTimePassword;
use packages\domain\model\authConfirmation\OneTimeToken;
use packages\domain\model\authConfirmation\OneTimeTokenValue;
use packages\test\helpers\authConfirmation\AuthConfirmationTestDataCreator;
use packages\test\helpers\authConfirmation\TestAuthConfirmationFactory;
use packages\test\helpers\authenticationInformation\AuthenticationInformationTestDataCreator;
use Tests\TestCase;

class EloquentAuthConfirmationRepositoryTest extends TestCase
{
    private EloquentAuthConfirmationRepository $eloquentAuthConfirmationRepository;
    private EloquentAuthenticationInformationRepository $eloquentAuthenticationInformationRepository;
    private AuthenticationInformationTestDataCreator $authenticationInformationTestDataCreator;
    private AuthConfirmationTestDataCreator $authConfirmationTestDataCreator;

    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
        $this->eloquentAuthConfirmationRepository = new EloquentAuthConfirmationRepository();
        $this->eloquentAuthenticationInformationRepository = new EloquentAuthenticationInformationRepository();
        $this->authenticationInformationTestDataCreator = new AuthenticationInformationTestDataCreator($this->eloquentAuthenticationInformationRepository);
        $this->authConfirmationTestDataCreator = new AuthConfirmationTestDataCreator(
            $this->eloquentAuthConfirmationRepository,
            $this->eloquentAuthenticationInformationRepository
        );
    }

    public function test_認証確認情報をインサートする()
    {
        // given
        // あらかじめ認証情報を保存しておく
        $userId =  $this->eloquentAuthenticationInformationRepository->nextUserId();
        $this->authenticationInformationTestDataCreator->create(
            id: $userId
        );

        // 認証確認情報を作成する
        $authConfirmation = TestAuthConfirmationFactory::createAuthConfirmation(
            userId: $userId
        );

        // when
        // 認証確認情報を保存する
        $this->eloquentAuthConfirmationRepository->save($authConfirmation);

        // then
        // 認証確認情報が保存されていることを確認する
        $actualAuthConfirmation = $this->eloquentAuthConfirmationRepository->findById($userId);
        $this->assertEquals($authConfirmation->oneTimePassword(), $actualAuthConfirmation->oneTimePassword());
        $this->assertEquals($authConfirmation->oneTimeToken()->value(), $actualAuthConfirmation->oneTimeToken()->value());
        $this->assertEquals($authConfirmation->oneTimeToken()->expirationdate(), $actualAuthConfirmation->oneTimeToken()->expirationdate());
    }

    public function test_認証確認情報を更新できる()
    {
        // given
        // 認証情報を保存しておく
        $userId =  $this->eloquentAuthenticationInformationRepository->nextUserId();
        $this->authenticationInformationTestDataCreator->create(
            id: $userId
        );

        // 認証確認情報を保存しておく
        $oneTimePassword = OneTimePassword::reconstruct('123456');
        $this->authConfirmationTestDataCreator->create(
            userId: $userId,
            oneTimePassword: $oneTimePassword
        );

        // when
        // 認証情報を更新する
        $expectedAuthConfirmation = $this->eloquentAuthConfirmationRepository->findById($userId);
        $expectedAuthConfirmation->reObtain();
        $this->eloquentAuthConfirmationRepository->save($expectedAuthConfirmation);

        // then
        // 認証情報が更新されていることを確認する
        $actualAuthConfirmation = $this->eloquentAuthConfirmationRepository->findById($userId);
        $this->assertEquals($expectedAuthConfirmation->oneTimePassword(), $actualAuthConfirmation->oneTimePassword());
        $this->assertEquals($expectedAuthConfirmation->oneTimeToken()->value(), $actualAuthConfirmation->oneTimeToken()->value());
        $this->assertEquals($expectedAuthConfirmation->oneTimeToken()->expirationdate(), $actualAuthConfirmation->oneTimeToken()->expirationdate());
    }

    public function test_ユーザーIDから認証確認情報を取得できる()
    {
        // given
        // 認証情報を保存しておく
        $検索対象のユーザーID =  $this->eloquentAuthenticationInformationRepository->nextUserId();
        $this->authenticationInformationTestDataCreator->create(
            id: $検索対象のユーザーID
        );

        // 認証確認情報を保存しておく
        $検索対象の認証確認情報 = $this->authConfirmationTestDataCreator->create(
            userId: $検索対象のユーザーID
        );

        // 検索対象ではない認証情報と認証確認情報を保存する
        $検索対象ではないユーザーid1 = $this->eloquentAuthenticationInformationRepository->nextUserId();
        $this->authenticationInformationTestDataCreator->create(
            id: $検索対象ではないユーザーid1
        );
        $this->authConfirmationTestDataCreator->create(
            userId: $検索対象ではないユーザーid1
        );

        $検索対象ではないユーザーid2 = $this->eloquentAuthenticationInformationRepository->nextUserId();
        $this->authenticationInformationTestDataCreator->create(
            id: $検索対象ではないユーザーid2
        );
        $this->authConfirmationTestDataCreator->create(
            userId: $検索対象ではないユーザーid2
        );

        // when
        $actualAuthConfirmation = $this->eloquentAuthConfirmationRepository->findById($検索対象のユーザーID);

        // then
        $this->assertEquals($検索対象の認証確認情報->oneTimePassword(), $actualAuthConfirmation->oneTimePassword());
        $this->assertEquals($検索対象の認証確認情報->oneTimeToken()->value(), $actualAuthConfirmation->oneTimeToken()->value());
        $this->assertEquals($検索対象の認証確認情報->oneTimeToken()->expirationdate(), $actualAuthConfirmation->oneTimeToken()->expirationdate());
    }

    public function test_ワンタイムトークンから認証確認情報を取得できる()
    {
        // given
        // 認証情報を保存しておく
        $検索対象のユーザーID =  $this->eloquentAuthenticationInformationRepository->nextUserId();
        $this->authenticationInformationTestDataCreator->create(
            id: $検索対象のユーザーID
        );

        // 認証確認情報を保存しておく
        $検索対象のワンタイムトークン値 = OneTimeTokenValue::create();
        $検索対象の認証確認情報 = $this->authConfirmationTestDataCreator->create(
            userId: $検索対象のユーザーID,
            oneTimeTokenValue: $検索対象のワンタイムトークン値
        );

        // 検索対象ではない認証情報と認証確認情報を保存する
        $検索対象ではないユーザーid = $this->eloquentAuthenticationInformationRepository->nextUserId();
        $this->authenticationInformationTestDataCreator->create(
            id: $検索対象ではないユーザーid
        );
        $this->authConfirmationTestDataCreator->create(
            userId: $検索対象ではないユーザーid,
            oneTimeTokenValue: OneTimeTokenValue::create(),
        );

        // when
        $actualAuthConfirmation = $this->eloquentAuthConfirmationRepository->findByTokenValue($検索対象のワンタイムトークン値);

        // then
        $this->assertEquals($検索対象の認証確認情報->oneTimePassword(), $actualAuthConfirmation->oneTimePassword());
        $this->assertEquals($検索対象の認証確認情報->oneTimeToken()->value(), $actualAuthConfirmation->oneTimeToken()->value());
        $this->assertEquals($検索対象の認証確認情報->oneTimeToken()->expirationdate(), $actualAuthConfirmation->oneTimeToken()->expirationdate());
    }

    public function test_認証確認情報を削除できる()
    {
        // given
        // 認証確認情報を作成して保存する
        $削除対象のユーザーID =  $this->eloquentAuthenticationInformationRepository->nextUserId();
        $this->authenticationInformationTestDataCreator->create(
            id: $削除対象のユーザーID
        );

        $this->authConfirmationTestDataCreator->create(
            userId: $削除対象のユーザーID
        );

        $削除対象ではないuserId = $this->eloquentAuthenticationInformationRepository->nextUserId();
        $this->authenticationInformationTestDataCreator->create(
            id: $削除対象ではないuserId
        );
        $this->authConfirmationTestDataCreator->create(
            userId: $削除対象ではないuserId
        );

        // when
        $this->eloquentAuthConfirmationRepository->delete($削除対象のユーザーID);

        // then
        $actualAuthConfirmation = $this->eloquentAuthConfirmationRepository->findById($削除対象のユーザーID);
        $this->assertNull($actualAuthConfirmation);
    }
}