<?php

use Lcobucci\JWT\Signer\Key\InMemory;
use packages\adapter\persistence\inMemory\InMemoryAuthenticationAccountRepository;
use packages\domain\model\authenticationAccount\LoginRestriction;
use packages\domain\model\authenticationAccount\FailedLoginCount;
use packages\domain\model\authenticationAccount\IAuthenticationAccountRepository;
use packages\domain\model\authenticationAccount\NextLoginAllowedAt;
use packages\domain\model\authenticationAccount\UserEmail;
use packages\domain\model\authenticationAccount\UserName;
use packages\domain\model\authenticationAccount\UserPassword;
use packages\domain\model\authenticationAccount\authenticationAccount;
use packages\domain\model\authenticationAccount\LoginRestrictionStatus;
use packages\domain\model\authenticationAccount\VerificationStatus;
use packages\domain\service\authenticationAccount\authenticationAccountService;
use packages\test\helpers\authenticationAccount\TestAuthenticationAccountFactory;
use packages\test\helpers\authenticationAccount\authenticationAccountTestDataFactory;
use PHPUnit\Framework\TestCase;

class AuthenticationAccountTest extends TestCase
{
    private InMemoryAuthenticationAccountRepository $authenticationAccountRepository;

    public function setUp(): void
    {
        $this->authenticationAccountRepository = new InMemoryAuthenticationAccountRepository();
    }

    public function test_重複したメールアドレスを持つユーザーが存在しない場合、ユーザープロフィールを初期化できる()
    {
        // given
        // user@example.comのアドレスを持つユーザーをあらかじめ作成しておく
        $alreadyExistsUserEmail = new UserEmail('user@example.com');
        $authenticationAccountTestDataFactory = new AuthenticationAccountTestDataFactory($this->authenticationAccountRepository);
        $authenticationAccountTestDataFactory->create($alreadyExistsUserEmail);

        $email = new UserEmail('otheruser@example.com');
        $userId = $this->authenticationAccountRepository->nextUserId();
        $password = UserPassword::create('1234abcABC!');
        $authenticationAccountService = new AuthenticationAccountService($this->authenticationAccountRepository);

        // when
        $authenticationAccount = AuthenticationAccount::create(
            $userId,
            $email,
            $password,
            $authenticationAccountService
        );

        // then
        $this->assertEquals(VerificationStatus::Unverified, $authenticationAccount->verificationStatus());
        $this->assertEquals(LoginRestrictionStatus::Unrestricted->value, $authenticationAccount->LoginRestriction()->loginRestrictionStatus());
        $this->assertEquals(0, $authenticationAccount->LoginRestriction()->failedLoginCount());
        $this->assertEquals(null, $authenticationAccount->LoginRestriction()->nextLoginAllowedAt());

        // 以下の属性はそのまま設定される
        $this->assertEquals($email, $authenticationAccount->email());
        $this->assertEquals($userId, $authenticationAccount->id());
        $this->assertEquals($password, $authenticationAccount->password());
    }

    public function test_重複したメールアドレスを持つユーザーが既に存在する場合、ユーザープロフィールを初期化できない()
    {
        // given
        // user@example.comのアドレスを持つユーザーをあらかじめ作成しておく
        $alreadyExistsUserEmail = new UserEmail('user@example.com');
        $authenticationAccountTestDataFactory = new AuthenticationAccountTestDataFactory($this->authenticationAccountRepository);
        $authenticationAccountTestDataFactory->create($alreadyExistsUserEmail);

        // メールアドレスが重複している
        $email = new UserEmail('user@example.com');
        $userId = $this->authenticationAccountRepository->nextUserId();
        $password = UserPassword::create('1234abcABC!');
        $authenticationAccountService = new AuthenticationAccountService($this->authenticationAccountRepository);

        // when・then
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('すでに存在するメールアドレスです。');
        AuthenticationAccount::create(
            $userId,
            $email,
            $password,
            $authenticationAccountService
        );
    }

