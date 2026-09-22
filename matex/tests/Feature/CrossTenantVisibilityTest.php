<?php

namespace Tests\Feature;

use App\Enums\PoStatus;
use App\Enums\QadSyncStatus;
use App\Enums\ScheduleStatus;
use App\Models\DeliveryNote;
use App\Models\Forecast;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Receiving;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Checks that no list/index page ever shows a transaction a user would get
 * 403'd on if they opened it directly — i.e. what a user sees in a list is
 * exactly what scopeVisibleTo()/policies already say they're allowed to
 * open. Doesn't hit QAD; purely local-fixture + HTTP-response assertions.
 * Reads page props via the inertia-laravel TestResponse::inertiaProps()
 * macro, which parses the page's embedded data straight off a normal HTML
 * response — no special Inertia headers needed.
 *
 * Run the same way as QadEndToEndRoleTest (real dev MySQL, not the default
 * sqlite suite) — see that file's class docblock for the exact command.
 */
class CrossTenantVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    private function rmItem(): Item
    {
        return Item::where('item_number', 'CMWNR00002')->firstOrFail();
    }

    /** Company 10 = L0254, Company 8 = L0061 (both real, both have portal accounts). */
    private function makePo(int $supplierRmId, int $ohpSupplierId, string $marker): PurchaseOrder
    {
        $po = PurchaseOrder::create([
            'po_number' => 'PO-XTEN-'.Str::upper(Str::random(8)),
            'supplier_rm_id' => $supplierRmId,
            'due_date' => '2026-12-20',
            'status' => PoStatus::Confirmed,
            'created_by' => 2,
            'notes' => $marker,
        ]);

        $poItem = $po->items()->create([
            'item_id' => $this->rmItem()->id,
            'qty_ordered' => 20,
            'qty_confirmed' => 20,
        ]);

        $po->schedules()->create([
            'purchase_order_item_id' => $poItem->id,
            'ohp_supplier_id' => $ohpSupplierId,
            'scheduled_date' => '2026-12-05',
            'qty' => 20,
            'qty_confirmed' => 20,
            'status' => ScheduleStatus::Planned,
        ]);

        return $po->fresh(['items', 'schedules']);
    }

    private function makeDn(PurchaseOrder $po): DeliveryNote
    {
        $schedule = $po->schedules->first();

        return DeliveryNote::create([
            'dn_number' => 'DN-'.$po->po_number.'-001',
            'purchase_order_id' => $po->id,
            'delivery_schedule_id' => $schedule->id,
            'purchase_order_item_id' => $schedule->purchase_order_item_id,
            'qty' => 20,
            'delivery_date' => '2026-12-05',
            'rm_sj_number' => 'SJ-'.Str::random(6),
            'generated_at' => now(),
        ]);
    }

    public function test_po_index_and_dashboard_only_show_own_companys_pos(): void
    {
        $poA = $this->makePo(10, 8, 'XTEN-A-'.Str::random(6)); // RM L0254 / OHP L0061
        $poB = $this->makePo(12, 6, 'XTEN-B-'.Str::random(6)); // RM L0224 / OHP OHP02

        $rmA = User::findOrFail(11);
        $rmB = User::findOrFail(12);
        $ohpA = User::findOrFail(10);
        $ohpB = User::findOrFail(8);
        $staff = [User::findOrFail(1), User::findOrFail(2), User::findOrFail(3)];

        // Supplier RM: only sees their own PO in the index and on the dashboard.
        $numbers = collect($this->actingAs($rmA)->get(route('purchase-orders.index'))->inertiaProps('orders.data'))->pluck('po_number');
        $this->assertContains($poA->po_number, $numbers);
        $this->assertNotContains($poB->po_number, $numbers);

        $numbers = collect($this->actingAs($rmB)->get(route('purchase-orders.index'))->inertiaProps('orders.data'))->pluck('po_number');
        $this->assertContains($poB->po_number, $numbers);
        $this->assertNotContains($poA->po_number, $numbers);

        $numbers = collect($this->actingAs($rmA)->get(route('dashboard'))->inertiaProps('recentPos'))->pluck('po_number');
        $this->assertContains($poA->po_number, $numbers);
        $this->assertNotContains($poB->po_number, $numbers);

        // Supplier OHP: only sees the PO they're actually scheduled on.
        $numbers = collect($this->actingAs($ohpA)->get(route('purchase-orders.index'))->inertiaProps('orders.data'))->pluck('po_number');
        $this->assertContains($poA->po_number, $numbers);
        $this->assertNotContains($poB->po_number, $numbers);

        $numbers = collect($this->actingAs($ohpB)->get(route('purchase-orders.index'))->inertiaProps('orders.data'))->pluck('po_number');
        $this->assertContains($poB->po_number, $numbers);
        $this->assertNotContains($poA->po_number, $numbers);

        // SDI staff (Admin/Purchasing/PPIC) legitimately see everything.
        foreach ($staff as $user) {
            $numbers = collect($this->actingAs($user)->get(route('purchase-orders.index'))->inertiaProps('orders.data'))->pluck('po_number');
            $this->assertContains($poA->po_number, $numbers);
            $this->assertContains($poB->po_number, $numbers);
        }
    }

    public function test_dn_index_only_shows_own_companys_delivery_notes(): void
    {
        $poA = $this->makePo(10, 8, 'XTEN-DN-A-'.Str::random(6));
        $poB = $this->makePo(12, 6, 'XTEN-DN-B-'.Str::random(6));
        $dnA = $this->makeDn($poA);
        $dnB = $this->makeDn($poB);

        $rmA = User::findOrFail(11);
        $ohpB = User::findOrFail(8);

        $numbers = collect($this->actingAs($rmA)->get(route('delivery-notes.index'))->inertiaProps('notes.data'))->pluck('dn_number');
        $this->assertContains($dnA->dn_number, $numbers);
        $this->assertNotContains($dnB->dn_number, $numbers);

        $numbers = collect($this->actingAs($ohpB)->get(route('delivery-notes.index'))->inertiaProps('notes.data'))->pluck('dn_number');
        $this->assertContains($dnB->dn_number, $numbers);
        $this->assertNotContains($dnA->dn_number, $numbers);

        $admin = User::findOrFail(1);
        $numbers = collect($this->actingAs($admin)->get(route('delivery-notes.index'))->inertiaProps('notes.data'))->pluck('dn_number');
        $this->assertContains($dnA->dn_number, $numbers);
        $this->assertContains($dnB->dn_number, $numbers);
    }

    public function test_forecast_index_only_shows_own_supplier_forecast(): void
    {
        $periodA = now()->addMonthNoOverflow(2)->startOfMonth();
        $forecastA = Forecast::create([
            'supplier_rm_id' => 10,
            'period_month' => $periodA,
            'notes' => 'XTEN forecast A '.Str::random(6),
            'uploaded_by' => 2,
        ]);
        $forecastB = Forecast::create([
            'supplier_rm_id' => 12,
            'period_month' => $periodA,
            'notes' => 'XTEN forecast B '.Str::random(6),
            'uploaded_by' => 2,
        ]);

        $rmA = User::findOrFail(11);
        $purchasing = User::findOrFail(2);

        $ids = collect($this->actingAs($rmA)->get(route('forecasts.index'))->inertiaProps('forecasts.data'))->pluck('id');
        $this->assertContains($forecastA->id, $ids);
        $this->assertNotContains($forecastB->id, $ids);

        $ids = collect($this->actingAs($purchasing)->get(route('forecasts.index'))->inertiaProps('forecasts.data'))->pluck('id');
        $this->assertContains($forecastA->id, $ids);
        $this->assertContains($forecastB->id, $ids);
    }

    public function test_billing_index_only_shows_own_supplier_receivings(): void
    {
        $poA = $this->makePo(10, 8, 'XTEN-BILL-A-'.Str::random(6));
        $poB = $this->makePo(12, 6, 'XTEN-BILL-B-'.Str::random(6));
        $dnA = $this->makeDn($poA);
        $dnB = $this->makeDn($poB);

        $receivingA = Receiving::create([
            'delivery_note_id' => $dnA->id,
            'delivery_schedule_id' => $dnA->delivery_schedule_id,
            'received_qty' => 20,
            'received_by' => 3,
            'received_at' => now(),
            'qad_status' => QadSyncStatus::Success,
        ]);
        $receivingB = Receiving::create([
            'delivery_note_id' => $dnB->id,
            'delivery_schedule_id' => $dnB->delivery_schedule_id,
            'received_qty' => 20,
            'received_by' => 3,
            'received_at' => now(),
            'qad_status' => QadSyncStatus::Success,
        ]);

        $rmA = User::findOrFail(11);
        $ppic = User::findOrFail(3);

        $ids = collect($this->actingAs($rmA)->get(route('billing.index'))->inertiaProps('receivings.data'))->pluck('id');
        $this->assertContains($receivingA->id, $ids);
        $this->assertNotContains($receivingB->id, $ids);

        $ids = collect($this->actingAs($ppic)->get(route('billing.index'))->inertiaProps('receivings.data'))->pluck('id');
        $this->assertContains($receivingA->id, $ids);
        $this->assertContains($receivingB->id, $ids);
    }
}
