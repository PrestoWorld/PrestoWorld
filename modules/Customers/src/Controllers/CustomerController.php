<?php

declare(strict_types=1);

namespace Modules\Customers\Controllers;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Cycle\Database\DatabaseProviderInterface;

class CustomerController
{
    protected DatabaseProviderInterface $dbal;

    public function __construct(mixed $app)
    {
        $this->dbal = $app->make(DatabaseProviderInterface::class);
    }

    public function index(Request $request): Response
    {
        $items = $this->dbal->database()->select('*')->from('optilarity_customers')->fetchAll();
        return Response::json($items);
    }

    public function show(Request $request, int $id): Response
    {
        $item = $this->dbal->database()->select('*')->from('optilarity_customers')->where('id', $id)->run()->fetch();
        if (!$item) return Response::json(['error' => 'Not found'], 404);
        return Response::json($item);
    }

    public function store(Request $request): Response
    {
        $data = (array)$request->post();
        $id = $this->dbal->database()->insert('optilarity_customers')->values(array_merge($data, [
            'created_at' => date('Y-m-d H:i:s')
        ]))->run();
        return Response::json(['id' => $id, 'success' => true]);
    }

    public function update(Request $request, int $id): Response
    {
        $data = (array)$request->post();
        $this->dbal->database()->update('optilarity_customers', array_merge($data, [
            'updated_at' => date('Y-m-d H:i:s')
        ]), ['id' => $id]);
        return Response::json(['success' => true]);
    }

    public function destroy(Request $request, int $id): Response
    {
        $this->dbal->database()->delete('optilarity_customers')->where('id', $id)->run();
        return Response::json(['success' => true]);
    }
}
