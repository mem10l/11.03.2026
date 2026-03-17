<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use  HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'role_id',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the role for this user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(UserRole::class, 'role_id', 'role_id');
    }

    /**
     * Get the internships supervised by this user.
     */
    public function supervisedInternships(): HasMany
    {
        return $this->hasMany(Internship::class, 'supervisor_id', 'id');
    }

    /**
     * Get the applications submitted by this user.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'student_id', 'id');
    }

    /**
     * Get the placements for this user.
     */
    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class, 'student_id', 'id');
    }

    /**
     * Get the grades for this user.
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'student_id', 'id');
    }

    /**
     * Get the class memberships for this user.
     */
    public function classMemberships(): HasMany
    {
        return $this->hasMany(ClassMember::class, 'user_id', 'id');
    }

    /**
     * Get the classes this user belongs to.
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_members', 'user_id', 'class_id')
            ->withPivot('member_id')
            ->using(ClassMember::class);
    }
}