    public function test_ユーザープロフィールを再構築できる()
    {
        // given
        $email = new UserEmail('otheruser@example.com');
        $userId = $this->authenticationAccountRepository->nextUserId();
        $password = UserPassword::create('1234abcABC!');
        $verificationStatus = VerificationStatus::Verified;
        $LoginRestriction = LoginRestriction::initialization();

        // when
        $authenticationAccount = AuthenticationAccount::reconstruct(
            $userId,
            $email,
            $password,
            $verificationStatus,
            $LoginRestriction
        );

        // then
        $this->assertEquals($email, $authenticationAccount->email());
        $this->assertEquals($userId, $authenticationAccount->id());
        $this->assertEquals($password, $authenticationAccount->password());
        $this->assertEquals($verificationStatus, $authenticationAccount->verificationStatus());
        $this->assertEquals($LoginRestriction, $authenticationAccount->LoginRestriction());
    }

    public function 認証ステータスを確認済みに更新できる()
    {
        // given
        // 確認済みステータスが未認証のユーザープロフィールを作成
        $verificationStatus = VerificationStatus::Unverified;
        $authenticationAccount = TestAuthenticationAccountFactory::create(
            null,
            null,
            $verificationStatus
        );

        // when
        $authenticationAccount->updateVerified();

        // then
        $this->assertEquals(VerificationStatus::Verified, $authenticationAccount->verificationStatus());
    }

    public function test_認証ステータスが確認済みの場合、パスワードの変更が行える()
    {
        // given
        // 確認済みステータスが確認済みのユーザープロフィールを作成
        $verificationStatus = VerificationStatus::Verified;
        $password = UserPassword::create('124abcABC!');
        $authenticationAccount = TestAuthenticationAccountFactory::create(
            null,
            $password,
            $verificationStatus
        );

        // when
        $passwordAfterChange = UserPassword::create('124abcABC!_afterChange');
        $authenticationAccount->changePassword($passwordAfterChange, new DateTimeImmutable());

        // then
        $this->assertEquals($passwordAfterChange, $authenticationAccount->password());
    }

    public function test_認証ステータスが未認証の場合、パスワードの変更が行えない()
    {
        // given
        $verificationStatus = VerificationStatus::Unverified;
        $password = UserPassword::create('124abcABC!');
        $authenticationAccount = TestAuthenticationAccountFactory::create(
            null,
            $password,
            $verificationStatus
        );

        // when・then
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('確認済みのユーザーではありません。');
        $passwordAfterChange = UserPassword::create('124abcABC!_afterChange');
        $authenticationAccount->changePassword($passwordAfterChange, new DateTimeImmutable());
    }

    public function test_アカウントがロックされている場合、パスワードの変更が行えない()
    {
        // given
        // アカウントがロックされているユーザープロフィールを作成
        $verificationStatus = VerificationStatus::Verified;
        $password = UserPassword::create('124abcABC!');
        $loginRestriction = LoginRestriction::reconstruct(
            FailedLoginCount::reconstruct(10),
            LoginRestrictionStatus::Restricted,
            NextLoginAllowedAt::reconstruct(new DateTimeImmutable('+10 minutes'))
        );
        $authenticationAccount = TestAuthenticationAccountFactory::create(
            null,
            $password,
            $verificationStatus,
            null,
            $loginRestriction
        );

        // when・then
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('アカウントがロックされています。');
        $passwordAfterChange = UserPassword::create('124abcABC!_afterChange');
        $authenticationAccount->changePassword($passwordAfterChange, new DateTimeImmutable());
    }

