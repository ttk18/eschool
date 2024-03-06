<?php

namespace App\Repositories\FeesClass;

use App\Models\FeesClass;
use App\Repositories\Saas\SaaSRepository;

class FeesClassRepository extends SaaSRepository implements FeesClassInterface {

    public function __construct(FeesClass $model) {
        parent::__construct($model);
    }
}
