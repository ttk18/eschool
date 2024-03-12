<?php

namespace App\Console\Commands;

use App\Models\AddonSubscription;
use App\Models\Package;
use App\Models\SchoolSetting;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\SubscriptionBill;
use App\Models\SubscriptionFeature;
use App\Models\User;
use App\Models\UserStatusForNextCycle;
use App\Services\CachingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

class SubscriptionBillCron extends Command
{
    private CachingService $cache;

    public function __construct(CachingService $cache)
    {
        parent::__construct();
        $this->cache = $cache;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptionBill:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        // Delete current subscription plan if not cleared previously bills

        $get_subscription_ids_for_unclear_past_bill = array();
        $unclear_addon_soft_delete = array();

        $today_date = Carbon::now()->format('Y-m-d');
        $settings = app(CachingService::class)->getSystemSettings();
        // $extra_day = Carbon::parse($today_date)->addDays(($settings['additional_billing_days'] - 1))->format('Y-m-d');

        $subscriptionBill = SubscriptionBill::with('subscription')->whereHas('transaction', function($q) {
            $q->whereNot('payment_status',"succeed");
        })->orWhereNull('payment_transaction_id')->where('due_date','<',$today_date)->get();

        $end_date = Carbon::yesterday()->format('Y-m-d');
        foreach ($subscriptionBill as $key => $bill) {

            $subscriptions = Subscription::where('school_id',$bill->school_id)->where('start_date','<=',$today_date)->where('end_date','>=',$today_date)->get();
            
            foreach ($subscriptions as $key => $subscription) {
                $end_paln = Subscription::find($subscription->id);
                $end_paln->end_date = $end_date;
                $end_paln->save();
                $get_subscription_ids_for_unclear_past_bill[] = $subscription->id;
            }

            // Delete upcoming plan if selected
            Subscription::where('school_id',$bill->school_id)->where('start_date','>',$today_date)->delete();
            

            $addon_subscriptions = AddonSubscription::where('school_id',$bill->school_id)->where('start_date','<=',$today_date)->where('end_date','>=',$today_date)->get();
            
            foreach ($addon_subscriptions as $key => $addon) {
                $addon_subscription = AddonSubscription::find($addon->id);
                $addon_subscription->end_date = $end_date;
                $addon_subscription->save();
                $unclear_addon_soft_delete[] = $addon->id;
            }

            
            // Delete upcoming plan if selected
            AddonSubscription::where('school_id',$bill->school_id)->where('start_date','>',$today_date)->delete();

            $school_settings = SchoolSetting::where('school_id',$bill->school_id)->where('name','auto_renewal_plan')->first();
            $school_settings->data = 0;
            $school_settings->save();

            // Remove cache
            $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.FEATURES'),$bill->school_id);
        }

        // End if not clear past bills


        // Bill Generation


        $today_date = Carbon::now()->format('Y-m-d');
        $today_date_without_format = Carbon::yesterday();
        $subscriptions = Subscription::whereDate('end_date', $today_date_without_format->format('Y-m-d'))
        ->whereHas('package',function($q) {
            $q->where('is_trial',0);
        })
        ->doesnthave('subscription_bill')
        ->get();

        foreach ($subscriptions as $subscription) {

            $subscription_date = Carbon::createFromDate($subscription->end_date);
            //
            if ($today_date_without_format->isSameDay($subscription_date)) {
                // Count Students
                $students = User::withTrashed()->where(function ($q) use ($subscription) {
                    $q->whereBetween('deleted_at', [$subscription->start_date, $subscription->end_date]);
                })->orWhereNull('deleted_at')->role('Student')->where('school_id', $subscription->school_id)->count();

                // Count Staffs
                $staffs = Staff::whereHas('user', static function ($q) use ($subscription) {
                    $q->where(function ($q) use ($subscription) {
                        $q->withTrashed()->whereBetween('deleted_at', [$subscription->start_date, $subscription->end_date])
                            ->orWhereNull('deleted_at');
                    })->where('school_id', $subscription->school_id);
                })->count();

                $addons = AddonSubscription::where('school_id', $subscription->school_id)->where('end_date', $subscription->end_date)->get();
                $total_addon = 0;
                $soft_delete_addon_ids = array();
                foreach ($addons as $addon) {
                    $total_addon += $addon->price;
                    $soft_delete_addon_ids[] = $addon->id;

                }

                $today_date = Carbon::now()->format('Y-m-d');
                $start_date = Carbon::parse($subscription->start_date);
                $usage_days = $start_date->diffInDays($subscription->end_date) + 1;
                $bill_cycle_days = $subscription->billing_cycle;

                // $student_charges = ($students * $subscription->student_charge) / $subscription->billing_cycle;
                // $staff_charges = ($staffs * $subscription->staff_charge) / $subscription->billing_cycle;

                $student_charges = number_format((($usage_days * $subscription->student_charge) / $bill_cycle_days), 4) * $students;
                $staff_charges = number_format((($usage_days * $subscription->staff_charge) / $bill_cycle_days), 4) * $staffs;

                $total_amount = $student_charges + $staff_charges;
                $total_amount += $total_addon;

                
                
                $settings = app(CachingService::class)->getSystemSettings();
                $due_date = Carbon::parse($subscription->end_date)->addDays(($settings['additional_billing_days']))->format('Y-m-d');

                $description = '';
                if (in_array($subscription->id, $get_subscription_ids_for_unclear_past_bill)) {
                    $description = 'Clear past dues for uninterrupted service due to outstanding balance on previous bill.';
                }

                $subscription_bill_data[] = [
                    'subscription_id' => $subscription->id,
                    'amount' => $total_amount,
                    'total_student' => $students,
                    'total_staff' => $staffs,
                    'school_id' => $subscription->school_id,
                    'payment_transaction_id' => null,
                    'due_date' => $due_date,
                    'description' => $description
                    
                ];

                SubscriptionBill::upsert($subscription_bill_data, ['subscription_id', 'school_id'], ['amount', 'total_student', 'total_staff', 'payment_transaction_id','due_date']);


                SubscriptionFeature::where('subscription_id',$subscription->id)->delete();
                

                // Check auto-renew plan is enabled
                $auto_renewal_plan = SchoolSetting::where('name', 'auto_renewal_plan')->where('data', 1)->where('school_id', $subscription->school_id)->first();
                if ($auto_renewal_plan) {
                    $check_subscription = Subscription::whereDate('start_date', '<=', $today_date)->whereDate('end_date', '>=', $today_date)->where('school_id', $subscription->school_id)->first();

                    // If already change plan for next billing cycle or not
                    if (!$check_subscription) {
                        // Not set, add previous subscription and addons
                        $previous_subscription = Subscription::where('school_id', $subscription->school_id)->orderBy('end_date', 'DESC')->first();

                        $settings = app(CachingService::class)->getSystemSettings();
                        $end_date = Carbon::parse($today_date)->addDays(($settings['billing_cycle_in_days'] - 1))->format('Y-m-d');

                        $package = Package::with('package_feature')->find($previous_subscription->package_id);

                        $upcoming_subscription_data = [
                            'school_id' => $subscription->school_id,
                            'package_id' => $package->id,
                            'name' => $package->name,
                            'student_charge' => $package->student_charge,
                            'staff_charge' => $package->staff_charge,
                            'start_date' => $today_date,
                            'end_date' => $end_date,
                            'billing_cycle' => $settings['billing_cycle_in_days']
                        ];
                        $new_subscription_plan = Subscription::create($upcoming_subscription_data);

                        $subscription_features = array();
                        foreach ($package->package_feature as $key => $feature) {
                            $subscription_features[] = [
                                'subscription_id' => $new_subscription_plan->id,
                                'feature_id' => $feature->feature_id
                            ];
                        }

                        SubscriptionFeature::upsert($subscription_features,['subscription_id','feature_id'],['subscription_id','feature_id']);


                        // Check addons
                        $addons = AddonSubscription::where('school_id',$subscription->school_id)->where('end_date',$subscription->end_date)->where('status',1)->get();
                        $addons_data = array();
                        foreach ($addons as $addon) {
                            $addons_data[] = [
                                'school_id' => $subscription->school_id,
                                'feature_id' => $addon->feature_id,
                                'price' => $addon->addon->price,
                                'start_date' => $today_date,
                                'end_date' => $end_date,
                                'status' => 1,
                            ];
                        }

                        AddonSubscription::upsert($addons_data,['school_id','feature_id','end_date'],['price','start_date','status']);



                    } else {
                        Log::info('Else parts');
                        // Already set plan, update charges in subscription table

                        $settings = app(CachingService::class)->getSystemSettings();
                        $end_date = Carbon::parse($today_date)->addDays(($settings['billing_cycle_in_days'] - 1))->format('Y-m-d');

                        $package = Package::find($check_subscription->package_id);
                        $update_subscription = Subscription::find($check_subscription->id);
                        $update_subscription->name = $package->name;
                        $update_subscription->student_charge = $package->student_charge;
                        $update_subscription->staff_charge = $package->staff_charge;
                        $update_subscription->end_date = $end_date;
                        $update_subscription->billing_cycle = $settings['billing_cycle_in_days'];
                        $update_subscription->save();

                        $subscription_features = array();
                        foreach ($package->package_feature as $key => $feature) {
                            $subscription_features[] = [
                                'subscription_id' => $update_subscription->id,
                                'feature_id' => $feature->feature_id
                            ];
                        }

                        SubscriptionFeature::upsert($subscription_features,['subscription_id','feature_id'],['subscription_id','feature_id']);

                        $addons = AddonSubscription::where('school_id',$subscription->school_id)->where('end_date',$subscription->end_date)->where('status',1)->get();

                        $update_addons = array();
                        foreach ($addons as $addon) {
                            $update_addons[] = [
                                'school_id' => $subscription->school_id,
                                'feature_id' => $addon->feature_id,
                                'price' => $addon->addon->price,
                                'start_date' => $update_subscription->start_date,
                                'end_date' => $update_subscription->end_date,
                                'status' => 1
                            ];
                        }

                        AddonSubscription::upsert($update_addons, ['school_id', 'feature_id', 'end_date'], ['price', 'start_date', 'status']);
                    }

                    AddonSubscription::whereIn('id',$soft_delete_addon_ids)->delete();

                    // Enable / Disable user for next bill cylce
                    $user_status = UserStatusForNextCycle::where('school_id',$subscription->school_id)->get();

                    $yesterday_date = Carbon::yesterday()->toDateTimeString();
                    $enable_user = array();
                    $disable_user = array();
                    foreach ($user_status as $key => $status) {
                        if ($status->status == 1) {
                            $enable_user[] = $status->user_id;
                        } else {
                            $disable_user[] = $status->user_id;
                        }
                    }

                    if (count($enable_user)) {
                        User::whereIn('id',$enable_user)->withTrashed()->update(['deleted_at' => null, 'status' => 1]);
                    }
                    if (count($disable_user)) {
                        User::whereIn('id',$disable_user)->withTrashed()->update(['deleted_at' => $yesterday_date, 'status' => 0]);
                    }
                    UserStatusForNextCycle::where('school_id',$subscription->school_id)->delete();
                }
            }

            // Unclear bill soft-delete addon
            AddonSubscription::whereIn('id',$unclear_addon_soft_delete)->delete();

            // Remove cache
            $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.FEATURES'),$subscription->school_id);
        }        

        Log::info("Cron is working fine!");
        return CommandAlias::SUCCESS;
    }
}