    public function test_ログイン失敗回数を更新する()
    {
        // given
        $verificationStatus = VerificationStatus::Verified;
        // ログイン失敗回数は0回
        $LoginRestriction = LoginRestriction::reconstruct(
            FailedLoginCount::reconstruct(0),
            LoginRestrictionStatus::Unrestricted,
            null
        );
        $authenticationAccount = TestAuthenticationAccountFactory::create(
            null,
            null,
            $verificationStatus,
            null,
            $LoginRestriction
        );

        // when
        $authenticationAccount->addFailedLoginCount();

        // then
        $this->assertEquals(1, $authenticationAccount->LoginRestriction()->failedLoginCount());
    }

    public function test_認証ステータスが未認証の場合、ログイン失敗回数を更新しない()
    {
        // given
        $verificationStatus = VerificationStatus::Unverified;
        // ログイン失敗回数は0回
        $LoginRestriction = LoginRestriction::reconstruct(
            FailedLoginCount::reconstruct(0),
            LoginRestrictionStatus::Unrestricted,
            null
        );
        $authenticationAccount = TestAuthenticationAccountFactory::create(
            null,
            null,
            $verificationStatus,
            null,
            $LoginRestriction
        );

        // when・then
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('確認済みのユーザーではありません。');
        $authenticationAccount->addFailedLoginCount();
    }

    public function test_ログイン制限が有効可能の場合、ログイン制限を有効にする()
    {
        // given
        // ログイン失敗回数が10回に達している認証情報を生成する
        $verificationStatus = VerificationStatus::Verified;
        $LoginRestriction = LoginRestriction::reconstruct(
            FailedLoginCount::reconstruct(10),
            LoginRestrictionStatus::Unrestricted,
            null
        );
        $authenticationAccount = TestAuthenticationAccountFactory::create(
            null,
            null,
            $verificationStatus,
            null,
            $LoginRestriction
        );

        // when
        $authenticationAccount->locking(new DateTimeImmutable());

        // then
        $this->assertEquals(LoginRestrictionStatus::Restricted->value, $authenticationAccount->LoginRestriction()->loginRestrictionStatus());
        $this->assertNotNull($authenticationAccount->LoginRestriction()->nextLoginAllowedAt());
    }

    public function test_認証ステータスが未認証の場合、ログイン制限を有効にできない()
    {
        // given
        $verificationStatus = VerificationStatus::Unverified;
        $LoginRestriction = LoginRestriction::reconstruct(
            FailedLoginCount::reconstruct(10),
            LoginRestrictionStatus::Unrestricted,
            null
        );
        $authenticationAccount = TestAuthenticationAccountFactory::create(
            null,
            null,
            $verificationStatus,
            null,
            $LoginRestriction
        );

        // when・then
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('確認済みのユーザーではありません。');
        $authenticationAccount->locking(new DateTimeImmutable());
    }

    public function test_ログイン制限が有効で再ログイン可能である場合はログイン制限を解除できる()
    {
        // given
        // ログイン制限は有効だが再ログインは可能である認証情報を生成する
        $verificationStatus = VerificationStatus::Verified;
        $LoginRestriction = LoginRestriction::reconstruct(
            FailedLoginCount::reconstruct(10),
            LoginRestrictionStatus::Restricted,
            NextLoginAllowedAt::reconstruct(new DateTimeImmutable('-1 minutes'))
        );
        $authenticationAccount = TestAuthenticationAccountFactory::create(
            null,
            null,
            $verificationStatus,
            null,
            $LoginRestriction
        );

        // when
        $authenticationAccount->unlocking(new DateTimeImmutable());

        // then
        $this->assertEquals(LoginRestrictionStatus::Unrestricted->value, $authenticationAccount->LoginRestriction()->loginRestrictionStatus());
        $this->assertNull($authenticationAccount->LoginRestriction()->nextLoginAllowedAt());
    }

