<?php

namespace App\Http\Controllers\Api;

use App\Account;
use App\Biller;
use App\Customer;
use App\GiftCard;
use App\Http\Controllers\Controller;
use App\PosSetting;
use App\Product;
use App\Product_Warehouse;
use App\RewardPointSetting;
use App\Tax;
use App\User;
use App\Warehouse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class TransactionController extends Controller
{
    public function index(Request $request){
        $role = Role::with('permissions')->find(auth()->user()->role_id);

        if($role->hasPermissionTo('sales-add')) {
            $lims_customer_list = Customer::where('is_active', true)->get();
            if(auth()->user()->role_id > 2) {
                $lims_warehouse_list = Warehouse::where([
                    ['is_active', true],
                    ['id', auth()->user()->warehouse_id]
                ])->get();
                $lims_biller_list = Biller::where([
                    ['is_active', true],
                    ['id', auth()->user()->biller_id]
                ])->get();
            }
            else {
                $lims_warehouse_list = Warehouse::where('is_active', true)->get();
                $lims_biller_list = Biller::where('is_active', true)->get();
            }

            $user = User::find(auth()->id());
            $user['warehouse_id'] = $user['warehouse_id'] ?? 1;

            $products = Product_Warehouse::with('product')->where('warehouse_id', $user['warehouse_id'])->get();
            $newProduct = [];

            foreach($products as $p){
                $item = $p['product'];
                if($item['is_active'] == 1){
                    $data['id'] = $item['id'];
                    $data['name'] = $item['name'];
                    $data['code'] = $item['code'];
                    $data['type'] = $item['type'];
                    $data['barcode_symbology'] = $item['barcode_symbology'];
                    $data['brand_id'] = $item['brand_id'];
                    $data['price'] = $item['price'];
                    $data['qty'] = $p['qty'];
    
                    array_push($newProduct, $data);
                }
            }


            $data = [
                "customer_list" => $lims_customer_list,
                "biller_list" => $lims_biller_list,
                "product_list" => $newProduct,
                // "warehouse_list" => $lims_warehouse_list,
                // "pos_setting_data" => $lims_pos_setting_data,
                // "tax_list" => $lims_tax_list,
                // "reward_point_setting_data" => $lims_reward_point_setting_data,
            ];

            return response()->json($data);

        }
        else{
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
        }
    }

    public function post_transaction(Request $request){
        // _token: 9E0UjmjaovzF5qo6wFr0FH4rM5H7BN2CkU8OTwfS
        // created_at: 
        // reference_no: 
        // warehouse_id_hidden: 1
        // warehouse_id: 1
        // biller_id_hidden: 1
        // biller_id: 1
        // customer_id_hidden: 1
        // customer_id: 1
        // product_code_name: 
        // product_batch_id[]: 
        // qty[]: 2
        // product_code[]: AX3GB
        // product_id[]: 1
        // sale_unit[]: Pcs
        // net_unit_price[]: 17000.00
        // discount[]: 0.00
        // tax_rate[]: 0.00
        // tax[]: 0.00
        // subtotal[]: 34000.00
        // imei_number[]: 
        // total_qty: 2
        // total_discount: 0.00
        // total_tax: 0.00
        // total_price: 34000.00
        // item: 1
        // order_tax: 0.00
        // grand_total: 34000.00
        // used_points: 
        // coupon_discount: 
        // sale_status: 1
        // coupon_active: 
        // coupon_id: 
        // coupon_discount: 
        // pos: 1
        // draft: 0
        // paying_amount: 50000
        // paid_amount: 34000.00
        // paid_by_id: 1
        // gift_card_id: 
        // cheque_no: 
        // payment_note: cash
        // sale_note: -
        // staff_note: -
        // order_discount_type: Flat
        // order_discount_value: 
        // order_discount: 0
        // order_tax_rate: 0
        // shipping_cost: 
        
    }

    public function scan_product(Request $request){
        $this->validate($request, [
            'code' => 'required',
        ]);

        $product = Product::where('code', 'like', '%'.$request['code'].'%')->orWhere('name', 'like', '%'.$request['code'].'%')->orWhere('barcode_symbology', 'like', '%'.$request['code'].'%')->first();
        $data = [];
        if($product){
            $data = [
                "id" => $product['id'],
                "name" => $product['name'],
                "code" => $product['code'],
                "barcode_symbology" => $product['barcode_symbology'],
                "price" => $product['price'],
            ];
        }

        return response()->json($data);
    }
}
