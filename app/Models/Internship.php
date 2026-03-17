<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Internship extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'internships';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'internship_id';

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
        'title',
        'start_date',
        'end_date',
        'class_id',
        'supervisor_id',
        'grading_type_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Get the class for this internship.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id', 'class_id');
    }

    /**
     * Get the supervisor for this internship.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id', 'id');
    }

    /**
     * Get the grading type for this internship.
     */
    public function gradingType(): BelongsTo
    {
        return $this->belongsTo(GradingType::class, 'grading_type_id', 'type_id');
    }

    /**
     * Get the applications for this internship.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'internship_id', 'internship_id');
    }

    /**
     * Get the placements for this internship.
     */
    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class, 'internship_id', 'internship_id');
    }

    /**
     * Get the grades for this internship.
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'internship_id', 'internship_id');
    }
}
