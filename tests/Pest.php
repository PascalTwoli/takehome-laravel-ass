<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(
    Tests\TestCase::class,
    RefreshDatabase::class,
)->in('Feature');

expects()->extend('toBeOne', function () {
    return $this->toBe(1);
});
