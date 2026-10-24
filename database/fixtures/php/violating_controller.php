<?php

namespace App\Http\Controllers;

use App\Models\Order;

class OrderController
{
    public function show(int $id)
    {
        return Order::where('id', $id)->first();
    }
}
