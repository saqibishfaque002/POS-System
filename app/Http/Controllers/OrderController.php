<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'total' => 'required|numeric',
            'offline_uuid' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // Check if order with offline_uuid already exists (idempotency)
            if ($request->offline_uuid) {
                $existingOrder = Order::where('offline_uuid', $request->offline_uuid)->first();
                if ($existingOrder) {
                    DB::rollBack();
                    return redirect()->back()->with('success', 'Order already processed');
                }
            }

            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                'total' => $request->total,
                'status' => 'completed',
                'offline_uuid' => $request->offline_uuid,
            ]);

            foreach ($request->items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ]);

                // Deduct stock
                $product->stock -= $item['quantity'];
                $product->save();
            }

            DB::commit();
            return redirect()->back()->with('success', 'Order created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error creating order: ' . $e->getMessage());
        }
    }
}
