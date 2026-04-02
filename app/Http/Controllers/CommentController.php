<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommentResource;
use App\Http\Resources\CommentResourceCollection;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): CommentResourceCollection
    {
        $query = Comment::with(['user', 'commentable']);

        if ($request->has('commentable_type')) {
            $query->where('commentable_type', $request->commentable_type);
        }

        if ($request->has('commentable_id')) {
            $query->where('commentable_id', $request->commentable_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $comments = $query->paginate($request->get('per_page', 15));

        return new CommentResourceCollection($comments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commentable_type' => ['required', 'string', 'in:App\Models\Application,App\Models\Evaluation'],
            'commentable_id' => ['required', 'integer'],
            'content' => ['required', 'string', 'max:1000'],
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $comment = Comment::create($validated);

        return response()->json([
            'message' => 'Komentārs izveidots veiksmīgi.',
            'data' => new CommentResource($comment->load(['user', 'commentable'])),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Comment $comment): CommentResource
    {
        $comment->load(['user', 'commentable']);

        return new CommentResource($comment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['sometimes', 'required', 'string', 'max:1000'],
        ]);

        $comment->update($validated);

        return response()->json([
            'message' => 'Komentārs atjaunināts veiksmīgi.',
            'data' => new CommentResource($comment->fresh(['user', 'commentable'])),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Comment $comment): JsonResponse
    {
        $comment->delete();

        return response()->json([
            'message' => 'Komentārs dzēsts veiksmīgi.',
        ]);
    }
}
