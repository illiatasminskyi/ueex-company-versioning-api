<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyVersion extends Model
{
    protected $table = 'company_versions';
    
    protected $fillable = [
        'company_id',
        'version',
        'name',
        'edrpou',
        'address'
    ];
    
    public $timestamps = false;
    
    protected $dates = ['created_at'];
    
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
