<?php

namespace Modules\AdminPlans\Support;

use Modules\AdminPlans\Models\AdminPlan;

class DefaultSignupPlanResolver
{
    public function resolve(): ?AdminPlan
    {
        return AdminPlan::query()
            ->where('status', true)
            ->where('default_signup_plan', true)
            ->orderByDesc('featured')
            ->orderBy('position')
            ->orderBy('id')
            ->first()
            ?? AdminPlan::query()
                ->where('status', true)
                ->where('free_plan', true)
                ->orderByDesc('featured')
                ->orderBy('position')
                ->orderBy('id')
                ->first();
    }
}

