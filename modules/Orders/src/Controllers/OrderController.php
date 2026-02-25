<?php

declare(strict_types=1);

namespace Modules\Orders\Controllers;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Witals\Framework\Database\Crud\CrudController;
use PrestoWorld\Ecommerce\OrderManager;
use Cycle\Database\DatabaseProviderInterface;

class OrderController extends CrudController
{
    protected string $table = 'orders';
    protected OrderManager $orderManager;

    public function __construct(DatabaseProviderInterface $dbal, OrderManager $orderManager)
    {
        parent::__construct($dbal);
        $this->orderManager = $orderManager;
    }

    public function store(Request $request): Response
    {
        $data = (array)$request->post();
        $id = $this->orderManager->createOrder($data);
        
        return $this->json(['id' => $id, 'success' => true]);
    }

    // index, show are inherited from CrudController
}
