<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'grade_id' => $this->grade_id,
            'internship' => new InternshipResource($this->whenLoaded('internship')),
            'internship_id' => $this->internship_id,
            'student' => new UserResource($this->whenLoaded('student')),
            'student_id' => $this->student_id,
            'grade' => $this->grade,
            'comment' => $this->comment,
        ];
    }
}
