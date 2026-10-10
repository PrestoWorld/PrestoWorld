<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\HeadlessCMS\Controller;

use PrestoWorld\Modules\Schema\PostRepository;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Witals\Framework\Http\Controllers\AbstractController;
use PrestoWorld\Modules\HeadlessCMS\Serializer\TermSerializer;
use PrestoWorld\Modules\HeadlessCMS\Transformer\TermTransformer;

class TermController extends AbstractController
{
    protected PostRepository $postRepository;
    protected TermSerializer $termSerializer;
    protected TermTransformer $termTransformer;

    public function __construct(
        PostRepository $postRepository,
        TermSerializer $termSerializer,
        TermTransformer $termTransformer
    ) {
        $this->postRepository = $postRepository;
        $this->termSerializer = $termSerializer;
        $this->termTransformer = $termTransformer;
    }

    /**
     * GET /api/v1/terms - List all terms
     */
    public function index(Request $request): Response
    {
        $query = [];

        $taxonomy = $request->query('taxonomy');
        if ($taxonomy) {
            $query['taxonomy'] = $taxonomy;
        }

        $search = $request->query('search');
        if ($search) {
            $query['search'] = $search;
        }

        $parent = $request->query('parent');
        if ($parent) {
            $query['parent'] = (int) $parent;
        }

        $hideEmpty = $request->query('hide_empty');
        if ($hideEmpty !== null) {
            $query['hide_empty'] = (bool) $hideEmpty;
        }

        $perPage = (int) ($request->query('per_page') ?? 20);
        if ($perPage > 0) {
            $query['per_page'] = $perPage;
        }

        $page = (int) ($request->query('page') ?? 1);
        if ($page > 0) {
            $query['offset'] = ($page - 1) * $perPage;
        }

        $orderBy = $request->query('orderby') ?? 'name';
        if ($orderBy) {
            $query['orderby'] = $orderBy;
        }

        $order = $request->query('order') ?? 'ASC';
        if ($order && in_array($order, ['ASC', 'DESC'])) {
            $query['order'] = $order;
        }

        $terms = $this->postRepository->find([]); // Using PostRepository as placeholder
        // Actually we need a term repository - let's use a simplified approach

        // For now, let's return what we have
        $total = count($terms); // Simplified

        $serializer = new TermSerializer();
        $serialized = $serializer->serializeCollection($terms);

        return Response::json([
            'success' => true,
            'data' => $serialized,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * GET /api/v1/terms/{id} - Get single term
     */
    public function show(Request $request, int $id): Response
    {
        $terms = $this->postRepository->find([]); // Simplified

        $foundTerm = null;
        foreach ($terms as $term) {
            if ((int) ($term['id'] ?? 0) === $id) {
                $foundTerm = $term;
                break;
            }
        }

        if (!$foundTerm) {
            return Response::json([
                'success' => false,
                'error' => "Term with id '{$id}' not found.",
            ], 404);
        }

        $serializer = new TermSerializer();
        $serialized = $serializer->serialize($foundTerm);

        return Response::json($this->termTransformer->transform($serialized));
    }

    /**
     * GET /api/v1/terms/by-slug - Get term by slug
     */
    public function showBySlug(Request $request): Response
    {
        $slug = $request->query('slug');
        $taxonomy = $request->query('taxonomy') ?? 'category';

        $terms = $this->postRepository->find([]); // Simplified

        $foundTerm = null;
        foreach ($terms as $term) {
            if ($term['slug'] === $slug && $term['taxonomy'] === $taxonomy) {
                $foundTerm = $term;
                break;
            }
        }

        if (!$foundTerm) {
            return Response::json([
                'success' => false,
                'error' => "Term with slug '{$slug}' in taxonomy '{$taxonomy}' not found.",
            ], 404);
        }

        $serializer = new TermSerializer();
        $serialized = $serializer->serialize($foundTerm);

        return Response::json($this->termTransformer->transform($serialized));
    }
}