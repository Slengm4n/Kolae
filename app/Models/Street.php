<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Street extends Model
{
    protected $table = 'street';

    public function neighborhood()
    {
        return $this->belongsToo(Neighborhood::class, 'id_neighborhood');
    }
}
