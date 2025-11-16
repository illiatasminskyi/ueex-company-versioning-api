<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $table = 'companies';
    
    protected $fillable = [
        'name',
        'edrpou',
        'address'
    ];
    
    public function versions(): HasMany
    {
        return $this->hasMany(CompanyVersion::class);
    }
}
