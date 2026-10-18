<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/** Modelo de club solo para tests (tabla test_items, ver tests/Fixtures/tenant-migrations). */
class TestItem extends Model
{
    protected $connection = 'tenant';

    protected $fillable = ['name'];
}
