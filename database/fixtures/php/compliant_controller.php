<?php

namespace App\Http\Controllers;

use App\Services\OrderService;

class OrderController
{
    public function __construct(private OrderService $orders)
    {
    }

    public function show(int $id)
    {
        return $this->orders->find($id);
    }
}
