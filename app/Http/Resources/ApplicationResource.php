<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'application_id' => $this->application_id,
            'internship' => new InternshipResource($this->whenLoaded('internship')),
            'internship_id' => $this->internship_id,
            'student' => new UserResource($this->whenLoaded('student')),
            'student_id' => $this->student_id,
            'company' => new CompanyResource($this->whenLoaded('company')),
            'company_id' => $this->company_id,
            'status' => new ApplicationStatusResource($this->whenLoaded('status')),
            'status_id' => $this->status_id,
            'motivation_letter' => $this->motivation_letter,
            'submitted_at' => $this->submitted_at,
        ];
    }
}
