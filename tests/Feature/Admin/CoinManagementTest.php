<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\CoinTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class CoinManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com'
        ]);
        
        $this->user = User::factory()->create([
            'role' => 'user',
            'email' => 'user@test.com'
        ]);
    }

    /** @test */
    public function admin_can_view_coin_transactions_index()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.coins.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.coins.index');
        $response->assertSee('مدیریت سکه‌ها');
    }

    /** @test */
    public function admin_can_create_coin_transaction()
    {
        $transactionData = [
            'user_id' => $this->user->id,
            'amount' => 1000,
            'type' => 'earned',
            'description' => 'Test transaction',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.coins.store'), $transactionData);

        $response->assertRedirect(route('admin.coins.index'));
        $response->assertSessionHas('success', 'سکه با موفقیت اضافه شد.');
        
        $this->assertDatabaseHas('coin_transactions', [
            'user_id' => $this->user->id,
            'amount' => 1000,
            'type' => 'earned',
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function admin_can_view_coin_transaction()
    {
        $transaction = CoinTransaction::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 500,
            'type' => 'purchased',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.coins.show', $transaction));

        $response->assertStatus(200);
        $response->assertViewIs('admin.coins.show');
        $response->assertSee($transaction->amount);
    }

    /** @test */
    public function admin_can_update_coin_transaction()
    {
        $transaction = CoinTransaction::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 500,
            'type' => 'purchased',
        ]);

        $updateData = [
            'amount' => 750,
            'type' => 'gift',
            'description' => 'Updated transaction',
            'status' => 'completed',
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.coins.update', $transaction), $updateData);

        $response->assertRedirect(route('admin.coins.index'));
        $response->assertSessionHas('success', 'تراکنش سکه با موفقیت به‌روزرسانی شد.');
        
        $this->assertDatabaseHas('coin_transactions', [
            'id' => $transaction->id,
            'amount' => 750,
            'type' => 'gift',
        ]);
    }

    /** @test */
    public function admin_can_delete_coin_transaction()
    {
        $transaction = CoinTransaction::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 500,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.coins.destroy', $transaction));

        $response->assertRedirect(route('admin.coins.index'));
        $response->assertSessionHas('success', 'تراکنش سکه با موفقیت حذف شد.');
        
        $this->assertDatabaseMissing('coin_transactions', [
            'id' => $transaction->id,
        ]);
    }

    /** @test */
    public function admin_can_perform_bulk_actions_on_coin_transactions()
    {
        $transactions = CoinTransaction::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $transactionIds = $transactions->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.coins.bulk-action'), [
                'action' => 'approve',
                'selected_items' => $transactionIds,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'تراکنش‌های انتخاب شده با موفقیت تایید شدند.');
        
        foreach ($transactions as $transaction) {
            $this->assertDatabaseHas('coin_transactions', [
                'id' => $transaction->id,
                'status' => 'completed',
            ]);
        }
    }

    /** @test */
    public function admin_can_export_coin_transactions()
    {
        CoinTransaction::factory()->count(5)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.coins.export'));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'گزارش تراکنش‌های سکه آماده دانلود است.');
    }

    /** @test */
    public function admin_can_view_coin_statistics()
    {
        CoinTransaction::factory()->count(10)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.coins.statistics'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.coins.statistics');
        $response->assertSee('آمار تراکنش‌های سکه');
    }

    /** @test */
    public function coin_transaction_validation_works()
    {
        $invalidData = [
            'user_id' => 999999, // Non-existent user
            'amount' => -100, // Negative amount
            'type' => 'invalid_type',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.coins.store'), $invalidData);

        $response->assertSessionHasErrors(['user_id', 'amount', 'type']);
    }

    /** @test */
    public function non_admin_cannot_access_coin_management()
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.coins.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function coin_transaction_updates_user_balance()
    {
        $initialBalance = $this->user->coins;
        $transactionAmount = 1000;

        $this->actingAs($this->admin)
            ->post(route('admin.coins.store'), [
                'user_id' => $this->user->id,
                'amount' => $transactionAmount,
                'type' => 'earned',
            ]);

        $this->user->refresh();
        $this->assertEquals($initialBalance + $transactionAmount, $this->user->coins);
    }

    /** @test */
    public function coin_transaction_deletion_reverts_user_balance()
    {
        $transaction = CoinTransaction::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 1000,
        ]);

        $initialBalance = $this->user->coins;
        $this->user->increment('coins', $transaction->amount);

        $this->actingAs($this->admin)
            ->delete(route('admin.coins.destroy', $transaction));

        $this->user->refresh();
        $this->assertEquals($initialBalance, $this->user->coins);
    }
}
