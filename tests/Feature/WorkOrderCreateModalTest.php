<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Models\WoCategory;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderCreateModalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_create_url_opens_listing_with_modal_flag(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('work-orders.create'))
            ->assertRedirect(route('work-orders.index', ['buat' => 1]));
    }

    public function test_listing_contains_create_modal(): void
    {
        $mtc = Department::create(['name' => 'Maintenance', 'code' => 'MTC', 'slug' => 'maintenance']);
        WoCategory::create([
            'department_id' => $mtc->id,
            'name' => 'Safety',
            'leadtime_days' => 1,
        ]);
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('work-orders.index'))
            ->assertOk()
            ->assertSee('id="wo-create-modal"', false)
            ->assertSee('js-open-wo-create', false)
            ->assertSee('Buat Work Order Baru')
            ->assertSee('Kirim Work Order');
    }

    public function test_store_still_creates_work_order(): void
    {
        $mtc = Department::create(['name' => 'Maintenance', 'code' => 'MTC', 'slug' => 'maintenance']);
        $category = WoCategory::create([
            'department_id' => $mtc->id,
            'name' => 'Safety',
            'leadtime_days' => 1,
        ]);
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->from(route('work-orders.index'))
            ->post(route('work-orders.store'), [
                'title' => 'Perbaikan mesin press',
                'description' => 'Mesin press line 3 macet.',
                'target_department_id' => $mtc->id,
                'wo_category_id' => $category->id,
            ])
            ->assertRedirect();

        $wo = WorkOrder::first();
        $this->assertNotNull($wo);
        $this->assertSame('Perbaikan mesin press', $wo->title);
        $this->assertSame('pending', $wo->status);
        $this->assertSame($user->id, $wo->requester_id);
    }

    public function test_validation_error_redisplays_modal(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->followRedirects(
            $this->actingAs($user)
                ->from(route('work-orders.index'))
                ->post(route('work-orders.store'), [])
        )
            ->assertSee('data-open="1"', false)
            ->assertSee('Judul WO');
    }
}
