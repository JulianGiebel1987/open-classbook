<?php

namespace OpenClassbook\Tests\Models;

use OpenClassbook\Models\User;
use OpenClassbook\Tests\DatabaseTestCase;

class UserModelTest extends DatabaseTestCase
{
    public function testFindByIdReturnsUser(): void
    {
        $id = $this->createTestUser();

        $user = User::findById($id);

        $this->assertNotNull($user);
        $this->assertEquals('testuser', $user['username']);
        $this->assertEquals('test@example.com', $user['email']);
        $this->assertEquals('lehrer', $user['role']);
    }

    public function testFindByIdReturnsNullForNonexistent(): void
    {
        $this->assertNull(User::findById(9999));
    }

    public function testFindByUsername(): void
    {
        $this->createTestUser();

        $user = User::findByUsername('testuser');
        $this->assertNotNull($user);
        $this->assertEquals('testuser', $user['username']);
    }

    public function testFindByEmail(): void
    {
        $this->createTestUser();

        $user = User::findByUsername('test@example.com');
        $this->assertNotNull($user);
        $this->assertEquals('testuser', $user['username']);
    }

    public function testFindByUsernameReturnsNull(): void
    {
        $this->assertNull(User::findByUsername('nonexistent'));
    }

    public function testFindByEmailReturnsUser(): void
    {
        $this->createTestUser(['email' => 'central@example.com']);

        $user = User::findByEmail('central@example.com');
        $this->assertNotNull($user);
        $this->assertEquals('testuser', $user['username']);
    }

    public function testFindByEmailIsCaseInsensitive(): void
    {
        $this->createTestUser(['email' => 'central@example.com']);

        $user = User::findByEmail('CENTRAL@EXAMPLE.COM');
        $this->assertNotNull($user);
        $this->assertEquals('testuser', $user['username']);
    }

    public function testFindByEmailReturnsNull(): void
    {
        $this->assertNull(User::findByEmail('nobody@example.com'));
    }

    public function testCreateUser(): void
    {
        $id = User::create([
            'username' => 'newuser',
            'email' => 'new@example.com',
            'password' => 'SecurePass123',
            'role' => 'admin',
        ]);

        $user = User::findById($id);
        $this->assertNotNull($user);
        $this->assertEquals('newuser', $user['username']);
        $this->assertEquals('admin', $user['role']);
        $this->assertTrue(password_verify('SecurePass123', $user['password_hash']));
    }

    public function testCreateUserDefaultMustChangePassword(): void
    {
        $id = User::create([
            'username' => 'newuser',
            'email' => 'new@example.com',
            'password' => 'SecurePass123',
            'role' => 'lehrer',
        ]);

        $user = User::findById($id);
        $this->assertEquals(1, $user['must_change_password']);
    }

    public function testUpdateUser(): void
    {
        $id = $this->createTestUser();

        User::update($id, ['username' => 'updateduser', 'role' => 'admin']);

        $user = User::findById($id);
        $this->assertEquals('updateduser', $user['username']);
        $this->assertEquals('admin', $user['role']);
    }

    public function testUpdateWithEmptyFieldsDoesNothing(): void
    {
        $id = $this->createTestUser();

        User::update($id, []);

        $user = User::findById($id);
        $this->assertEquals('testuser', $user['username']);
    }

    public function testUpdatePassword(): void
    {
        $id = $this->createTestUser(['must_change_password' => 1]);

        User::updatePassword($id, 'NewSecurePass1');

        $user = User::findById($id);
        $this->assertTrue(password_verify('NewSecurePass1', $user['password_hash']));
        $this->assertEquals(0, $user['must_change_password']);
    }

    public function testUsernameExists(): void
    {
        $this->createTestUser();

        $this->assertTrue(User::usernameExists('testuser'));
        $this->assertFalse(User::usernameExists('nonexistent'));
    }

    public function testUsernameExistsWithExclude(): void
    {
        $id = $this->createTestUser();

        $this->assertFalse(User::usernameExists('testuser', $id));
        $this->assertTrue(User::usernameExists('testuser'));
    }

    public function testEmailExists(): void
    {
        $this->createTestUser(['email' => 'taken@example.com']);

        $this->assertTrue(User::emailExists('taken@example.com'));
        $this->assertTrue(User::emailExists('TAKEN@example.com')); // case-insensitiv
        $this->assertFalse(User::emailExists('free@example.com'));
    }

    public function testEmailExistsWithExclude(): void
    {
        $id = $this->createTestUser(['email' => 'taken@example.com']);

        $this->assertFalse(User::emailExists('taken@example.com', $id));
        $this->assertTrue(User::emailExists('taken@example.com'));
    }

