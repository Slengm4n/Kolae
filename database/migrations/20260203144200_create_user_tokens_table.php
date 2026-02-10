<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserTokensTable extends AbstractMigration
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
        $userTokens = $this->table('user_tokens');
        $userTokens->addColumn('user_id', 'integer', ['signed' => false])
                   ->addColumn('selector', 'string', ['limit' => 255, 'null' => true])
                   ->addColumn('hashed_validator', 'string', ['limit' => 255])
                   ->addColumn('expiry', 'datetime')
                   ->addTimestamps()
                   ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
                   ->create();
    }
}
