<?php

namespace Tests\Feature;

use App\Enums\PoStatus;
use App\Enums\ScheduleStatus;
use App\Models\DeliveryNote;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Full-stack, role-by-role coverage of the matex business flow, run against
 * the real dev database (wrapped in a transaction that's rolled back after
 * every test — see DatabaseTransactions) and, where the flow reaches
 * QAD (PO approval, receiving), the real QAD test instance.
 *
 * NOT part of the default `php artisan test` run: phpunit.xml points the
 * suite at an in-memory sqlite DB (and this environment doesn't even have
 * pdo_sqlite installed), and this file needs the real dev MySQL DB for its
 * fixture users/companies/QAD-synced data, plus live network access to QAD.
 * Run it explicitly, pointed at the dev DB:
 *
 *   DB_CONNECTION=mysql DB_DATABASE=matex DB_HOST=127.0.0.1 DB_PORT=3306 \
 *   DB_USERNAME=root DB_PASSWORD= php artisan test tests/Feature/QadEndToEndRoleTest.php
 *
 * Every test that reaches QAD (only test_full_workflow_across_all_roles_reaches_qad)
 * creates a real PO in QAD's test environment (domain 7000) that can't be
 * rolled back — local DB rows are cleaned up automatically, but QAD's own
 * copy isn't. That's expected; don't run that one test in a hot loop.
 *
 * Fixtures reuse real synced reference data already in the dev DB:
 * company 10 = L0254 (INDOJAPAN STEEL CENTRE, supplier_rm, user 11)
 * company 12 = L0224 (3M, supplier_rm, user 12)
 * company 2  = RM01 (demo, supplier_rm, user 4) — used as the "wrong RM"
 * company 8  = L0061 (DHARMA POLIMETAL, qad_suppliers.category=ohp, user 10)
 *              — the only OHP-categorized QAD supplier that also has a
 *              portal account, so it's the one real StorePurchaseOrderRequest
 *              validation (exists + category + has-account checks) accepts.
 * company 6  = OHP02 (demo-only, supplier_ohp, user 8) — used as the "wrong OHP"
 *              for fixture-based tests that bypass HTTP validation.
 * user 1 = admin, user 2 = purchasing, user 3 = ppic
 * item CMWNR00002 (local id 5, uom PC) — real QAD-synced RM item.
 */
class QadEndToEndRoleTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::findOrFail(1);
    }

    private function purchasing(): User
    {
        return User::findOrFail(2);
    }

    private function ppic(): User
    {
        return User::findOrFail(3);
    }

    private function rmWrong(): User
    {
        return User::findOrFail(4); // RM01, not on any fixture PO below
    }

    private function rm(): User
    {
        return User::findOrFail(11); // L0254
    }

    private function rmOther(): User
    {
        return User::findOrFail(12); // L0224
    }

    private function ohp(): User
    {
        return User::findOrFail(10); // L0061 / DHARMA POLIMETAL
    }

    private function ohpWrong(): User
    {
        return User::findOrFail(8); // OHP02
    }

    private function rmItem(): Item
    {
        return Item::where('item_number', 'CMWNR00002')->firstOrFail();
    }

    /**
     * Builds a PO + single item + single schedule directly (bypassing the
     * action/QAD layer) so authorization-boundary tests don't need to pay
     * for a real QAD round trip just to get a fixture into the right status.
     */
    private function makeFixturePo(PoStatus $status, int $supplierRmId = 10, int $ohpSupplierId = 8): PurchaseOrder
    {
        $po = PurchaseOrder::create([
            'po_number' => 'DR-TEST-'.Str::upper(Str::random(8)),
            'supplier_rm_id' => $supplierRmId,
            'due_date' => '2026-12-20',
            'status' => $status,
            'created_by' => $this->purchasing()->id,
        ]);

        $confirmed = in_array($status, [PoStatus::AwaitingPurchasingOk, PoStatus::Confirmed, PoStatus::InProgress, PoStatus::Completed], true);

        $poItem = $po->items()->create([
            'item_id' => $this->rmItem()->id,
            'qty_ordered' => 30,
            'qty_confirmed' => $confirmed ? 30 : null,
        ]);

        $po->schedules()->create([
            'purchase_order_item_id' => $poItem->id,
            'ohp_supplier_id' => $ohpSupplierId,
            'scheduled_date' => '2026-12-05',
            'qty' => 30,
            'qty_confirmed' => $confirmed ? 30 : null,
            'status' => ScheduleStatus::Planned,
        ]);

        return $po->fresh(['items', 'schedules']);
    }

    private function makeFixtureDn(ScheduleStatus $scheduleStatus, int $supplierRmId = 10, int $ohpSupplierId = 8): DeliveryNote
    {
        $po = $this->makeFixturePo(PoStatus::Confirmed, $supplierRmId, $ohpSupplierId);
        $schedule = $po->schedules->first();
        $schedule->update([
            'status' => $scheduleStatus,
            'rm_sj_number' => 'SJ-FIXTURE-'.Str::random(6),
        ]);

        return DeliveryNote::create([
            'dn_number' => 'DN-'.$po->po_number.'-001',
            'purchase_order_id' => $po->id,
            'delivery_schedule_id' => $schedule->id,
            'purchase_order_item_id' => $schedule->purchase_order_item_id,
            'qty' => 30,
            'delivery_date' => '2026-12-05',
            'rm_sj_number' => $schedule->rm_sj_number,
            'generated_at' => now(),
        ]);
    }

    // ------------------------------------------------------------------
    // Happy path — every role, in sequence, through real HTTP routes,
    // reaching real QAD at the two integration points (approve, receive).
    // ------------------------------------------------------------------

    public function test_full_workflow_across_all_roles_reaches_qad(): void
    {
        Storage::fake('public');

        $marker = 'E2E-ROLE-TEST-'.Str::random(6);

        // 1. Purchasing creates the PO.
        $response = $this->actingAs($this->purchasing())->post(route('purchase-orders.store'), [
            'supplier_code' => 'L0254',
            'due_date' => '2026-12-20',
            'notes' => $marker,
            'items' => [[
                'item_number' => 'CMWNR00002',
                'qty_ordered' => 30,
                'schedules' => [[
                    'ohp_supplier_code' => 'L0061',
                    'scheduled_date' => '2026-12-05',
                    'qty' => 30,
                ]],
            ]],
        ]);
        $response->assertRedirect();

        $po = PurchaseOrder::where('notes', $marker)->firstOrFail();
        $this->assertSame(PoStatus::Draft, $po->status);
        $this->assertSame(10, $po->supplier_rm_id); // resolved to L0254's company

        // 2. Purchasing submits to RM.
        $this->actingAs($this->purchasing())
            ->post(route('purchase-orders.submit', $po))
            ->assertRedirect();
        $po->refresh();
        $this->assertSame(PoStatus::AwaitingRmConfirm, $po->status);

        // 3. Supplier RM confirms qty + schedule.
        $poItem = $po->items->first();
        $schedule = $po->schedules->first();
        $this->actingAs($this->rm())
            ->post(route('purchase-orders.confirm-rm', $po), [
                'items' => [['id' => $poItem->id, 'qty_confirmed' => 30]],
                'schedules' => [['id' => $schedule->id, 'qty_confirmed' => 30]],
            ])
            ->assertRedirect();
        $po->refresh();
        $this->assertSame(PoStatus::AwaitingPurchasingOk, $po->status);

        // 4. Purchasing approves -> real maintainPurchaseOrder push to QAD.
        $this->actingAs($this->purchasing())
            ->post(route('purchase-orders.approve', $po))
            ->assertRedirect();
        $po->refresh();
        $this->assertSame(PoStatus::Confirmed, $po->status);
        $this->assertSame('success', $po->qad_status->value);
        $this->assertNotNull($po->qad_po_number);
        $this->assertSame($po->qad_po_number, $po->po_number, 'DR- placeholder should be replaced by the real QAD PO number');
        $this->assertNotNull($poItem->fresh()->qad_line_number);

        // 5. Supplier RM fills SJ + generates DN, then confirms shipment.
        $schedule->refresh();
        $response = $this->actingAs($this->rm())->post(
            route('delivery-schedules.generate-dn', $schedule),
            ['rm_sj_number' => 'SJ-'.$marker, 'delivery_date' => '2026-12-05'],
        );
        $response->assertRedirect();
        $dn = DeliveryNote::where('delivery_schedule_id', $schedule->id)->firstOrFail();

        $this->actingAs($this->rm())
            ->post(route('delivery-notes.confirm-shipment', $dn))
            ->assertRedirect();
        $this->assertSame(ScheduleStatus::ShipConfirmed, $schedule->fresh()->status);

        // 6. Supplier OHP confirms receipt with an SJ document upload.
        $file = UploadedFile::fake()->create('sj.pdf', 10, 'application/pdf');
        $this->actingAs($this->ohp())
            ->post(route('delivery-notes.confirm-ohp', $dn), [
                'sj_document' => $file,
                'notes' => 'E2E OHP confirmation',
            ])
            ->assertRedirect();
        $this->assertSame(ScheduleStatus::OhpOk, $schedule->fresh()->status);

        // 7. PPIC receives in two increments -> real receivePurchaseOrder pushes to QAD.
        $this->actingAs($this->ppic())
            ->post(route('receivings.store', $dn), ['received_qty' => 18])
            ->assertRedirect();
        $dn->refresh();
        $this->assertSame(18, $dn->received_qty);
        $this->assertSame(12, $dn->remaining_qty);
        $this->assertFalse($dn->is_fully_received);
        $this->assertSame('success', $dn->receivings->first()->qad_status->value);

        $this->actingAs($this->ppic())
            ->post(route('receivings.store', $dn), ['received_qty' => 12])
            ->assertRedirect();
        $dn->refresh();
        $this->assertSame(30, $dn->received_qty);
        $this->assertSame(0, $dn->remaining_qty);
        $this->assertTrue($dn->is_fully_received);

        $po->refresh();
        $this->assertSame(PoStatus::Completed, $po->status);
    }

    // ------------------------------------------------------------------
    // Authorization boundaries — one method per step of the flow.
    // ------------------------------------------------------------------

    public function test_only_purchasing_and_admin_can_create_po(): void
    {
        $payload = [
            'supplier_code' => 'L0254',
            'due_date' => '2026-12-20',
            'items' => [[
                'item_number' => 'CMWNR00002',
                'qty_ordered' => 10,
                'schedules' => [['ohp_supplier_code' => 'L0061', 'scheduled_date' => '2026-12-05', 'qty' => 10]],
            ]],
        ];

        foreach ([$this->rm(), $this->ohp(), $this->ppic()] as $user) {
            $this->actingAs($user)->post(route('purchase-orders.store'), $payload)->assertForbidden();
        }

        $this->actingAs($this->admin())->post(route('purchase-orders.store'), array_merge($payload, ['notes' => 'admin-create-'.Str::random(4)]))
            ->assertRedirect();
    }

    public function test_only_the_matching_supplier_rm_can_confirm_the_po(): void
    {
        $po = $this->makeFixturePo(PoStatus::AwaitingRmConfirm);
        $poItem = $po->items->first();
        $schedule = $po->schedules->first();
        $payload = [
            'items' => [['id' => $poItem->id, 'qty_confirmed' => 30]],
            'schedules' => [['id' => $schedule->id, 'qty_confirmed' => 30]],
        ];

        foreach ([$this->rmWrong(), $this->rmOther(), $this->ohp(), $this->ppic(), $this->purchasing()] as $user) {
            $this->actingAs($user)->post(route('purchase-orders.confirm-rm', $po), $payload)->assertForbidden();
        }

        $this->actingAs($this->rm())->post(route('purchase-orders.confirm-rm', $po), $payload)->assertRedirect();
        $this->assertSame(PoStatus::AwaitingPurchasingOk, $po->fresh()->status);
    }

    public function test_supplier_rm_can_move_schedule_date_within_due_month_but_not_outside_it(): void
    {
        // due_date is 2026-12-20 in makeFixturePo, so the confirm matrix's
        // valid range is the whole of December 2026.
        $po = $this->makeFixturePo(PoStatus::AwaitingRmConfirm);
        $poItem = $po->items->first();
        $schedule = $po->schedules->first();

        $this->actingAs($this->rm())
            ->post(route('purchase-orders.confirm-rm', $po), [
                'items' => [['id' => $poItem->id, 'qty_confirmed' => 30]],
                'schedules' => [['id' => $schedule->id, 'qty_confirmed' => 30, 'scheduled_date' => '2027-01-05']],
            ])
            ->assertSessionHasErrors('schedules.0.scheduled_date');
        $this->assertSame('2026-12-05', $schedule->fresh()->scheduled_date->format('Y-m-d'));

        $this->actingAs($this->rm())
            ->post(route('purchase-orders.confirm-rm', $po), [
                'items' => [['id' => $poItem->id, 'qty_confirmed' => 30]],
                'schedules' => [['id' => $schedule->id, 'qty_confirmed' => 30, 'scheduled_date' => '2026-12-18']],
            ])
            ->assertRedirect();
        $this->assertSame('2026-12-18', $schedule->fresh()->scheduled_date->format('Y-m-d'));
    }

    public function test_only_purchasing_and_admin_can_approve_or_reject_po(): void
    {
        $po = $this->makeFixturePo(PoStatus::AwaitingPurchasingOk);

        foreach ([$this->rm(), $this->ohp(), $this->ppic()] as $user) {
            $this->actingAs($user)->post(route('purchase-orders.approve', $po))->assertForbidden();
            $this->actingAs($user)->post(route('purchase-orders.reject', $po), ['reason' => 'nope'])->assertForbidden();
        }

        // Reject doesn't touch QAD — safe to actually exercise the success path.
        $this->actingAs($this->purchasing())
            ->post(route('purchase-orders.reject', $po), ['reason' => 'Qty tidak sesuai stok'])
            ->assertRedirect();
        $po->refresh();
        $this->assertSame(PoStatus::AwaitingRmConfirm, $po->status);
        $this->assertSame('Qty tidak sesuai stok', $po->rejection_reason);
        // RM's last-submitted qty_confirmed must survive a reject — they
        // revise from their own numbers, not lose them back to the draft.
        $this->assertSame(30, $po->items->first()->fresh()->qty_confirmed);
    }

    public function test_only_the_matching_supplier_rm_can_generate_dn_and_confirm_shipment(): void
    {
        $po = $this->makeFixturePo(PoStatus::Confirmed);
        $schedule = $po->schedules->first();

        foreach ([$this->rmWrong(), $this->rmOther(), $this->ohp(), $this->ppic(), $this->purchasing()] as $user) {
            $this->actingAs($user)
                ->post(route('delivery-schedules.generate-dn', $schedule), ['rm_sj_number' => 'SJ-INTRUDER'])
                ->assertForbidden();
        }

        $this->actingAs($this->rm())
            ->post(route('delivery-schedules.generate-dn', $schedule), ['rm_sj_number' => 'SJ-OK-001'])
            ->assertRedirect();
        $dn = DeliveryNote::where('delivery_schedule_id', $schedule->id)->firstOrFail();

        foreach ([$this->rmWrong(), $this->rmOther(), $this->ohp(), $this->ppic(), $this->purchasing()] as $user) {
            $this->actingAs($user)->post(route('delivery-notes.confirm-shipment', $dn))->assertForbidden();
        }

        $this->actingAs($this->rm())->post(route('delivery-notes.confirm-shipment', $dn))->assertRedirect();
        $this->assertSame(ScheduleStatus::ShipConfirmed, $schedule->fresh()->status);
    }

    public function test_only_the_matching_supplier_ohp_can_confirm_receipt(): void
    {
        Storage::fake('public');
        $dn = $this->makeFixtureDn(ScheduleStatus::ShipConfirmed);

        foreach ([$this->ohpWrong(), $this->rm(), $this->ppic(), $this->purchasing()] as $user) {
            $this->actingAs($user)
                ->post(route('delivery-notes.confirm-ohp', $dn), [
                    'sj_document' => UploadedFile::fake()->create('sj.pdf', 5, 'application/pdf'),
                ])
                ->assertForbidden();
        }

        $this->actingAs($this->ohp())
            ->post(route('delivery-notes.confirm-ohp', $dn), [
                'sj_document' => UploadedFile::fake()->create('sj.pdf', 5, 'application/pdf'),
            ])
            ->assertRedirect();
        $this->assertSame(ScheduleStatus::OhpOk, $dn->deliverySchedule->fresh()->status);
    }

    public function test_only_ppic_and_admin_can_receive_and_kill_switch_blocks_everyone(): void
    {
        $dn = $this->makeFixtureDn(ScheduleStatus::OhpOk);

        // Route middleware ('role:ppic,admin') blocks every other role,
        // including Purchasing and the two supplier roles, before the
        // request even reaches DeliveryNotePolicy::receive().
        foreach ([$this->rm(), $this->ohp(), $this->purchasing()] as $user) {
            $this->actingAs($user)
                ->post(route('receivings.store', $dn), ['received_qty' => 10])
                ->assertForbidden();
        }

        // Kill switch: even PPIC is blocked when qad.receiving_enabled=false.
        config(['qad.receiving_enabled' => false]);
        $this->actingAs($this->ppic())
            ->post(route('receivings.store', $dn), ['received_qty' => 10])
            ->assertForbidden();
        $this->assertSame(0, $dn->receivings()->count());
    }

    public function test_supplier_rm_cannot_view_another_rms_po(): void
    {
        $po = $this->makeFixturePo(PoStatus::Confirmed); // supplier_rm_id = company 10 (L0254)

        $this->actingAs($this->rmWrong())->get(route('purchase-orders.show', $po))->assertForbidden();
        $this->actingAs($this->rmOther())->get(route('purchase-orders.show', $po))->assertForbidden();

        // Owning RM, and every SDI-staff role, can view it.
        $this->actingAs($this->rm())->get(route('purchase-orders.show', $po))->assertOk();
        $this->actingAs($this->ppic())->get(route('purchase-orders.show', $po))->assertOk();
        $this->actingAs($this->purchasing())->get(route('purchase-orders.show', $po))->assertOk();
        $this->actingAs($this->admin())->get(route('purchase-orders.show', $po))->assertOk();

        // OHP not scheduled on this PO at all cannot view it either.
        $this->actingAs($this->ohpWrong())->get(route('purchase-orders.show', $po))->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Admin area / user management.
    // ------------------------------------------------------------------

    public function test_admin_can_create_users_of_any_role_including_admin(): void
    {
        $email = 'e2e-admin-created-'.Str::random(6).'@example.test';

        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'E2E New Admin',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ])->assertRedirect(route('admin.users.index'));

        $created = User::where('email', $email)->firstOrFail();
        $this->assertTrue($created->hasRole('admin'));
    }

    public function test_purchasing_can_create_non_admin_users_but_not_admin(): void
    {
        $email = 'e2e-ppic-created-'.Str::random(6).'@example.test';

        $this->actingAs($this->purchasing())->post(route('admin.users.store'), [
            'name' => 'E2E New PPIC',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'ppic',
        ])->assertRedirect(route('admin.users.index'));
        $this->assertTrue(User::where('email', $email)->exists());

        $adminEmail = 'e2e-should-not-exist-'.Str::random(6).'@example.test';
        $this->actingAs($this->purchasing())->post(route('admin.users.store'), [
            'name' => 'Should Not Be Created',
            'email' => $adminEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ])->assertSessionHasErrors('role');
        $this->assertFalse(User::where('email', $adminEmail)->exists());

        // And Purchasing can't reach an *existing* Admin account either.
        $this->actingAs($this->purchasing())->get(route('admin.users.edit', $this->admin()))->assertForbidden();
        $this->actingAs($this->purchasing())->delete(route('admin.users.destroy', $this->admin()))->assertForbidden();
    }

    public function test_supplier_and_ppic_roles_cannot_reach_admin_area(): void
    {
        foreach ([$this->rm(), $this->ohp(), $this->ppic()] as $user) {
            $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.qad-items.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.qad-suppliers.index'))->assertForbidden();
        }
    }
}
