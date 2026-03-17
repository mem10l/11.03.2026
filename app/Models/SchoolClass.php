<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'classes';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'class_id';

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
        'class_name',
        'school_year',
    ];

    /**
     * Get the class members for this class.
     */
    public function classMembers(): HasMany
    {
        return $this->hasMany(ClassMember::class, 'class_id', 'class_id');
    }

    /**
     * Get the internships for this class.
     */
    public function internships(): HasMany
    {
        return $this->hasMany(Internship::class, 'class_id', 'class_id');
    }

    /**
     * Get the students in this class.
     */
    public function students(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_members', 'class_id', 'user_id')
            ->withPivot('member_id')
            ->using(ClassMember::class);
    }
}
