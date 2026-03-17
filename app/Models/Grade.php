<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'grades';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'grade_id';

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
        'internship_id',
        'student_id',
        'grade',
        'comment',
    ];

    /**
     * Get the internship for this grade.
     */
    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class, 'internship_id', 'internship_id');
    }

    /**
     * Get the student for this grade.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id', 'id');
    }
}
