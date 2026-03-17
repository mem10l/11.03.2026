<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'companies';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'company_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'address',
    ];

    /**
     * Get the applications for this company.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'company_id', 'company_id');
    }

    /**
     * Get the placements for this company.
     */
    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class, 'company_id', 'company_id');
    }
}
