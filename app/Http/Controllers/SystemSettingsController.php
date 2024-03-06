<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionFeature;
use App\Repositories\Feature\FeatureInterface;
use App\Repositories\Package\PackageInterface;
use App\Repositories\PackageFeature\PackageFeatureInterface;
use App\Repositories\PaymentConfiguration\PaymentConfigurationInterface;
use App\Repositories\SchoolSetting\SchoolSettingInterface;
use App\Repositories\SystemSetting\SystemSettingInterface;
use App\Services\CachingService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class SystemSettingsController extends Controller {
    // Initializing the Settings Repository
    private SystemSettingInterface $systemSettings;
    private CachingService $cache;
    private FeatureInterface $feature;
    private PackageInterface $package;
    private PackageFeatureInterface $packageFeature;
    private PaymentConfigurationInterface $paymentConfiguration;
    private SchoolSettingInterface $schoolSetting;

    public function __construct(SystemSettingInterface $systemSettings, CachingService $cachingService, PaymentConfigurationInterface $paymentConfiguration, FeatureInterface $feature, PackageInterface $package, PackageFeatureInterface $packageFeature, SchoolSettingInterface $schoolSetting) {
        $this->systemSettings = $systemSettings;
        $this->cache = $cachingService;
        $this->feature = $feature;
        $this->package = $package;
        $this->packageFeature = $packageFeature;
        $this->paymentConfiguration = $paymentConfiguration;
        $this->schoolSetting = $schoolSetting;
    }

    public function index() {
        ResponseService::noPermissionThenRedirect('system-setting-manage');
        $settings = $this->cache->getSystemSettings();
        $getDateFormat = getDateFormat();
        $getTimezoneList = getTimezoneList();
        $getTimeFormat = getTimeFormat();
        return view('settings.system-settings', compact('settings', 'getDateFormat', 'getTimezoneList', 'getTimeFormat'));
    }


    public function store(Request $request) {
        ResponseService::noPermissionThenRedirect('system-setting-manage');

        $request->validate([
            'favicon'         => 'nullable|mimes:jpg,png,jpeg,svg,icon',
            'horizontal_logo' => 'nullable|mimes:jpg,png,jpeg,svg',
            'vertical_logo'   => 'nullable|mimes:jpg,png,jpeg,svg'
        ]);

        $settings = array(
            'time_zone', 'date_format', 'time_format', 'theme_color', 'horizontal_logo', 'vertical_logo', 'favicon',
            'system_name', 'address', 'tag_line', 'mobile', 'login_page_logo', 'additional_billing_days',
            'billing_cycle_in_days', 'current_plan_expiry_warning_days',
            //            'currency_code','currency_symbol'
        );
        try {
            $data = array();
            foreach ($settings as $row) {
                if ($row == 'horizontal_logo' || $row == 'vertical_logo' || $row == 'favicon' || $row == 'login_page_logo') {
                    if ($request->hasFile($row)) {
                        // TODO : Remove the old files from server
                        $data[] = [
                            "name" => $row,
                            "data" => $request->file($row),
                            "type" => "file"
                        ];
                    }
                } else {
                    $data[] = [
                        "name" => $row,
                        "data" => $request->$row,
                        "type" => "string"
                    ];
                }
            }

            changeEnv([
                'APP_NAME' => $request->system_name
            ]);
            $this->systemSettings->upsert($data, ["name"], ["data"]);
            changeEnv([
//                'APP_NAME' => '"' . $request->system_name . '"'
'APP_NAME' => $request->system_name
            ]);
            $this->cache->removeSystemCache(config('constants.CACHE.SYSTEM.SETTINGS'));
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "System Settings Controller -> Store method");
            ResponseService::errorResponse();
        }
    }

    public function update(Request $request) {
        ResponseService::noPermissionThenRedirect('system-setting-manage');
        $request->validate([
            'name' => 'required',
            'data' => 'required'
        ]);
        try {
            $OtherSettingsData[] = array(
                'name' => $request->name,
                'data' => htmlspecialchars($request->data),
                'type' => 'string'
            );
            $this->systemSettings->upsert($OtherSettingsData, ["name"], ["data"]);
            $this->cache->removeSystemCache(config('constants.CACHE.SYSTEM.SETTINGS'));
            ResponseService::successResponse("Data Stored Successfully");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "System Settings Controller -> otherSystemSettings method");
            ResponseService::errorResponse();
        }
    }

    public function fcmIndex() {
        ResponseService::noPermissionThenRedirect('fcm-setting-manage');
        $name = 'fcm_server_key';
        $data = htmlspecialchars_decode($this->cache->getSystemSettings($name));
        return view('settings.fcm', compact('name', 'data'));
    }

    public function privacyPolicy() {
        ResponseService::noPermissionThenRedirect('privacy-policy');
        $name = 'privacy_policy';
        $data = htmlspecialchars_decode($this->cache->getSystemSettings($name));
        return view('settings.privacy-policy', compact('name', 'data'));
    }

    public function contactUs() {
        ResponseService::noPermissionThenRedirect('contact-us');
        $name = 'contact_us';
        $data = htmlspecialchars_decode($this->cache->getSystemSettings($name));
        return view('settings.contact-us', compact('name', 'data'));
    }

    public function aboutUs() {
        ResponseService::noPermissionThenRedirect('about-us');
        $name = 'about_us';
        $data = htmlspecialchars_decode($this->cache->getSystemSettings($name));
        return view('settings.about-us', compact('name', 'data'));
    }

    public function termsConditions() {
        ResponseService::noPermissionThenRedirect('terms-condition');
        $name = 'terms_condition';
        $data = htmlspecialchars_decode($this->cache->getSystemSettings($name));
        return view('settings.terms-condition', compact('name', 'data'));
    }

    public function appSettingsIndex() {
        ResponseService::noPermissionThenRedirect('app-settings');

        // List of the names to be fetched
        $names = array('app_link', 'ios_app_link', 'app_version', 'ios_app_version', 'force_app_update', 'app_maintenance', 'teacher_app_link', 'teacher_ios_app_link', 'teacher_app_version', 'teacher_ios_app_version', 'teacher_force_app_update', 'teacher_app_maintenance');

        // Passing the array of names and gets the array of data
        $settings = $this->systemSettings->getBulkData($names);
        return view('settings.app', compact('settings'));
    }

    public function appSettingsUpdate(Request $request) {
        ResponseService::noPermissionThenRedirect('app-settings');
        $request->validate([
            'app_link'         => 'required',
            'ios_app_link'     => 'required',
            'app_version'      => 'required',
            'ios_app_version'  => 'required',
            'force_app_update' => 'required',
            'app_maintenance'  => 'required',
            // 'teacher_app_link'         => 'required',
            // 'teacher_ios_app_link'     => 'required',
            // 'teacher_app_version'      => 'required',
            // 'teacher_ios_app_version'  => 'required',
            // 'teacher_force_app_update' => 'required',
            // 'teacher_app_maintenance'  => 'required',
        ]);

        try {
            $settings = [
                'app_link',
                'ios_app_link',
                'app_version',
                'ios_app_version',
                'force_app_update',
                'app_maintenance',
                //            'teacher_app_link',
                //            'teacher_ios_app_link',
                //            'teacher_app_version',
                //            'teacher_ios_app_version',
                //            'teacher_force_app_update',
                //            'teacher_app_maintenance',
            ];
            foreach ($settings as $row) {
                $data[] = [
                    'name' => $row,
                    'data' => $request->$row,
                    'type' => 'string'
                ];
                // Call storeOrUpdate function of Setting upsert
            }
            $this->systemSettings->upsert($data, ["name"], ["data"]);
            $this->cache->removeSystemCache(config('constants.CACHE.SYSTEM.SETTINGS'));
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "System Settings Controller -> appSettingsUpdate method");
            ResponseService::errorResponse();
        }
    }

    public function emailIndex() {
        ResponseService::noPermissionThenRedirect('email-setting-create');
        // List of the names to be fetched
        $names = array('mail_mailer', 'mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption', 'mail_send_from', 'email_verified');
        // Passing the array of names and gets the array of data
        $settings = $this->cache->getSystemSettings($names);
        return view('settings.email', compact('settings'));
    }

    public function emailUpdate(Request $request) {
        ResponseService::noPermissionThenRedirect('email-setting-create');
        $request->validate([
            'mail_mailer'     => 'required',
            'mail_host'       => 'required',
            'mail_port'       => 'required',
            'mail_username'   => 'required',
            'mail_password'   => 'required',
            'mail_encryption' => 'required',
            'mail_send_from'  => 'required|email',
        ]);

        $settings = [
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_send_from',
            'email_verified'
        ];

        try {
            foreach ($settings as $row) {
                $data[] = [
                    'name' => $row,
                    'data' => ($row == 'email_verified' ? 0 : $request->$row),
                    'type' => $row == 'email_verified' ? 'boolean' : 'string'
                ];
            }
            // Call Upsert function of Setting Upsert
            $this->systemSettings->upsert($data, ["name"], ["data"]);
            Cache::flush();

            // Update ENV
            $env_update = changeEnv([
                'MAIL_MAILER'       => $request->mail_mailer,
                'MAIL_HOST'         => $request->mail_host,
                'MAIL_PORT'         => $request->mail_port,
                'MAIL_USERNAME'     => $request->mail_username,
                'MAIL_PASSWORD'     => $request->mail_password,
                'MAIL_ENCRYPTION'   => $request->mail_encryption,
                'MAIL_FROM_ADDRESS' => $request->mail_send_from

            ]);
            if ($env_update) {
                ResponseService::successResponse("Data Updated Successfully");
            } else {
                ResponseService::errorResponse();
            }
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "System Settings Controller -> emailUpdate method");
            ResponseService::errorResponse();
        }
    }

    public function verifyEmailConfiguration(Request $request) {
        ResponseService::noPermissionThenRedirect('email-setting-create');
        $validator = Validator::make($request->all(), [
            'verify_email' => 'required|email',
        ]);
        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        }
        try {
            $data_email = [
                'email' => $request->verify_email,
            ];
            $admin_mail = $this->cache->getSystemSettings()['mail_send_from'];
            if (!filter_var($request->verify_email, FILTER_VALIDATE_EMAIL)) {
                $response = array(
                    'error'   => true,
                    'message' => trans('invalid_email'),
                );
                return response()->json($response);
            }
            Mail::send('mail', $data_email, static function ($message) use ($data_email, $admin_mail) {
                $message->to($data_email['email'])->subject('Connection Verified successfully');
                $message->from($admin_mail, 'Super Admin');
            });
            $this->systemSettings->updateOrCreate(['name' => 'email_verified'], ['data' => 1, 'type' => 'string']);
            $this->cache->removeSystemCache(config('constants.CACHE.SYSTEM.SETTINGS'));
            ResponseService::successResponse("Email Sent Successfully");
        } catch (Throwable $e) {
            if (Str::contains($e->getMessage(), ['Failed', 'Mail', 'Mailer', 'MailManager'])) {
                $error = "Email verification failed Please check your SMTP credentials";
            } else {
                $error = $e->getMessage() . ' in ' . $e->getFile() . ' At Line : ' . $e->getLine();
            }

            DB::rollback();
            ResponseService::errorResponse("Error Occurred", ['error' => $error, 'stacktrace' => $e->getTraceAsString()]);
        }
    }

    public function paymentIndex() {
        /* This method is used for both Super Admin & School Admin */
        ResponseService::noAnyRoleThenRedirect(['Super Admin', 'School Admin']);
        $paymentConfiguration = $this->paymentConfiguration->all();
        $paymentGateway = [];
        foreach ($paymentConfiguration as $row) {
            $paymentGateway[$row->payment_method] = $row->toArray();
        }
        if (Auth::user()->hasRole('Super Admin')) {
            $settings = $this->cache->getSystemSettings();
        } else if (Auth::user()->hasRole('School Admin')) {
            $settings = $this->cache->getSchoolSettings();
        }
        return view('settings.payment', compact('paymentGateway', 'settings'));
    }

    public function paymentUpdate(Request $request) {
        /* This method is used for both Super Admin & School Admin */
        ResponseService::noAnyRoleThenRedirect(['Super Admin', 'School Admin']);
        $request->validate([
            'gateway'        => 'required|array',
            'gateway.Stripe' => 'required|array|required_array_keys:api_key,secret_key,webhook_secret_key,status',
        ]);
        try {
            DB::beginTransaction();
            foreach ($request->gateway as $key => $gateway) {
                $this->paymentConfiguration->updateOrCreate(['payment_method' => $key], [
                    'api_key'            => $gateway["api_key"] ?? '',
                    'secret_key'         => $gateway["secret_key"] ?? '',
                    'webhook_secret_key' => $gateway["webhook_secret_key"] ?? '',
                    'status'             => $gateway["status"] ?? '',
                    'currency_code'      => $gateway["currency_code"] ?? ''
                ]);
            }
            if (Auth::user()->hasRole('Super Admin')) {
                $this->systemSettings->upsert([
                    ["name" => 'currency_code',
                     "data" => $request->currency_code,
                     "type" => "string"
                    ],
                    ["name" => 'currency_symbol',
                     "data" => $request->currency_symbol,
                     "type" => "string"
                    ]
                ], ["name"], ["data"]);
            }
            if (Auth::user()->hasRole('School Admin')) {
                $this->schoolSetting->upsert([
                    ["name" => 'currency_code',
                     "data" => $request->currency_code,
                     "type" => "string"
                    ],
                    ["name" => 'currency_symbol',
                     "data" => $request->currency_symbol,
                     "type" => "string"
                    ]
                ], ["name"], ["data"]);
            }
            DB::commit();
            $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.SETTINGS'));
            $this->cache->removeSystemCache(config('constants.CACHE.SYSTEM.SETTINGS'));
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "SchoolSettings Controller -> storeOnlineExamTermsCondition method");
            ResponseService::errorResponse();
        }
    }

    public function front_site_settings() {

        ResponseService::noPermissionThenRedirect('front-site-setting');
        $names = array('front_site_theme_color', 'primary_color', 'secondary_color', 'home_image', 'facebook', 'instagram', 'linkedin', 'footer_text', 'short_description');

        // Passing the array of names and gets the array of data
        $settings = $this->systemSettings->getBulkData($names);

        return view('settings.front_site_settings', compact('settings'));
    }

    public function front_site_settings_update(Request $request) {
        ResponseService::noPermissionThenRedirect('front-site-setting');

        $request->validate([
            'home_image' => 'nullable|mimes:jpg,png,jpeg,svg,icon|max:2048',
        ]);
        $settings = array(
            'front_site_theme_color', 'primary_color', 'secondary_color', 'short_description', 'home_image', 'facebook', 'instagram', 'linkedin', 'footer_text'
        );
        try {
            $data = array();
            foreach ($settings as $row) {
                if ($row == 'home_image') {
                    if ($request->hasFile($row)) {
                        // TODO : Remove the old files from server
                        $data[] = [
                            "name" => $row,
                            "data" => $request->file($row),
                            "type" => "file"
                        ];
                    }
                } else {
                    $data[] = [
                        "name" => $row,
                        "data" => $request->$row,
                        "type" => "text"
                    ];
                }
            }

            $this->systemSettings->upsert($data, ["name"], ["data"]);
            $this->cache->removeSystemCache(config('constants.CACHE.SYSTEM.SETTINGS'));
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "System Settings Controller -> Front Site Settings Update method");
            ResponseService::errorResponse();
        }
    }

    public function subscription_settings() {
        ResponseService::noPermissionThenRedirect('subscription-settings');
        $settings = $this->cache->getSystemSettings();
        $features = $this->feature->builder()->orderBy('is_default', 'DESC')->get();
        $package = $this->package->builder()->where('is_trial', 1)->first();
        return view('settings.subscription-settings', compact('settings', 'features', 'package'));
    }

    public function subscription_settings_update(Request $request) {
        ResponseService::noPermissionThenRedirect('subscription-settings');
        $settings = array(
            'additional_billing_days', 'billing_cycle_in_days', 'current_plan_expiry_warning_days', 'trial_days', 'student_limit', 'staff_limit'
        );
        try {
            $data = array();
            foreach ($settings as $row) {
                $data[] = [
                    "name" => $row,
                    "data" => $request->$row,
                    "type" => "text"
                ];
            }

            $package = $this->package->builder()->where('is_trial', 1)->first();
            $package_data = [
                'name'           => 'Trial Package',
                'description'    => $request->free_trial_subscription_description,
                'student_charge' => 0,
                'staff_charge'   => 0,
                'status'         => $request->status,
                'is_trial'       => 1,
                'highlight'      => $request->highlight ?? 0,
                'rank'           => -1
            ];
            if ($package) {
                // Update trial Package
                $package = $this->package->update($package->id, $package_data);

                $today_date = Carbon::now()->format('Y-m-d');
                $subscriptions = Subscription::whereHas('package',function($q) {
                    $q->where('is_trial',1);
                })->where('start_date','<=',$today_date)->where('end_date','>=',$today_date)->doesntHave('subscription_bill');

                $school_ids = $subscriptions->pluck('school_id');
                $subscriptions = $subscriptions->pluck('id');

                $package_features = $package->package_feature->pluck('feature_id')->toArray();
                $packageFeatures = [];

                foreach ($request->feature_id as $feature) {
                    $packageFeatures[] = [
                        'package_id' => $package->id,
                        'feature_id' => $feature
                    ];

                    // Remove package features
                    $key = array_search($feature, $package_features);
                    if ($key !== false) {
                        unset($package_features[$key]);
                    }
                }
                $this->packageFeature->upsert($packageFeatures, ['feature_id', 'package_id'], ['package_id', 'feature_id']);

                // Delete package features
                $this->packageFeature->builder()->whereIn('feature_id', $package_features)->where('package_id', $package->id)->delete();

                // Update immediate trial package
                SubscriptionFeature::whereIn('subscription_id',$subscriptions)->delete();
                foreach ($subscriptions as $key => $subscription) {
                    foreach ($request->feature_id as $feature) {
                        $subscriptionFeatures[] = [
                            'subscription_id' => $subscription,
                            'feature_id' => $feature
                        ];
                    }
                    SubscriptionFeature::upsert($subscriptionFeatures,['subscription_id','feature_id'],['subscription_id','feature_id']);
                }

                // Remove school feature cache
                foreach ($school_ids as $key => $school_id) {
                    $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.FEATURES'),$school_id);
                }
                
            } else {
                // Create trial Package
                $package = $this->package->create($package_data);
                // Create package features
                $packageFeatures = [];
                foreach ($request->feature_id as $feature) {
                    $packageFeatures[] = [
                        'package_id' => $package->id,
                        'feature_id' => $feature
                    ];
                }
                $this->packageFeature->upsert($packageFeatures, ['package_id', 'feature_id'], ['package_id', 'feature_id']); // Store package features
            }

            $this->systemSettings->upsert($data, ["name"], ["data"]);
            $this->cache->removeSystemCache(config('constants.CACHE.SYSTEM.SETTINGS'));
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "System Settings Controller -> Subscription Settings Update method");
            ResponseService::errorResponse();
        }
    }

    public function school_terms_condition() {
        ResponseService::noPermissionThenRedirect('school-terms-condition');

        $name = 'school_terms_condition';
        $data = htmlspecialchars_decode($this->cache->getSystemSettings($name));

        return view('settings.school_term_condition', compact('name', 'data'));

    }

}
