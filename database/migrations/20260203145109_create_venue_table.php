<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateVenueTable extends AbstractMigration
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
        $table = $this->table('venues');
        $table->addColumn('user_id', 'integer' , ['signed' => false])
              ->addColumn('address_id', 'integer' , ['signed' => false])
              ->addColumn('name', 'string', ['limit' => 255])
              ->addColumn('average_price_per_hour', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
              ->addColumn('court_capacity', 'integer', ['null' => true])
              ->addColumn('has_leisure_area', 'boolean', ['default' => false])
              ->addColumn('leisure_area_capacity', 'integer', ['null' => true])
              ->addColumn('floor_type', 'enum', ['values' => ['grama natural', 'grama sintética', 'cimento', 'madeira', 'outro'], 'null' => true])
              ->addColumn('has_lighting', 'boolean', ['default' => false])
              ->addColumn('is_covered', 'boolean', ['default' => false])
              ->addColumn('status', 'enum', ['values' => ['available', 'unavailable', 'in_maintenance'], 'default' => 'available'])
              ->addTimestamps()
              ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
              ->addForeignKey('address_id', 'addresses', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
              ->create();
    }
}