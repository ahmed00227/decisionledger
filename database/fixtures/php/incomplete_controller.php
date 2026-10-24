<?php

namespace App\Http\Controllers;

class OrderController
{
    public function show(int $id)
    {
        return Order::where(
