<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;

class FollowController extends Controller
{
    use AuthorizesApiRequests;

    public function index(): JsonResponse
    {
        $user = $this->currentUser()->load([
            'followedCategories:id,name,slug,description',
            'followedTags:id,name,slug,description,color',
        ]);

        return response()->json([
            'categories' => $user->followedCategories,
            'tags' => $user->followedTags,
        ]);
    }

    public function followCategory(string $categoryId): JsonResponse
    {
        $user = $this->currentUser();
        $category = Category::findOrFail($categoryId);
        $user->followedCategories()->syncWithoutDetaching([$category->id]);

        return response()->json([
            'message' => 'Category followed successfully',
            'category' => $category,
        ]);
    }

    public function unfollowCategory(string $categoryId): JsonResponse
    {
        $user = $this->currentUser();
        $category = Category::findOrFail($categoryId);
        $user->followedCategories()->detach($category->id);

        return response()->json([
            'message' => 'Category unfollowed successfully',
        ]);
    }

    public function followTag(string $tagId): JsonResponse
    {
        $user = $this->currentUser();
        $tag = Tag::findOrFail($tagId);
        $user->followedTags()->syncWithoutDetaching([$tag->id]);

        return response()->json([
            'message' => 'Tag followed successfully',
            'tag' => $tag,
        ]);
    }

    public function unfollowTag(string $tagId): JsonResponse
    {
        $user = $this->currentUser();
        $tag = Tag::findOrFail($tagId);
        $user->followedTags()->detach($tag->id);

        return response()->json([
            'message' => 'Tag unfollowed successfully',
        ]);
    }
}
