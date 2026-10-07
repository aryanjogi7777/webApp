<?php

namespace Tests\Feature;

use App\Models\DefaultingAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DefaultingAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_lookup_is_the_home_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Find an account.')
            ->assertSee('mobile number');
    }

    public function test_account_can_be_created_updated_and_deleted(): void
    {
        $this->post(route('accounts.store'), [
            'account_id' => '0012345678',
            'old_account_id' => 'OLD-123',
            'name' => 'Asha Sharma',
            'address' => 'Radour, Haryana',
            'phone_number' => '9810876666',
            'closing_balance' => '125000.00',
            'category' => 'DS',
            'progress' => 'Notice sent',
            'paid_amount' => '25000',
        ])->assertRedirect(route('accounts.index'));

        $account = DefaultingAccount::query()->where('account_id', '0012345678')->firstOrFail();
        $this->assertSame(100000.0, $account->pending_amount);

        $this->put(route('accounts.update', $account), [
            'account_id' => '0012345678',
            'old_account_id' => '',
            'name' => 'Asha Sharma Updated',
            'address' => 'Yamunanagar',
            'closing_balance' => '125000',
            'category' => 'NDS',
            'progress' => 'Follow-up required',
            'paid_amount' => '50000',
        ])->assertRedirect(route('accounts.index'));

        $this->assertDatabaseHas('defaulting_accounts', [
            'id' => $account->id,
            'name' => 'Asha Sharma Updated',
            'category' => 'NDS',
            'paid_amount' => 50000,
        ]);

        $this->delete(route('accounts.destroy', $account))
            ->assertRedirect(route('accounts.index'));

        $this->assertDatabaseMissing('defaulting_accounts', ['id' => $account->id]);
    }

    public function test_csv_sheet_with_title_row_can_be_imported(): void
    {
        $csv = "List of defaulting amounts\n".
            "S.No.,ACCT_ID,old acc id,NAME,ADDRESS,CLOSING BALANCE_LPS,Category,Progress of the JE,pay\n".
            "1,0098765432,OLD-9,Meena Devi,\"Radour PH No - 9810876666, Haryana\",78000,DS,\"Case filed\",12000\n".
            "2,0098765433,OLD-10,Rekha Devi,\"Radaur PH No - 9050059988\",NDS,49984,,2500\n".
            "3,0098765434,,Gita Devi,\"Radaur\",AGRI,40100,,\n";

        $this->post(route('accounts.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('accounts.csv', $csv),
        ])->assertRedirect(route('accounts.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('defaulting_accounts', [
            'account_id' => '0098765432',
            'old_account_id' => 'OLD-9',
            'name' => 'Meena Devi',
            'closing_balance' => 78000,
            'category' => 'DS',
            'progress' => 'Case filed',
            'paid_amount' => 12000,
            'phone_number' => '9810876666',
        ]);

        $this->assertDatabaseHas('defaulting_accounts', [
            'account_id' => '0098765433',
            'closing_balance' => 49984,
            'category' => 'NDS',
            'phone_number' => '9050059988',
        ]);

        $this->assertDatabaseHas('defaulting_accounts', [
            'account_id' => '0098765434',
            'closing_balance' => 40100,
            'category' => 'AGRI',
        ]);
    }

    public function test_quick_lookup_finds_an_account_by_phone_and_shows_its_full_record(): void
    {
        DefaultingAccount::create([
            'account_id' => '0012345678',
            'old_account_id' => 'OLD-123',
            'name' => 'Asha Sharma',
            'address' => 'Radour, Haryana',
            'phone_number' => '9810876666',
            'closing_balance' => 125000,
            'category' => 'DS',
            'progress' => 'Notice sent',
            'paid_amount' => 25000,
        ]);

        $this->get(route('home', ['q' => '+91 98108 76666']))
            ->assertOk()
            ->assertSee('Asha Sharma')
            ->assertSee('0012345678')
            ->assertSee('OLD-123')
            ->assertSee('9810876666')
            ->assertSee('Notice sent')
            ->assertSee('₹100,000.00');
    }

    public function test_xlsx_export_returns_a_download(): void
    {
        DefaultingAccount::create([
            'account_id' => '12345',
            'name' => 'Test account',
            'closing_balance' => 50000,
            'category' => 'NDS',
        ]);

        $response = $this->get(route('accounts.export.download', ['format' => 'xlsx']));

        $response->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition');
    }
}
