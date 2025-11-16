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
        'address',
        'created_at'
    ];
    
    public $timestamps = false;
    
    protected $casts = [
        'created_at' => 'datetime'
    ];
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->created_at) {
                $model->created_at = now();
            }
        });
    }
    
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
