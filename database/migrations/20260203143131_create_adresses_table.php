<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdressesTable extends AbstractMigration
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
        $addresses = $this->table('addresses');
        $addresses->addColumn('cep', 'string', ['limit' => 9, 'null' => true])
                  ->addColumn('street', 'string', ['limit' => 100, 'null' => true])
                  ->addColumn('number', 'string', ['limit' => 10, 'null' => true])
                  ->addColumn('neighborhood', 'string', ['limit' => 50, 'null' => true])
                  ->addColumn('complement', 'string', ['limit' => 100, 'null' => true])
                  ->addColumn('city', 'string', ['limit' => 50, 'null' => true])
                  ->addColumn('state', 'string', ['limit' => 2, 'null' => true])
                  ->addColumn('latitude', 'decimal', ['precision' => 10, 'scale' => 8, 'null' => true])
                  ->addColumn('longitude', 'decimal', ['precision' => 11, 'scale' => 8, 'null' => true])
                  ->addTimestamps()
                  ->create();
    }
}
