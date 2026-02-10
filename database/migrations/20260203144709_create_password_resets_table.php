<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePasswordResetsTable extends AbstractMigration
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
        $passwordResets = $this->table('password_resets');
        $passwordResets->addColumn('user_id', 'integer' ,['signed' => false])
                       ->addColumn('email', 'string', ['limit' => 255])
                       ->addColumn('token', 'string', ['limit' => 255])
                       ->addColumn('expires_at', 'datetime')
                       ->addTimestamps()
                       ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
                       ->create();
    }
}
