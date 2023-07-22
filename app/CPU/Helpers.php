<?php

namespace App\CPU;

use App\Product;
use App\ProductBatch;
use App\ProductPurchase;
use App\ProductReturn;
use App\ProductVariant;
use App\Unit;

class Helpers
{
    public static function imgUrl($type)
    {
        if ($type == 'product') {
            return 'public/images/product/';
        }
    }

    public static function errMsg($status, $field, $pesan)
    {
        $resp = [
            "message" => 'The given data was invalid.',
            $status => [
                $field => [
                    $pesan
                ]
            ]
        ];

        return $resp;
    }
    public static function responseApi($status, $message)
    {
        if ($status == 'fail') {
            $response = [
                'status' => $status,
                'message' => $message
            ];
            return $response;
        }
        if ($status == 'success') {
            $response = [
                'status' => $status,
                'message' => $message
            ];
            return $response;
        }
    }

    public static function calculateAverageCOGS($product_sale_data)
    {
        $product_cost = 0;
        foreach ($product_sale_data as $key => $product_sale) {
            $product_data = Product::select('type', 'product_list', 'variant_list', 'qty_list')->find($product_sale->product_id);
            if ($product_data->type == 'combo') {
                $product_list = explode(",", $product_data->product_list);
                if ($product_data->variant_list)
                    $variant_list = explode(",", $product_data->variant_list);
                else
                    $variant_list = [];
                $qty_list = explode(",", $product_data->qty_list);

                foreach ($product_list as $index => $product_id) {
                    if (count($variant_list) && $variant_list[$index]) {
                        $product_purchase_data = ProductPurchase::where([
                            ['product_id', $product_id],
                            ['variant_id', $variant_list[$index]]
                        ])
                            ->select('recieved', 'purchase_unit_id', 'total')
                            ->get();
                    } else {
                        $product_purchase_data = ProductPurchase::where('product_id', $product_id)
                            ->select('recieved', 'purchase_unit_id', 'total')
                            ->get();
                    }
                    $total_received_qty = 0;
                    $total_purchased_amount = 0;
                    $sold_qty = $product_sale->sold_qty * $qty_list[$index];
                    foreach ($product_purchase_data as $key => $product_purchase) {
                        $purchase_unit_data = Unit::select('operator', 'operation_value')->find($product_purchase->purchase_unit_id);
                        if ($purchase_unit_data->operator == '*')
                            $total_received_qty += $product_purchase->recieved * $purchase_unit_data->operation_value;
                        else
                            $total_received_qty += $product_purchase->recieved / $purchase_unit_data->operation_value;
                        $total_purchased_amount += $product_purchase->total;
                    }
                    if ($total_received_qty)
                        $averageCost = $total_purchased_amount / $total_received_qty;
                    else
                        $averageCost = 0;
                    $product_cost += $sold_qty * $averageCost;
                }
            } else {
                if ($product_sale->product_batch_id) {
                    $product_purchase_data = ProductPurchase::where([
                        ['product_id', $product_sale->product_id],
                        ['product_batch_id', $product_sale->product_batch_id]
                    ])
                        ->select('recieved', 'purchase_unit_id', 'total')
                        ->get();
                } elseif ($product_sale->variant_id) {
                    $product_purchase_data = ProductPurchase::where([
                        ['product_id', $product_sale->product_id],
                        ['variant_id', $product_sale->variant_id]
                    ])
                        ->select('recieved', 'purchase_unit_id', 'total')
                        ->get();
                } else {
                    $product_purchase_data = ProductPurchase::where('product_id', $product_sale->product_id)
                        ->select('recieved', 'purchase_unit_id', 'total')
                        ->get();
                }
                $total_received_qty = 0;
                $total_purchased_amount = 0;
                if ($product_sale->sale_unit_id) {
                    $sale_unit_data = Unit::select('operator', 'operation_value')->find($product_sale->sale_unit_id);
                    if ($sale_unit_data->operator == '*')
                        $sold_qty = $product_sale->sold_qty * $sale_unit_data->operation_value;
                    else
                        $sold_qty = $product_sale->sold_qty / $sale_unit_data->operation_value;
                } else {
                    $sold_qty = $product_sale->sold_qty;
                }
                foreach ($product_purchase_data as $key => $product_purchase) {
                    $purchase_unit_data = Unit::select('operator', 'operation_value')->find($product_purchase->purchase_unit_id);
                    if ($purchase_unit_data->operator == '*')
                        $total_received_qty += $product_purchase->recieved * $purchase_unit_data->operation_value;
                    else
                        $total_received_qty += $product_purchase->recieved / $purchase_unit_data->operation_value;
                    $total_purchased_amount += $product_purchase->total;
                }
                if ($total_received_qty)
                    $averageCost = $total_purchased_amount / $total_received_qty;
                else
                    $averageCost = 0;
                $product_cost += $sold_qty * $averageCost;
            }
        }
        return $product_cost;
    }
    public static function productReturnData($id)
    {
        $lims_product_return_data = ProductReturn::where('return_id', $id)->get();
        $data = [];
        foreach ($lims_product_return_data as $key => $product_return_data) {
            $product = Product::find($product_return_data->product_id);
            if($product_return_data->sale_unit_id != 0){
                $unit_data = Unit::find($product_return_data->sale_unit_id);
                $unit = $unit_data->unit_code;
            }
            else
                $unit = '';
            if($product_return_data->variant_id) {
                $lims_product_variant_data = ProductVariant::select('item_code')->FindExactProduct($product_return_data->product_id, $product_return_data->variant_id)->first();
                $product->code = $lims_product_variant_data->item_code;
            }
            if($product_return_data->product_batch_id) {
                $product_batch_data = ProductBatch::select('batch_no')->find($product_return_data->product_batch_id);
                $product_return[7][$key] = $product_batch_data->batch_no;
            }
            else
                // $product_return[7][$key] = 'N/A';
            $product_return['product_name'][$key] = $product->name . ' [' . $product->code . ']';
            if($product_return_data->imei_number){
                $product_return[0][$key] .= '<br>IMEI or Serial Number: ' . $product_return_data->imei_number;
            }
            $product_return['qty'][$key] = $product_return_data->qty;
            // $product_return[2][$key] = $unit;
            // $product_return[3][$key] = $product_return_data->tax;
            // $product_return[4][$key] = $product_return_data->tax_rate;
            // $product_return[5][$key] = $product_return_data->discount;
            $product_return['total_price'][$key] = $product_return_data->total;

            $item = [
                'product_name' => $product->name,
                'qty' => $product_return_data->qty,
                'price' => $product_return_data->total,
            ];

            array_push($data, $item);
        }
        return $data;
    }
}