    public function testFindAllNoFilters(): void
    {
        $this->createTestUser(['username' => 'user1']);
        $this->createTestUser(['username' => 'user2', 'email' => 'user2@example.com']);

        $users = User::findAll();
        $this->assertCount(2, $users);
    }

    public function testFindAllFilterByRole(): void
    {
        $this->createTestUser(['username' => 'teacher1', 'role' => 'lehrer']);
        $this->createTestUser(['username' => 'admin1', 'email' => 'admin@example.com', 'role' => 'admin']);

        $teachers = User::findAll(['role' => 'lehrer']);
        $this->assertCount(1, $teachers);
        $this->assertEquals('teacher1', $teachers[0]['username']);
    }

    public function testFindAllFilterBySearch(): void
    {
        $this->createTestUser(['username' => 'johndoe', 'email' => 'john@example.com']);
        $this->createTestUser(['username' => 'janedoe', 'email' => 'jane@example.com']);

        $results = User::findAll(['search' => 'john']);
        $this->assertCount(1, $results);
        $this->assertEquals('johndoe', $results[0]['username']);
    }

    public function testFindAllFilterByActive(): void
    {
        $this->createTestUser(['username' => 'active1', 'active' => 1]);
        $this->createTestUser(['username' => 'inactive1', 'email' => 'in@example.com', 'active' => 0]);

        $active = User::findAll(['active' => 1]);
        $this->assertCount(1, $active);
        $this->assertEquals('active1', $active[0]['username']);
    }

    public function testSetAndFindResetToken(): void
    {
        $id = $this->createTestUser();
        $token = hash('sha256', 'testtoken');
        $expires = new \DateTime('+1 hour');

        User::setResetToken($id, $token, $expires);

        // SQLite doesn't support NOW() so we query directly
        $user = self::$pdo->query("SELECT * FROM users WHERE password_reset_token = '$token'")->fetch();
        $this->assertNotFalse($user);
        $this->assertEquals($token, $user['password_reset_token']);
    }

    public function testClearResetToken(): void
    {
        $id = $this->createTestUser();
        $token = hash('sha256', 'testtoken');
        $expires = new \DateTime('+1 hour');

        User::setResetToken($id, $token, $expires);
        User::clearResetToken($id);

        $user = User::findById($id);
        $this->assertNull($user['password_reset_token']);
        $this->assertNull($user['password_reset_expires']);
    }

    public function testMarkEmailVerifiedSetsTimestamp(): void
    {
        $id = $this->createTestUser();
        $this->assertNull(User::findById($id)['email_verified_at']);

        User::markEmailVerified($id);

        $this->assertNotNull(User::findById($id)['email_verified_at']);
    }

    public function testSetAndFindEmailVerificationToken(): void
    {
        $id = $this->createTestUser();
        $token = hash('sha256', 'verifytoken');
        $expires = new \DateTime('+1 day');

        User::setEmailVerificationToken($id, $token, $expires, 'neu@example.com');

        $found = User::findByEmailVerificationToken($token);
        $this->assertNotNull($found);
        $this->assertEquals($id, (int) $found['id']);
        $this->assertEquals('neu@example.com', $found['pending_email']);
    }

    public function testApplyPendingEmailUpdatesEmailAndUsername(): void
    {
        $id = $this->createTestUser(['username' => 'old@example.com', 'email' => 'old@example.com']);
        $token = hash('sha256', 'verifytoken');
        User::setEmailVerificationToken($id, $token, new \DateTime('+1 day'), 'Neu@Example.com');

        User::applyPendingEmail($id);

        $user = User::findById($id);
        $this->assertEquals('Neu@Example.com', $user['email']);
        $this->assertEquals('neu@example.com', $user['username']); // normalisiert
        $this->assertNotNull($user['email_verified_at']);
        $this->assertNull($user['pending_email']);
        $this->assertNull($user['email_verification_token']);
    }

    public function testApplyPendingEmailWithoutPendingDoesNothing(): void
    {
        $id = $this->createTestUser(['username' => 'stay@example.com', 'email' => 'stay@example.com']);

        User::applyPendingEmail($id);

        $user = User::findById($id);
        $this->assertEquals('stay@example.com', $user['username']);
    }

    public function testSessionVersionDefaultsToZero(): void
    {
        $id = $this->createTestUser();

        $this->assertSame(0, User::getSessionVersion($id));
    }

    public function testIncrementSessionVersionAdvancesValue(): void
    {
        $id = $this->createTestUser();

        User::incrementSessionVersion($id);
        $this->assertSame(1, User::getSessionVersion($id));

        User::incrementSessionVersion($id);
        $this->assertSame(2, User::getSessionVersion($id));
    }

    public function testGetSessionVersionReturnsNullForUnknownUser(): void
    {
        $this->assertNull(User::getSessionVersion(9999));
    }
}
