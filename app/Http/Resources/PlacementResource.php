<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlacementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'placement_id' => $this->placement_id,
            'internship' => new InternshipResource($this->whenLoaded('internship')),
            'internship_id' => $this->internship_id,
            'student' => new UserResource($this->whenLoaded('student')),
            'student_id' => $this->student_id,
            'company' => new CompanyResource($this->whenLoaded('company')),
            'company_id' => $this->company_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ];
    }
}
