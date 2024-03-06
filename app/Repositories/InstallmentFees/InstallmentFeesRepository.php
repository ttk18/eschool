<?php

namespace App\Repositories\InstallmentFees;

use App\Models\InstallmentFee;
use App\Repositories\Saas\SaaSRepository;

class InstallmentFeesRepository extends SaaSRepository implements InstallmentFeesInterface {

    public function __construct(InstallmentFee $model) {
        parent::__construct($model);
    }

    public function default() {
        return $this->defaultModel()->where('default', 1)->first();
    }
}
