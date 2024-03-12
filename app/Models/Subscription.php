<?php

namespace App\Models;

use App\Services\CachingService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Subscription extends Model {
    use HasFactory;

    protected $fillable = [
        'package_id',
        'school_id',
        'name',
        'student_charge',
        'staff_charge',
        'start_date',
        'end_date',
        'billing_cycle',
    ];

    protected $appends = ['status','bill_date','due_date'];

    public function scopeOwner() {

        if(Auth::user()->school_id){
            return $this->where('school_id', Auth::user()->school_id);
        }

        if (!Auth::user()->school_id) {
            return $this;
        }

        return $this;
    }


    public function package() {
        return $this->belongsTo(Package::class)->withTrashed();
    }

    /**
     * Get the school that owns the Subscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function school()
    {
        return $this->belongsTo(School::class)->withTrashed();
    }

    public function features()
    {
        return $this->hasManyThrough(Feature::class, PackageFeature::class,'package_id','id','package_id','feature_id');
    }


    public function subscription_bill()
    {
        return $this->hasOne(SubscriptionBill::class);
    }

    /**
     * Get all of the subscription_feature for the Subscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscription_feature()
    {
        return $this->hasMany(SubscriptionFeature::class);
    }

    public function getStatusAttribute() {
        $today_date = Carbon::now()->format('Y-m-d');

        // 1 => Current Cycle, 2 => Paid, 3 => Over Due, 4 => Failed, 5 => Pending, 6 => Next Billing Cycle, 7 => Unpaid
        if ($this->start_date <= $today_date && $this->end_date >= $today_date) {
            if (!$this->subscription_bill) {
                return 1; // Current Billing Cycle
            }
        }
        if ($this->start_date > $today_date) {
            return 6; // Next Billing Cycle
        }

        // TODO : This subscription_bill relationship called using Lazy load which will
        // Cause multiple query executions, so execute this only if the relationship called with Eager loading
        if ($this->subscription_bill) {
            if ($this->subscription_bill->transaction) {
                if ($this->subscription_bill->transaction->payment_status == 'succeed') {
                    return 2; // Paid
                }
                if ($this->subscription_bill->transaction->payment_status == 'failed') {
                    return 4; // Failed
                }
                if ($this->subscription_bill->transaction->payment_status == 'pending') {
                    return 5; // Pending
                }
            } else {
                $setting = app(CachingService::class)->getSystemSettings();
                // If bill amount is 0 then set by default as paid
                if ($this->subscription_bill->amount == 0) {
                    return 2;
                }
                if ($this->subscription_bill->due_date < $today_date) {
                    return 3; // Overdue
                } else {
                    return 7; // Unpaid
                }
            }
        }

        return 0;
    }

    public function getBillDateAttribute() {
        return Carbon::parse($this->end_date)->addDays(1)->format('Y-m-d');
    }
    public function getDueDateAttribute() {
        $setting = app(CachingService::class)->getSystemSettings();
        return Carbon::parse($this->end_date)->addDays($setting['additional_billing_days'])->format('Y-m-d');
    }

    public function getExtraBillingStatusAttribute() {
        $today_date = Carbon::now()->format('Y-m-d');
        $setting = app(CachingService::class)->getSystemSettings();
        $extra_day = Carbon::parse($this->end_date)->addDays($setting['additional_billing_days'])->format('Y-m-d');
        $status = 0;
        if ($today_date <= $extra_day) {
            $status = 1;
        }
        return $status;
    }

}
