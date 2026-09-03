<?php

use App\Enums\UserRole;
use App\Models\Line;
use App\Models\Product;
use App\Models\ProductModel;
use App\Models\ProductionLog;
use App\Models\User;
use App\Policies\ProductPolicy;
use App\Policies\ProductionLogPolicy;
use Illuminate\Support\Str;

test('inactive users cannot authenticate', function () {
    $user = User::factory()->inactive()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('products index paginates and filters by category', function () {
    $admin = User::factory()->role(UserRole::Gm)->create();
    $line = Line::create(['name' => 'Line A', 'code' => 'LA', 'is_active' => true]);
    $model = ProductModel::create(['line_id' => $line->id, 'name' => 'Model A', 'is_active' => true]);

    foreach (range(1, 55) as $i) {
        Product::create([
            'product_model_id' => $model->id,
            'name' => "Product {$i}",
            'code' => "P{$i}",
            'category' => $i % 2 === 0 ? 'FG' : 'SA',
            'is_active' => true,
        ]);
    }

    $this->actingAs($admin)
        ->get(route('products.index', ['category' => 'FG']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('MasterData/Products/Index')
            ->has('products.data', 27)
            ->where('products.per_page', 50)
            ->where('filters.category', 'FG'));
});

test('catalog models require line access', function () {
    $leader = User::factory()->role(UserRole::Leader)->create();
    $allowed = Line::create(['name' => 'Allowed', 'code' => 'AL', 'is_active' => true]);
    $denied = Line::create(['name' => 'Denied', 'code' => 'DN', 'is_active' => true]);
    $leader->lines()->attach($allowed->id);

    ProductModel::create(['line_id' => $allowed->id, 'name' => 'M1', 'is_active' => true]);

    $this->actingAs($leader)
        ->getJson(route('catalog.models', ['line_id' => $allowed->id]))
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs($leader)
        ->getJson(route('catalog.models', ['line_id' => $denied->id]))
        ->assertForbidden();
});

test('catalog products support search', function () {
    $leader = User::factory()->role(UserRole::Leader)->create();
    $line = Line::create(['name' => 'Line B', 'code' => 'LB', 'is_active' => true]);
    $leader->lines()->attach($line->id);
    $model = ProductModel::create(['line_id' => $line->id, 'name' => 'Model B', 'is_active' => true]);

    Product::create([
        'product_model_id' => $model->id,
        'name' => 'Alpha Widget',
        'code' => 'AW-1',
        'category' => 'FG',
        'is_active' => true,
    ]);
    Product::create([
        'product_model_id' => $model->id,
        'name' => 'Beta Gadget',
        'code' => 'BG-1',
        'category' => 'FG',
        'is_active' => true,
    ]);

    $this->actingAs($leader)
        ->getJson(route('catalog.products', [
            'product_model_id' => $model->id,
            'search' => 'Alpha',
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alpha Widget');
});

test('policy matrix for master data and entry', function () {
    $gm = User::factory()->role(UserRole::Gm)->create();
    $unitHead = User::factory()->role(UserRole::UnitHead)->create();
    $leader = User::factory()->role(UserRole::Leader)->create();
    $productPolicy = new ProductPolicy;
    $logPolicy = new ProductionLogPolicy;

    expect($productPolicy->viewAny($gm))->toBeTrue()
        ->and($productPolicy->viewAny($unitHead))->toBeFalse()
        ->and($productPolicy->viewAny($leader))->toBeFalse()
        ->and($logPolicy->create($leader))->toBeTrue()
        ->and($logPolicy->create($unitHead))->toBeTrue()
        ->and($logPolicy->create($gm))->toBeFalse();
});

test('client_uuid makes entry store idempotent', function () {
    $leader = User::factory()->role(UserRole::Leader)->create();
    $line = Line::create(['name' => 'Line C', 'code' => 'LC', 'is_active' => true]);
    $leader->lines()->attach($line->id);
    $model = ProductModel::create(['line_id' => $line->id, 'name' => 'Model C', 'is_active' => true]);
    $product = Product::create([
        'product_model_id' => $model->id,
        'name' => 'Widget C',
        'is_active' => true,
    ]);

    $uuid = (string) Str::uuid();
    $payload = [
        'line_id' => $line->id,
        'product_model_id' => $model->id,
        'product_id' => $product->id,
        'total_production' => 10,
        'total_reject' => 1,
        'total_repair' => 0,
        'client_uuid' => $uuid,
    ];

    $this->actingAs($leader)
        ->postJson(route('entry.store'), $payload)
        ->assertCreated();

    $this->actingAs($leader)
        ->postJson(route('entry.store'), $payload)
        ->assertCreated();

    expect(ProductionLog::where('client_uuid', $uuid)->count())->toBe(1);
});
