<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InternshipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'internship_id' => $this->internship_id,
            'title' => $this->title,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'class' => new SchoolClassResource($this->whenLoaded('class')),
            'class_id' => $this->class_id,
            'supervisor' => new UserResource($this->whenLoaded('supervisor')),
            'supervisor_id' => $this->supervisor_id,
            'grading_type' => new GradingTypeResource($this->whenLoaded('gradingType')),
            'grading_type_id' => $this->grading_type_id,
        ];
    }
}
