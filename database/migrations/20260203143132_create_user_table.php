<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $users = $this->table('users');
        $users->addColumn('name', 'string', ['limit' => 255])
              ->addColumn('email', 'string', ['limit' => 255])
              ->addColumn('birthdate', 'date')
              ->addColumn('password_hash', 'string', ['limit' => 255])
              ->addColumn('role', 'enum', ['values' => ['user', 'admin'], 'default' => 'user'])
              ->addColumn('status', 'enum', ['values' => ['active', 'inactive'], 'default' => 'active'])
              ->addColumn('cnpj', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('avatar_path', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('force_password_change', 'boolean', ['default' => true])
              ->addTimestamps()
              ->addIndex(['email'], ['unique' => true])
              ->addIndex(['cnpj'], ['unique' => true])
              ->create();
    }
}
