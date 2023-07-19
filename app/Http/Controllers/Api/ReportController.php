<?php

namespace App\Http\Controllers\Api;

use App\Expense;
use App\ExpenseCategory;
use App\Http\Controllers\Controller;
use App\Product;
use App\Product_Sale;
use App\Product_Warehouse;
use App\ProductPurchase;
use App\ProductReturn;
use App\ProductVariant;
use App\PurchaseProductReturn;
use App\Variant;
use App\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function saleReport(Request $request)
    {
        $user = auth()->user();
        $data = $request->all();
        $start_date = $data['start_date'];
        $end_date = $data['end_date'];
        $warehouse_id = $data['warehouse_id'];

        if ($start_date == null) {
            $start_date = date('Y-m') . '-01';
        }
        if ($end_date == null) {
            $end_date = now()->format('Y-m-d');
        }

        $product_id = [];
        $variant_id = [];
        $product_name = [];
        $product_qty = [];
        $lims_product_all = Product::select('id', 'name', 'qty', 'is_variant')->where('is_active', true)->get();

        foreach ($lims_product_all as $product) {
            $lims_product_sale_data = null;
            $variant_id_all = [];
            if ($warehouse_id == 0) {
                if ($product->is_variant)
                    $variant_id_all = Product_Sale::distinct('variant_id')->where('product_id', $product->id)->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->pluck('variant_id');
                else
                    $lims_product_sale_data = Product_Sale::where('product_id', $product->id)->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->first();
            } else {
                if ($product->is_variant)
                    $variant_id_all = DB::table('sales')
                        ->join('product_sales', 'sales.id', '=', 'product_sales.sale_id')
                        ->distinct('variant_id')
                        ->where([
                            ['product_sales.product_id', $product->id],
                            ['sales.warehouse_id', $warehouse_id]
                        ])->whereDate('sales.created_at', '>=', $start_date)
                        ->whereDate('sales.created_at', '<=', $end_date)
                        ->pluck('variant_id');
                else
                    $lims_product_sale_data = DB::table('sales')
                        ->join('product_sales', 'sales.id', '=', 'product_sales.sale_id')->where([
                            ['product_sales.product_id', $product->id],
                            ['sales.warehouse_id', $warehouse_id]
                        ])->whereDate('sales.created_at', '>=', $start_date)
                        ->whereDate('sales.created_at', '<=', $end_date)
                        ->first();
            }
            if ($lims_product_sale_data) {
                $product_name[] = $product->name;
                $product_id[] = $product->id;
                $variant_id[] = null;
                if ($warehouse_id == 0)
                    $product_qty[] = $product->qty;
                else {
                    $product_qty[] = Product_Warehouse::where([
                        ['product_id', $product->id],
                        ['warehouse_id', $warehouse_id]
                    ])->sum('qty');
                }
            } elseif (count($variant_id_all)) {
                foreach ($variant_id_all as $key => $variantId) {
                    $variant_data = Variant::find($variantId);
                    $product_name[] = $product->name . ' [' . $variant_data->name . ']';
                    $product_id[] = $product->id;
                    $variant_id[] = $variant_data->id;
                    if ($warehouse_id == 0)
                        $product_qty[] = ProductVariant::FindExactProduct($product->id, $variant_data->id)->first()->qty;
                    else
                        $product_qty[] = Product_Warehouse::where([
                            ['product_id', $product->id],
                            ['variant_id', $variant_data->id],
                            ['warehouse_id', $warehouse_id]
                        ])->first()->qty;
                }
            }
        }
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        $resp = [
            // 'product_id' => $product_id,
            // 'variant_id' => $variant_id,
            // 'product_name' => $product_name,
            // 'product_qty' => $product_qty,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'warehouse_id' => $warehouse_id,
            'total_amount' => '',
            'total_qty' => '',
            'total_in_stock' => '',
            'data_sale' => [],
            'lims_warehouse_list' => $lims_warehouse_list,
        ];

        if (!empty($product_name)) {
            $total_a = [];
            $total_q = [];
            $total_in = [];
            foreach ($product_id as $key => $pro_id) {
                if ($warehouse_id == 0) {
                    if ($variant_id[$key]) {
                        $sold_price = DB::table('product_sales')->where([
                            ['product_id', $pro_id],
                            ['variant_id', $variant_id[$key]]
                        ])->whereDate('created_at', '>=', $start_date)
                            ->whereDate('created_at', '<=', $end_date)
                            ->sum('total');

                        $product_sale_data = DB::table('product_sales')->where([
                            ['product_id', $pro_id],
                            ['variant_id', $variant_id[$key]]
                        ])->whereDate('created_at', '>=', $start_date)
                            ->whereDate('created_at', '<=', $end_date)
                            ->get();
                    } else {
                        $sold_price = DB::table('product_sales')->where('product_id', $pro_id)
                            ->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->sum('total');

                        $product_sale_data = DB::table('product_sales')->where('product_id', $pro_id)->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->get();
                    }
                } else {
                    if ($variant_id[$key]) {
                        $sold_price = DB::table('sales')
                            ->join('product_sales', 'sales.id', '=', 'product_sales.sale_id')->where([
                                ['product_sales.product_id', $pro_id],
                                ['variant_id', $variant_id[$key]],
                                ['sales.warehouse_id', $warehouse_id]
                            ])->whereDate('sales.created_at', '>=', $start_date)->whereDate('sales.created_at', '<=', $end_date)->sum('total');
                        $product_sale_data = DB::table('sales')
                            ->join('product_sales', 'sales.id', '=', 'product_sales.sale_id')->where([
                                ['product_sales.product_id', $pro_id],
                                ['variant_id', $variant_id[$key]],
                                ['sales.warehouse_id', $warehouse_id]
                            ])->whereDate('sales.created_at', '>=', $start_date)->whereDate('sales.created_at', '<=', $end_date)->get();
                    } else {
                        $sold_price = DB::table('sales')
                            ->join('product_sales', 'sales.id', '=', 'product_sales.sale_id')->where([
                                ['product_sales.product_id', $pro_id],
                                ['sales.warehouse_id', $warehouse_id]
                            ])->whereDate('sales.created_at', '>=', $start_date)->whereDate('sales.created_at', '<=', $end_date)->sum('total');
                        $product_sale_data = DB::table('sales')
                            ->join('product_sales', 'sales.id', '=', 'product_sales.sale_id')->where([
                                ['product_sales.product_id', $pro_id],
                                ['sales.warehouse_id', $warehouse_id]
                            ])->whereDate('sales.created_at', '>=', $start_date)->whereDate('sales.created_at', '<=', $end_date)->get();
                    }
                }

                $sold_qty = 0;

                // return count($product_sale_data);

                foreach ($product_sale_data as $product_sale) {
                    $unit = DB::table('units')->find($product_sale->sale_unit_id);
                    if ($unit) {
                        if ($unit->operator == '*')
                            $sold_qty += $product_sale->qty * $unit->operation_value;
                        elseif ($unit->operator == '/')
                            $sold_qty += $product_sale->qty / $unit->operation_value;
                    } else
                        $sold_qty += $product_sale->qty;
                }

                $item = [
                    'product_id' => $product_id[$key],
                    'product_name' => $product_name[$key],
                    'sold_amount' => $sold_price,
                    'sold_qty' => $sold_qty,
                    'in_stock' => $product_qty[$key]
                ];
                // return $item;
                array_push($resp['data_sale'], $item);
                array_push($total_a, $sold_price);
                array_push($total_q, $sold_qty);
                array_push($total_in, $product_qty[$key]);
            }
            $resp['total_amount'] = array_sum($total_a);
            $resp['total_qty'] = array_sum($total_q);
            $resp['total_in_stock'] = array_sum($total_in);
        }

        return response()->json($resp);
        return view('backend.report.sale_report', compact('product_id', 'variant_id', 'product_name', 'product_qty', 'start_date', 'end_date', 'lims_warehouse_list', 'warehouse_id'));
    }

    public function expense_category(){
        $cat = ExpenseCategory::where('is_active', 1)->get();

        return response()->json($cat);
    }

    public function expenseReport(Request $request)
    {
        $user = auth()->user();
        $data = $request->all();
        $start_date = $data['start_date'];
        $end_date = $data['end_date'];
        $warehouse_id = $data['warehouse_id'];

        // if ($warehouse_id == null || $warehouse_id == 0) {
        //     $warehouse_id = 1;
        // }

        if ($start_date == null) {
            $start_date = date('Y-m') . '-01';
        }
        if ($end_date == null) {
            $end_date = now()->format('Y-m-d');
        }

        if($warehouse_id == 0 || $warehouse_id == null){
            $expenses = Expense::with('expenseCategory')->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->orderBy('created_at', 'desc')->get();
        }else{
            $expenses = Expense::with('expenseCategory')->where('warehouse_id', $warehouse_id)->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->orderBy('created_at', 'desc')->get();
        }

        $resp = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'warehouse_id' => $warehouse_id,
            'data' => [],
        ];

        foreach ($expenses as $e) {
            $item = [
                'id' => $e['id'],
                'category_name' => $e['expenseCategory']['name'] ?? 'Invalid Category name',
                'reference_no' => $e['reference_no'],
                'amount' => $e['amount'],
                'warehouse_id' => $e['warehouse_id'],
                'note' => $e['note'],
            ];

            array_push($resp['data'], $item);
        }

        return response()->json($resp);
    }

    public function stockAlert(Request $request)
    {
        $warehouse_id = $request->warehouse_id;

        if ($warehouse_id == null) {
            $alert = Product_Warehouse::with('product')->whereHas('product', function ($q) {
                $q->where('is_active', 1);
            })->get();
        } else {
            $alert = Product_Warehouse::with('product')->where('warehouse_id', $warehouse_id)->whereHas('product', function ($q) {
                $q->where('is_active', 1);
            })->get();
        }

        $resp = ['warehouse_id' => $warehouse_id, 'data' => []];

        foreach ($alert as $a) {
            $item = [
                'id' => $a['product']['id'],
                'name' => $a['product']['name'],
                'qty' => $a['product']['qty'],
                'warehouse_id' => $a['warehouse_id'],
            ];
            if ($a['product']['alert_quantity'] > $a['qty']) {
                array_push($resp['data'], $item);
            }
        }


        return response()->json($resp);
    }

    public function purchaseReport(Request $request)
    {
        $user = auth()->user();
        $data = $request->all();
        $start_date = $data['start_date'];
        $end_date = $data['end_date'];
        $warehouse_id = $data['warehouse_id'];

        if ($start_date == null) {
            $start_date = date('Y-m') . '-01';
        }
        if ($end_date == null) {
            $end_date = now()->format('Y-m-d');
        }

        $product_id = [];
        $variant_id = [];
        $product_name = [];
        $product_qty = [];
        $lims_product_all = Product::select('id', 'name', 'qty', 'is_variant')->where('is_active', true)->get();
        foreach ($lims_product_all as $product) {
            $lims_product_purchase_data = null;
            $variant_id_all = [];
            if ($warehouse_id == 0 || $warehouse_id ==  null) {
                if ($product->is_variant)
                    $variant_id_all = ProductPurchase::distinct('variant_id')->where('product_id', $product->id)->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->pluck('variant_id');
                else
                    $lims_product_purchase_data = ProductPurchase::where('product_id', $product->id)->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->first();
            } else {
                if ($product->is_variant)
                    $variant_id_all = DB::table('purchases')
                        ->join('product_purchases', 'purchases.id', '=', 'product_purchases.purchase_id')
                        ->distinct('variant_id')
                        ->where([
                            ['product_purchases.product_id', $product->id],
                            ['purchases.warehouse_id', $warehouse_id]
                        ])->whereDate('purchases.created_at', '>=', $start_date)
                        ->whereDate('purchases.created_at', '<=', $end_date)
                        ->pluck('variant_id');
                else
                    $lims_product_purchase_data = DB::table('purchases')
                        ->join('product_purchases', 'purchases.id', '=', 'product_purchases.purchase_id')->where([
                            ['product_purchases.product_id', $product->id],
                            ['purchases.warehouse_id', $warehouse_id]
                        ])->whereDate('purchases.created_at', '>=', $start_date)
                        ->whereDate('purchases.created_at', '<=', $end_date)
                        ->first();
            }

            if ($lims_product_purchase_data) {
                $product_name[] = $product->name;
                $product_id[] = $product->id;
                $variant_id[] = null;
                if ($warehouse_id == 0)
                    $product_qty[] = $product->qty;
                else
                    $product_qty[] = Product_Warehouse::where([
                        ['product_id', $product->id],
                        ['warehouse_id', $warehouse_id]
                    ])->sum('qty');
            } elseif (count($variant_id_all)) {
                foreach ($variant_id_all as $key => $variantId) {
                    $variant_data = Variant::find($variantId);
                    $product_name[] = $product->name . ' [' . $variant_data->name . ']';
                    $product_id[] = $product->id;
                    $variant_id[] = $variant_data->id;
                    if ($warehouse_id == 0)
                        $product_qty[] = ProductVariant::FindExactProduct($product->id, $variant_data->id)->first()->qty;
                    else
                        $product_qty[] = Product_Warehouse::where([
                            ['product_id', $product->id],
                            ['variant_id', $variant_data->id],
                            ['warehouse_id', $warehouse_id]
                        ])->first()->qty;
                }
            }
        }

        $lims_warehouse_list = Warehouse::where('is_active', true)->get();


        $resp = [
            // 'product_id' => $product_id,
            // 'variant_id' => $variant_id,
            // 'product_name' => $product_name,
            // 'product_qty' => $product_qty,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'warehouse_id' => $warehouse_id,
            'purchased_amount' => '',
            'purchased_qty' => '',
            'total_in_stock' => '',
            'data_purchased' => [],
            'lims_warehouse_list' => $lims_warehouse_list,
        ];

        if (!empty($product_name)) {
            $total_a = [];
            $total_q = [];
            $total_in = [];
            foreach ($product_id as $key => $pro_id) {
                if ($warehouse_id == 0) {
                    if ($variant_id[$key]) {
                        $purchased_cost = DB::table('product_purchases')->where([
                            ['product_id', $pro_id],
                            ['variant_id', $variant_id[$key]]
                        ])->whereDate('created_at', '>=', $start_date)
                            ->whereDate('created_at', '<=', $end_date)
                            ->sum('total');

                        $product_purchase_data = DB::table('product_purchases')->where([
                            ['product_id', $pro_id],
                            ['variant_id', $variant_id[$key]]
                        ])->whereDate('created_at', '>=', $start_date)
                            ->whereDate('created_at', '<=', $end_date)
                            ->get();
                    } else {
                        $purchased_cost = DB::table('product_purchases')->where('product_id', $pro_id)->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->sum('total');

                        $product_purchase_data = DB::table('product_purchases')->where('product_id', $pro_id)->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->get();
                    }
                } else {
                    if ($variant_id[$key]) {
                        $purchased_cost = DB::table('purchases')
                            ->join('product_purchases', 'purchases.id', '=', 'product_purchases.purchase_id')->where([
                                ['product_purchases.product_id', $pro_id],
                                ['product_purchases.variant_id', $variant_id[$key]],
                                ['purchases.warehouse_id', $warehouse_id]
                            ])->whereDate('purchases.created_at', '>=', $start_date)->whereDate('purchases.created_at', '<=', $end_date)->sum('total');
                        $product_purchase_data = DB::table('purchases')
                            ->join('product_purchases', 'purchases.id', '=', 'product_purchases.purchase_id')->where([
                                ['product_purchases.product_id', $pro_id],
                                ['product_purchases.variant_id', $variant_id[$key]],
                                ['purchases.warehouse_id', $warehouse_id]
                            ])->whereDate('purchases.created_at', '>=', $start_date)->whereDate('purchases.created_at', '<=', $end_date)->get();
                    } else {
                        $purchased_cost = DB::table('purchases')
                            ->join('product_purchases', 'purchases.id', '=', 'product_purchases.purchase_id')->where([
                                ['product_purchases.product_id', $pro_id],
                                ['purchases.warehouse_id', $warehouse_id]
                            ])->whereDate('purchases.created_at', '>=', $start_date)->whereDate('purchases.created_at', '<=', $end_date)->sum('total');
                        $product_purchase_data = DB::table('purchases')
                            ->join('product_purchases', 'purchases.id', '=', 'product_purchases.purchase_id')->where([
                                ['product_purchases.product_id', $pro_id],
                                ['purchases.warehouse_id', $warehouse_id]
                            ])->whereDate('purchases.created_at', '>=', $start_date)->whereDate('purchases.created_at', '<=', $end_date)->get();
                    }
                }

                $purchased_qty = 0;

                // return count($product_sale_data);

                foreach ($product_purchase_data as $product_purchase) {
                    $unit = DB::table('units')->find($product_purchase->purchase_unit_id);
                    if ($unit->operator == '*') {
                        $purchased_qty += $product_purchase->qty * $unit->operation_value;
                    } elseif ($unit->operator == '/') {
                        $purchased_qty += $product_purchase->qty / $unit->operation_value;
                    }
                }

                $item = [
                    'product_id' => $product_id[$key],
                    'product_name' => $product_name[$key],
                    'purchased_amount' => $purchased_cost,
                    'purchased_qty' => $purchased_qty,
                    'in_stock' => $product_qty[$key],
                ];
                // return $item;
                array_push($resp['data_purchased'], $item);
                array_push($total_a, $purchased_cost);
                array_push($total_q, $purchased_qty);
                array_push($total_in, $product_qty[$key]);
            }
            $resp['purchased_amount'] = array_sum($total_a);
            $resp['purchased_qty'] = array_sum($total_q);
            $resp['total_in_stock'] = array_sum($total_in);
        }

        return $resp;
        return view('backend.report.purchase_report', compact('product_id', 'variant_id', 'product_name', 'product_qty', 'start_date', 'end_date', 'lims_warehouse_list', 'warehouse_id'));
    }

    public function stockReport(Request $request)
    {
        $user = auth()->user();
        $warehouse_id = $request->warehouse_id;
        if ($warehouse_id == null || $warehouse_id == 0) {
            $warehouse_id = 1;
        }

        $product = Product_Warehouse::with('product')->whereHas('product', function ($q) {
            $q->where('is_active', 1);
        })->where('warehouse_id', $warehouse_id)->get();

        $resp = [
            'total_item' => count($product),
            'warehouse_id' => $warehouse_id,
            'stock' => []
        ];

        foreach ($product as $p) {
            $data = [];
            $data['product_id'] = $p['product']['id'];
            $data['warehouse_id'] = $p['warehouse_id'];
            $data['name'] = $p['product']['name'];
            $data['qty'] = $p['qty'];
            $data['price'] = $p['price'] ?? Product::find($p['product_id'])['price'];

            array_push($resp['stock'], $data);
        }

        return $resp;
    }
}
