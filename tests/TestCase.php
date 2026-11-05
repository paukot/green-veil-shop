<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        if ($this->app) {
            DB::flushQueryLog();
        }

        parent::tearDown();

        foreach (get_object_vars($this) as $key => $value) {
            unset($this->{$key});
        }

        gc_collect_cycles();
    }
}
