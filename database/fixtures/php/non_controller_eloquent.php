<?php

namespace App\Services;

use App\Models\Order;

class OrderService
{
    public function find(int $id)
    {
        return Order::where('id', $id)->first();
    }
}
