<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSportsTable extends AbstractMigration
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
        $sports = $this->table('sports');
        $sports->addColumn('name', 'string', ['limit' => 100])
               ->addColumn('status', 'enum', ['values' => ['active', 'inactive'], 'default' => 'active'])
               ->addColumn('description', 'text', ['null' => true])
               ->addColumn('icon_path', 'string', ['limit' => 255, 'null' => true])
               ->addTimestamps()
               ->create();
    }
}
