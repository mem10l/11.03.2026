<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'comment_id' => $this->comment_id,
            'content' => $this->content,
            'user_id' => $this->user_id,
            'commentable_type' => $this->commentable_type,
            'commentable_id' => $this->commentable_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => new UserResource($this->whenLoaded('user')),
            'commentable' => [
                'type' => $this->commentable_type,
                'id' => $this->commentable_id,
                'data' => new class($this->whenLoaded('commentable')) extends JsonResource
                {
                    public function toArray(Request $request): array
                    {
                        return $this->resource ? $this->resource->toArray() : null;
                    }
                },
            ],
        ];
    }
}
