<?php

declare(strict_types=1);

namespace Modules\StaticPages\Controllers;

use Witals\Framework\Http\Response;
use Witals\Framework\Http\Request;
use Cycle\Database\DatabaseProviderInterface;
use PrestoWorld\Theme\ThemeManager;
use Witals\Framework\Database\Crud\CrudController;

class PageController extends CrudController
{
    protected ThemeManager $theme;
    protected string $table = 'optilarity_static_pages';
    protected ?string $translationTable = 'optilarity_translations_static_pages';
    protected array $translatableFields = ['title', 'content'];
    protected bool $isSeoable = true;

    public function __construct(DatabaseProviderInterface $dbal, ThemeManager $theme)
    {
        parent::__construct($dbal);
        $this->theme = $theme;
    }

    /**
     * Show a single page by slug.
     */
    public function show(Request $request, $slug): Response
    {
        $query = $this->getInitialQuery($request);
        
        $alias = ($this->translationTable) ? 'p.' : '';
        $query->where($alias . 'slug', $slug)
              ->where($alias . 'status', 'publish');

        $pageRaw = $query->run()->fetch();

        if (!$pageRaw) {
            return $this->themeResponse('404', ['title' => 'Page Not Found']);
        }

        $page = $this->processItem($pageRaw);

        return $this->themeResponse('page', [
            'page' => $page,
            'title' => $page['title']
        ]);
    }

    protected function themeResponse(string $view, array $data = []): Response
    {
        $html = $this->theme->render($view, $data);
        return Response::html($html);
    }
}
