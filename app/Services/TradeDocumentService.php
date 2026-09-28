<?php

namespace App\Services;

class TradeDocumentService
{
    public function savePurchaseRequestItems($purchaseRequest, array $items)
    {
        $purchaseRequest->items()->delete();
        foreach ($items as $item) {
            $purchaseRequest->items()->create([
                'item_id' => $item['item_id'],
                'quantity' => $item['quantity'],
                'unit_id' => $item['unit_id']
            ]);
        }
    }

    public function savePurchaseOrderItems($purchaseOrder, array $items)
    {
        $purchaseOrder->items()->delete();
        $totalAmount = 0;

        foreach ($items as $item) {
            $subtotal = $item['quantity'] * $item['unit_price'];
            $taxAmount = $subtotal * ($item['tax_percent'] / 100);
            $netAmount = $subtotal + $taxAmount;
            $totalAmount += $netAmount;

            $purchaseOrder->items()->create([
                'item_id' => $item['item_id'],
                'unit_id' => $item['unit_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $subtotal,
                'tax_percent' => $item['tax_percent'],
                'tax_amount' => $taxAmount,
                'net_amount' => $netAmount,
            ]);
        }

        $purchaseOrder->update(['total_amount' => $totalAmount]);
    }

    public function saveGoodReceivedNoteItems($grn, array $items)
    {
        $grn->items()->delete();
        $totalAmount = 0;

        foreach ($items as $item) {
            $subtotal = $item['quantity'] * $item['unit_price'];
            $totalAmount += $subtotal;

            $grn->items()->create([
                'item_id' => $item['item_id'],
                'unit_id' => $item['unit_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $subtotal,
            ]);
        }

        $grn->update(['total_amount' => $totalAmount]);
    }

    public function savePurchaseInvoiceItems($purchaseInvoice, array $items, $globalAdjustments = [])
    {
        $purchaseInvoice->items()->delete();
        $totalAmount = 0;

        foreach ($items as $item) {
            $subtotal = ($item['quantity'] * $item['unit_price']) - ($item['discount_amount'] ?? 0) + ($item['tax_amount'] ?? 0);
            $totalAmount += $subtotal;

            $purchaseInvoice->items()->create([
                'item_id' => $item['item_id'],
                'unit_id' => $item['unit_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount_amount' => $item['discount_amount'] ?? 0,
                'tax_amount' => $item['tax_amount'] ?? 0,
                'subtotal' => $subtotal,
            ]);
        }

        $globalDiscount = $globalAdjustments['discount_amount'] ?? 0;
        $globalTax = $globalAdjustments['tax_amount'] ?? 0;
        $shipping = $globalAdjustments['shipping_amount'] ?? 0;

        $totalAmount = $totalAmount - $globalDiscount + $globalTax + $shipping;
        $purchaseInvoice->update(['total_amount' => $totalAmount]);
    }

    public function savePurchaseReturnItems($purchaseReturn, array $items, $globalAdjustments = [])
    {
        $purchaseReturn->items()->delete();
        $totalAmount = 0;

        foreach ($items as $item) {
            $subtotal = ($item['quantity'] * $item['unit_price']) - ($item['discount_amount'] ?? 0) + ($item['tax_amount'] ?? 0);
            $totalAmount += $subtotal;

            $purchaseReturn->items()->create([
                'item_id' => $item['item_id'],
                'unit_id' => $item['unit_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount_amount' => $item['discount_amount'] ?? 0,
                'tax_amount' => $item['tax_amount'] ?? 0,
                'subtotal' => $subtotal,
            ]);
        }

        $globalDiscount = $globalAdjustments['discount_amount'] ?? 0;
        $globalTax = $globalAdjustments['tax_amount'] ?? 0;
        $shipping = $globalAdjustments['shipping_amount'] ?? 0;

        $totalAmount = $totalAmount - $globalDiscount + $globalTax + $shipping;
        $purchaseReturn->update(['total_amount' => $totalAmount]);
    }
}
