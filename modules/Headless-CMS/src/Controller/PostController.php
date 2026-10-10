<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\HeadlessCMS\Controller;

use PrestoWorld\Modules\Schema\PostRepository;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Witals\Framework\Http\Controllers\AbstractController;
use PrestoWorld\Modules\HeadlessCMS\Serializer\PostSerializer;
use PrestoWorld\Modules\HeadlessCMS\Transformer\PostTransformer;

class PostController extends AbstractController
{
    protected PostRepository $postRepository;
    protected PostSerializer $postSerializer;
    protected PostTransformer $postTransformer;

    public function __construct(
        PostRepository $postRepository,
        PostSerializer $postSerializer,
        PostTransformer $postTransformer
    ) {
        $this->postRepository = $postRepository;
        $this->postSerializer = $postSerializer;
        $this->postTransformer = $postTransformer;
    }

    /**
     * GET /api/v1/posts - List all posts
     */
    public function index(Request $request): Response
    {
        $query = [];

        // Parse query parameters
        $postType = $request->query('post_type');
        if ($postType) {
            $query['post_type'] = $postType;
        }

        $status = $request->query('status');
        if ($status) {
            $query['status'] = $status;
        }

        $search = $request->query('search');
        if ($search) {
            $query['search'] = $search;
        }

        $category = $request->query('category');
        if ($category) {
            $query['category'] = $category;
        }

        $author = $request->query('author');
        if ($author) {
            $query['author_id'] = (int) $author;
        }

        $order = $request->query('order') ?? 'DESC';
        if ($order && in_array($order, ['ASC', 'DESC'])) {
            $query['order'] = $order;
        }

        $orderBy = $request->query('orderby') ?? 'date';
        if ($orderBy) {
            $query['orderby'] = $orderBy;
        }

        $perPage = (int) ($request->query('per_page') ?? 10);
        if ($perPage > 0) {
            $query['per_page'] = $perPage;
        }

        $page = (int) ($request->query('page') ?? 1);
        if ($page > 0) {
            $query['offset'] = ($page - 1) * $perPage;
        }

        $locale = $request->query('locale');
        if ($locale) {
            $query['locale'] = $locale;
            $this->postRepository->setLocale($locale);
        }

        // Fetch posts
        $posts = $this->postRepository->find($query);
        $total = $this->postRepository->count($query);

        // Serialize
        $serializer = new PostSerializer();
        $serializedPosts = $serializer->serializeCollection($posts, ['locale' => $locale]);
        
        $paginated = $serializer->paginate(
            $serializedPosts,
            $total,
            $perPage,
            $page
        );

        // Transform
        $transformed = $this->postTransformer->transformCollection($paginated);

        return Response::json($transformed);
    }

    /**
     * GET /api/v1/posts/{id} - Get single post
     */
    public function show(Request $request, int $id): Response
    {
        $locale = $request->query('locale');

        if ($locale) {
            $this->postRepository->setLocale($locale);
        }

        $post = $this->postRepository->find(['post_type' => 'post', 'status' => 'publish', 'author_id' => $id]);

        if (empty($posts)) {
            return Response::json([
                'success' => false,
                'error' => "Post with id '{$id}' not found.",
            ], 404);
        }

        $post = array_values($post)[0];
        $serializer = new PostSerializer();
        $serialized = $serializer->serialize($post, ['locale' => $locale]);

        return Response::json($this->postTransformer->transform($serialized));
    }

    /**
     * GET /api/v1/posts/by-slug - Get post by slug
     */
    public function showBySlug(Request $request): Response
    {
        $slug = $request->query('slug');
        $postType = $request->query('post_type') ?? 'post';
        $locale = $request->query('locale');

        if ($locale) {
            $this->postRepository->setLocale($locale);
        }

        $posts = $this->postRepository->find([
            'post_type' => $postType,
            'status' => 'publish',
        ]);

        $foundPost = null;
        foreach ($posts as $post) {
            if ($post['slug'] === $slug) {
                $foundPost = $post;
                break;
            }
        }

        if (!$foundPost) {
            return Response::json([
                'success' => false,
                'error' => "Post with slug '{$slug}' not found.",
            ], 404);
        }

        $serializer = new PostSerializer();
        $serialized = $serializer->serialize($foundPost, ['locale' => $locale]);

        return Response::json($this->postTransformer->transform($serialized));
    }

    /**
     * GET /api/v1/posts/{id}/related - Get related posts
     */
    public function related(Request $request, int $id): Response
    {
        $postType = $request->query('post_type') ?? 'post';
        $limit = (int) ($request->query('limit') ?? 5);

        $post = $this->postRepository->findById($id);

        if (!$post) {
            return Response::json([
                'success' => false,
                'error' => "Post with id '{$id}' not found.",
            ], 404);
        }

        $related = $this->postRepository->find([
            'post_type' => $postType,
            'tax_query' => [
                [
                    'taxonomy' => $post['terms'][0]?.taxonomy ?? 'category',
                    'terms' => array_map(fn($t) => $t->id ?? 0, $post['terms']),
                    'field' => 'term_id',
                ],
            ],
            'per_page' => $limit + 1,
            'status' => 'publish',
        ]);

        $serializer = new PostSerializer();
        $serialized = $serializer->serializeCollection(array_values($related), [
            'locale' => $this->postRepository->getLocale(),
        ]);

        return Response::json($this->postTransformer->transformRelatedCollection($serialized));
    }
}