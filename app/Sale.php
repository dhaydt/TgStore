<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable =[
        "reference_no", "user_id", "cash_register_id", "customer_id", "warehouse_id", "biller_id", "item", "total_qty", "total_discount", "total_tax", "total_price", "order_tax_rate", "order_tax", "order_discount_type", "order_discount_value", "order_discount", "coupon_id", "coupon_discount", "shipping_cost", "grand_total", "sale_status", "payment_status", "paid_amount", "document", "sale_note", "staff_note", "created_at"
    ];

    public function biller()
    {
    	return $this->belongsTo('App\Biller');
    }

    public function customer()
    {
    	return $this->belongsTo('App\Customer');
    }

    /**
     * Get all of the comments for the Sale
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function product_sale(): HasMany
    {
        return $this->hasMany(Product_Sale::class, 'sale_id', 'id');
    }

    public function warehouse()
    {
    	return $this->belongsTo('App\Warehouse');
    }

    public function user()
    {
    	return $this->belongsTo('App\User');
    }
}
