<?php

namespace Dedoc\Scramble\Tests\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

class TestSchemaBuilder
{
    public function __construct(private Connection $connection) {}

    public function createUsersTable(): void
    {
        $schema = new Schema();
        $table = $schema->createTable('users');
        $table->addColumn('id', Types::INTEGER, ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('name', Types::STRING, ['length' => 255]);
        $table->addColumn('email', Types::STRING, ['length' => 255]);
        $table->addColumn('password', Types::STRING, ['length' => 255]);
        $table->addColumn('roles', Types::JSON);
        $table->addColumn('remember_token', Types::STRING, ['length' => 100, 'notnull' => false]);
        $table->addColumn('created_at', Types::DATETIME_MUTABLE);
        $table->addColumn('updated_at', Types::DATETIME_MUTABLE);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['email']);

        $queries = $schema->toSql($this->connection->getDatabasePlatform());
        foreach ($queries as $query) {
            $this->connection->executeStatement($query);
        }
    }

    public function createPostsTable(): void
    {
        $schema = new Schema();
        $table = $schema->createTable('posts');
        $table->addColumn('id', Types::INTEGER, ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('status', Types::STRING, ['length' => 255]); // Using string instead of enum for broader compatibility
        $table->addColumn('user_id', Types::INTEGER, ['unsigned' => true]);
        $table->addColumn('title', Types::STRING, ['length' => 255]);
        $table->addColumn('settings', Types::JSON, ['notnull' => false]);
        $table->addColumn('body', Types::TEXT);
        $table->addColumn('approved_at', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $table->addColumn('created_at', Types::DATETIME_MUTABLE);
        $table->addColumn('updated_at', Types::DATETIME_MUTABLE);
        $table->addColumn('deleted_at', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $table->setPrimaryKey(['id']);

        $queries = $schema->toSql($this->connection->getDatabasePlatform());
        foreach ($queries as $query) {
            $this->connection->executeStatement($query);
        }
    }

    public function createRolesTable(): void
    {
        $schema = new Schema();
        $table = $schema->createTable('roles');
        $table->addColumn('id', Types::BIGINT, ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('name', Types::STRING, ['length' => 255]);
        $table->addColumn('guard_name', Types::STRING, ['length' => 255]);
        $table->addColumn('created_at', Types::DATETIME_MUTABLE);
        $table->addColumn('updated_at', Types::DATETIME_MUTABLE);
        $table->setPrimaryKey(['id']);

        $queries = $schema->toSql($this->connection->getDatabasePlatform());
        foreach ($queries as $query) {
            $this->connection->executeStatement($query);
        }
    }

    public function dropAllTables(): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();

        foreach ($tables as $tableName) {
            $this->connection->executeStatement(
                $this->connection->getDatabasePlatform()->getDropTableSQL($tableName)
            );
        }
    }
}
