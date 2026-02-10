<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMatchesTable extends AbstractMigration
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
        $matches = $this->table('matches');
        $matches->addColumn('venue_id', 'integer' , ['signed' => false])
                ->addColumn('sport_id', 'integer' , ['signed' => false])
                ->addColumn('creator_user_id', 'integer', ['signed' => false])
                ->addColumn('start_time', 'datetime')
                ->addColumn('status', 'enum', ['values' => ['scheduled', 'completed', 'cancelled'], 'default' => 'scheduled'])
                ->addTimestamps()
                ->addForeignKey('creator_user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
                ->addForeignKey('sport_id', 'sports', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
                ->addForeignKey('venue_id', 'venues', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
                ->create();
    }
}
