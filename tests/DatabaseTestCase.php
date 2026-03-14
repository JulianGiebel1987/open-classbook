<?php

namespace OpenClassbook\Tests;

use OpenClassbook\Database;
use PDO;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    protected static PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        self::createSchema();
        Database::setConnection(self::$pdo);
    }

    public static function tearDownAfterClass(): void
    {
        Database::resetConnection();
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::$pdo->exec('BEGIN');
    }

    protected function tearDown(): void
    {
        self::$pdo->exec('ROLLBACK');
        parent::tearDown();
    }

    private static function createSchema(): void
    {
        self::$pdo->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(100) NOT NULL UNIQUE,
                email VARCHAR(255) DEFAULT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(20) NOT NULL,
                active INTEGER NOT NULL DEFAULT 1,
                must_change_password INTEGER NOT NULL DEFAULT 1,
                password_reset_token VARCHAR(255) DEFAULT NULL,
                password_reset_expires DATETIME DEFAULT NULL,
                last_login DATETIME DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');

        self::$pdo->exec('
            CREATE TABLE teachers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                firstname VARCHAR(100) NOT NULL,
                lastname VARCHAR(100) NOT NULL,
                abbreviation VARCHAR(10) NOT NULL UNIQUE,
                subjects TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');

        self::$pdo->exec('
            CREATE TABLE classes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(50) NOT NULL,
                school_year VARCHAR(9) NOT NULL,
                head_teacher_id INTEGER DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (head_teacher_id) REFERENCES teachers(id),
                UNIQUE (name, school_year)
            )
        ');

        self::$pdo->exec('
            CREATE TABLE students (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER DEFAULT NULL,
                firstname VARCHAR(100) NOT NULL,
                lastname VARCHAR(100) NOT NULL,
                class_id INTEGER NOT NULL,
                birthday DATE DEFAULT NULL,
                guardian_email VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (class_id) REFERENCES classes(id)
            )
        ');

        self::$pdo->exec('
            CREATE TABLE class_teacher (
                class_id INTEGER NOT NULL,
                teacher_id INTEGER NOT NULL,
                PRIMARY KEY (class_id, teacher_id),
                FOREIGN KEY (class_id) REFERENCES classes(id),
                FOREIGN KEY (teacher_id) REFERENCES teachers(id)
            )
        ');

        self::$pdo->exec('
            CREATE TABLE login_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(100) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                successful INTEGER NOT NULL DEFAULT 0
            )
        ');

        self::$pdo->exec('
            CREATE TABLE audit_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER DEFAULT NULL,
                action VARCHAR(100) NOT NULL,
                entity_type VARCHAR(50) DEFAULT NULL,
                entity_id INTEGER DEFAULT NULL,
                details TEXT DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');
    }

    protected function createTestUser(array $overrides = []): int
    {
        $defaults = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password_hash' => password_hash('TestPasswort1', PASSWORD_BCRYPT),
            'role' => 'lehrer',
            'active' => 1,
            'must_change_password' => 0,
        ];

        $data = array_merge($defaults, $overrides);

        self::$pdo->prepare(
            'INSERT INTO users (username, email, password_hash, role, active, must_change_password) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            $data['username'],
            $data['email'],
            $data['password_hash'],
            $data['role'],
            $data['active'],
            $data['must_change_password'],
        ]);

        return (int) self::$pdo->lastInsertId();
    }

    protected function createTestTeacher(int $userId, array $overrides = []): int
    {
        $defaults = [
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'abbreviation' => 'MU',
            'subjects' => 'Mathematik, Physik',
        ];

        $data = array_merge($defaults, $overrides);

        self::$pdo->prepare(
            'INSERT INTO teachers (user_id, firstname, lastname, abbreviation, subjects) VALUES (?, ?, ?, ?, ?)'
        )->execute([
            $userId,
            $data['firstname'],
            $data['lastname'],
            $data['abbreviation'],
            $data['subjects'],
        ]);

        return (int) self::$pdo->lastInsertId();
    }

    protected function createTestClass(array $overrides = []): int
    {
        $defaults = [
            'name' => '5a',
            'school_year' => '2025/2026',
            'head_teacher_id' => null,
        ];

        $data = array_merge($defaults, $overrides);

        self::$pdo->prepare(
            'INSERT INTO classes (name, school_year, head_teacher_id) VALUES (?, ?, ?)'
        )->execute([
            $data['name'],
            $data['school_year'],
            $data['head_teacher_id'],
        ]);

        return (int) self::$pdo->lastInsertId();
    }
}
