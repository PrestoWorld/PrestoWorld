<?php

declare(strict_types=1);

namespace Modules\WebsiteTemplates\Controllers;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Cycle\Database\DatabaseProviderInterface;
use PrestoWorld\Theme\ThemeManager;
use Witals\Framework\Database\Crud\CrudController;
use PrestoWorld\Ecommerce\Traits\HasPurchaseAction;

class TemplateController extends CrudController
{
    use HasPurchaseAction;

    protected ThemeManager $theme;
    protected string $table = 'optilarity_templates';
    protected ?string $translationTable = 'optilarity_translations_templates';
    protected array $translatableFields = ['name', 'description', 'category'];
    protected bool $isSeoable = true;
    protected string $buyableType = 'template';

    public function __construct(DatabaseProviderInterface $dbal, ThemeManager $theme)
    {
        parent::__construct($dbal);
        $this->theme = $theme;
    }

    /**
     * Overriding index to return HTML view instead of JSON.
     */
    public function index(Request $request): Response
    {
        $category = $request->query('category');
        $search = $request->query('search');

        // Redirect legacy category query to slug if needed
        if ($category && !$request->query('category_slug')) {
             $tp = $this->dbal->database()->select('category_slug')
                ->from($this->table)
                ->where('category', $category)
                ->limit(1)->run()->fetch();
            
            if ($tp) {
                return $this->redirect(route_url('web-templates') . '/' . $tp['category_slug'] . ($search ? '?search=' . urlencode($search) : ''));
            }
        }

        // Use parent index logic's JSON response to get items (or duplicate logic for HTML)
        // Since we want HTML, we manually fetch using some parent helpers if possible, 
        // but here it's cleaner to just fetch.
        
        $query = $this->getInitialQuery($request);
        $this->applyFilters($query, $request);
        
        $templates = array_map([$this, 'processItem'], $query->fetchAll());

        $html = $this->theme->render('templates-list', [
            'title' => __('Website Templates'),
            'templates' => $templates,
            'categories' => $this->getCategories(),
            'current_category' => $category,
            'search_query' => $search
        ]);

        return Response::html($html);
    }

    public function resolve(Request $request, string $slug): Response
    {
        // 1. Try template slug
        $query = $this->getInitialQuery($request);
        $query->where('slug', $slug);
        
        $template = $query->run()->fetch();

        if ($template) {
            $item = $this->processItem($template);
            $html = $this->theme->render('template-single', [
                'title' => $item['name'], // Using translated name
                'template' => $item
            ]);
            return Response::html($html);
        }

        // 2. Try category slug
        $categoryExists = $this->dbal->database()->select('category')
            ->from($this->table)
            ->where('category_slug', $slug)
            ->limit(1)->run()->fetch();

        if ($categoryExists) {
            $request = $request->withAttribute('category_slug', $slug);
            $query = $this->getInitialQuery($request);
            $query->where('category_slug', $slug);
            
            $this->applyFilters($query, $request);

            $html = $this->theme->render('templates-list', [
                'title' => __('Website Templates') . ' - ' . $categoryExists['category'],
                'templates' => array_map([$this, 'processItem'], $query->fetchAll()),
                'categories' => $this->getCategories(),
                'current_category' => $categoryExists['category'],
                'current_category_slug' => $slug,
                'search_query' => $request->query('search')
            ]);
            return Response::html($html);
        }

        return Response::html('Content not found', 404);
    }

    protected function getInitialQuery(Request $request)
    {
        $locale = $request->getAttribute('locale') ?: app()->translator()->getLocale();
        $defaultLocale = config('app.locale', 'en');

        if ($this->translationTable && $locale !== $defaultLocale) {
            $foreignKey = 'template_id';
            $fields = ['p.*'];
            foreach ($this->translatableFields as $field) {
                $fields[] = "t.{$field} as {$field}_translated";
            }
            
            return $this->dbal->database()->select($fields)
                ->from($this->table . ' as p')
                ->leftJoin($this->translationTable . ' as t')
                ->on('t.' . $foreignKey, 'p.id')
                ->where('t.language', $locale);
        }

        return $this->dbal->database()->select('*')->from($this->table);
    }

    protected function applyFilters($query, Request $request): void
    {
        $alias = ($query->getType() === 'select' && str_contains((string)$query, ' as p')) ? 'p.' : '';
        
        $query->where($alias . 'status', 'active');

        if ($search = $request->query('search')) {
            $query->where($alias . 'name', 'LIKE', '%' . $search . '%')
                  ->orWhere($alias . 'description', 'LIKE', '%' . $search . '%');
        }
    }

    protected function getCategories(): array
    {
        // Categories also need translation if we want them in the filter shelf
        // For now keep it simple or join there too.
        return $this->dbal->database()->select('category', 'category_slug')
            ->from($this->table)
            ->where('status', 'active')
            ->distinct()
            ->fetchAll();
    }

    protected function resolveItem(string $id)
    {
        $item = $this->dbal->database()->select('*')
            ->from($this->table)
            ->where('id', $id)
            ->run()
            ->fetch();

        if (!$item) {
            return null;
        }

        return new class($item, $this) implements \PrestoWorld\Ecommerce\Contracts\BuyableInterface {
            public function __construct(private array $data, private $controller) {}
            public function getBuyableId(): string|int { return $this->data['id']; }
            public function getBuyableTitle(): string { return $this->controller->translate($this->data['name']); }
            public function getBuyablePrice(): int|float { return (float)($this->data['price'] ?? 0); }
            public function getBuyableType(): string { return 'template'; }
        };
    }
}
