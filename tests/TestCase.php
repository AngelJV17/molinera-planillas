<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Gate;

abstract class TestCase extends BaseTestCase
{
    protected bool $grantAllPermissions = true;

    protected function setUp(): void
    {
        parent::setUp();

        $testCase = $this;

        Gate::before(function (User $user, string $ability) use ($testCase) {
            return $testCase->grantAllPermissions ? true : null;
        });
    }
}
