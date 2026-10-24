<?php

namespace App\Http\Controllers;

class OrderController
{
    public function show(int $id)
    {
        // Order::where('id', $id)->first();
        $hint = "Order::where('id', \$id)->first()";
        /* User::find(1) */
        $doc = 'DB::table("orders")';

        return response()->json(['id' => $id, 'hint' => $hint, 'doc' => $doc]);
    }
}
