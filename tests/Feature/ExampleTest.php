<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_home_page_loads_the_quick_lookup(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Find an account.')
            ->assertSee('name or mobile number')
            ->assertSee('Dashboard')
            ->assertSee('Create account')
            ->assertSee('Account list')
            ->assertSee('Import sheet')
            ->assertSee('Export data');
    }

    public function test_import_and_export_have_separate_module_pages(): void
    {
        $this->get(route('accounts.import'))
            ->assertOk()
            ->assertSee('Import account data')
            ->assertSee('Upload a spreadsheet');

        $this->get(route('accounts.export'))
            ->assertOk()
            ->assertSee('Export account data')
            ->assertSee('Prepare your export');
    }

    public function test_the_new_account_form_loads(): void
    {
        $this->get(route('accounts.create'))
            ->assertOk()
            ->assertSee('Account holder name')
            ->assertSee('Phone number')
            ->assertSee('Closing balance (LPS)')
            ->assertSee('Progress of the JE');
    }
}
