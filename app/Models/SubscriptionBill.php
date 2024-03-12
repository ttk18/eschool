<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SubscriptionBill extends Model
{
    use HasFactory;
    protected $fillable = ['subscription_id','amount','total_student','total_staff','payment_transaction_id','due_date','school_id'];

    public function scopeOwner()
    {
        if (Auth::user()->school_id) {
            return $this->where('school_id',Auth::user()->school_id);
        }
        return $this;
    }

    /**
     * Get the subscription that owns the SubscriptionBill
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the transaction that owns the SubscriptionBill
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transaction()
    {
        return $this->belongsTo(PaymentTransaction::class,'payment_transaction_id','id');
    }
}