    public function test_ログイン制限が有効状態で再ログインが不可である場合、ログインができないことを判定できる()
    {
        // given
        // ログイン制限が有効状態で再ログインが不可である認証情報を生成する
        $verificationStatus = VerificationStatus::Verified;
        $loginRestriction = LoginRestriction::reconstruct(
            FailedLoginCount::reconstruct(10),
            LoginRestrictionStatus::Restricted,
            NextLoginAllowedAt::reconstruct(new DateTimeImmutable('+10 minutes'))
        );
        $authenticationAccount = TestAuthenticationAccountFactory::create(
            null,
            null,
            $verificationStatus,
            null,
            $loginRestriction
        );

        // when
        $result = $authenticationAccount->canLoggedIn(new DateTimeImmutable());

        // then
        $this->assertFalse($result);
    }

    public function test_ログイン制限が有効状態で再ログインが可能である場合、ログインが可能であることを判定できる()
    {
        // given
        // ログイン制限は有効だが再ログイン可能な認証情報を生成する
        $verificationStatus = VerificationStatus::Verified;
        $loginRestriction = LoginRestriction::reconstruct(
            FailedLoginCount::reconstruct(10),
            LoginRestrictionStatus::Restricted,
            NextLoginAllowedAt::reconstruct(new DateTimeImmutable('-1 minutes'))
        );
        $authenticationAccount = TestAuthenticationAccountFactory::create(
            null,
            null,
            $verificationStatus,
            null,
            $loginRestriction
        );

        // when
        $result = $authenticationAccount->canLoggedIn(new DateTimeImmutable());

        // then
        $this->assertTrue($result);
    }

    public function test_ログイン制限が有効状態ではない場合、ログインが可能であることを判定できる()
    {
        // given
        // ログイン制限が有効状態ではない認証情報を生成する
        $verificationStatus = VerificationStatus::Verified;
        $loginRestriction = LoginRestriction::reconstruct(
            FailedLoginCount::reconstruct(9),
            LoginRestrictionStatus::Unrestricted,
            null
        );
        $authenticationAccount = TestAuthenticationAccountFactory::create(
            null,
            null,
            $verificationStatus,
            null,
            $loginRestriction
        );

        // when
        $result = $authenticationAccount->canLoggedIn(new DateTimeImmutable());

        // then
        $this->assertTrue($result);
    }

    public function test_認証ステータスが未認証の場合、ログイン不可であることを判定できる()
    {
        // given
        // 認証ステータスが未認証の認証情報を生成する
        $verificationStatus = VerificationStatus::Unverified;
        $authenticationAccount = TestAuthenticationAccountFactory::create(
            null,
            null,
            $verificationStatus
        );

        // when
        $result = $authenticationAccount->canLoggedIn(new DateTimeImmutable());

        // then
        $this->assertFalse($result);
    }

    public function test_ログイン制限を有効にできるかどうかを判定できる()
    {
        // given
        // ログイン失敗回数が10回に達していている認証情報を生成する
        $verificationStatus = VerificationStatus::Verified;
        $loginRestriction = LoginRestriction::reconstruct(
            FailedLoginCount::reconstruct(10),
            LoginRestrictionStatus::Unrestricted,
            null
        );
        $authenticationAccount = TestAuthenticationAccountFactory::create(
            null,
            null,
            $verificationStatus,
            null,
            $loginRestriction
        );

        // when
        $result = $authenticationAccount->canLocking(new DateTimeImmutable());

        // then
        $this->assertTrue($result);
    }

    public function test_ログイン制限を有効にできないことを判定できる()
    {
        // given
        // ログイン失敗回数が10回に達していない認証情報を生成する
        $verificationStatus = VerificationStatus::Verified;
        $loginRestriction = LoginRestriction::reconstruct(
            FailedLoginCount::reconstruct(9),
            LoginRestrictionStatus::Unrestricted,
            null
        );
        $authenticationAccount = TestAuthenticationAccountFactory::create(
            null,
            null,
            $verificationStatus,
            null,
            $loginRestriction
        );

        // when
        $result = $authenticationAccount->canLocking(new DateTimeImmutable());

        // then
        $this->assertFalse($result);
    }
}