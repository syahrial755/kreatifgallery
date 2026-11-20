<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Isolasi: tes tidak pernah boleh menyentuh berkas media sungguhan di storage/app/public.
        Storage::fake('public');
    }
}
