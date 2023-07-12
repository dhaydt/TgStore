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
}
