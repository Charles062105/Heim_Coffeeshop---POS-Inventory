<?php

namespace Tests\Feature;

use App\Models\ProductAddon;
use App\Models\AddonIngredient;
use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAddonManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_update_archive_and_restore_an_addon(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)->post(route('addons.store'), [
            'name' => 'Extra Espresso Shot',
            'price' => '25.00',
        ])->assertRedirect(route('addons.index'))
            ->assertSessionHasNoErrors();

        $addon = ProductAddon::where('name', 'Extra Espresso Shot')->firstOrFail();
        $this->assertSame('active', $addon->status);
        $this->assertSame('25.00', $addon->price);

        $this->actingAs($manager)->put(route('addons.update', $addon), [
            'name' => 'Double Espresso Shot',
            'price' => '35.00',
        ])->assertRedirect(route('addons.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('product_addons', [
            'id' => $addon->id,
            'name' => 'Double Espresso Shot',
            'price' => '35.00',
            'status' => 'active',
        ]);

        $this->actingAs($manager)->patch(route('addons.toggle', $addon))
            ->assertRedirect()
            ->assertSessionHas('success', 'Add-on "Double Espresso Shot" archived.');
        $this->assertDatabaseHas('product_addons', ['id' => $addon->id, 'status' => 'inactive']);

        $this->actingAs($manager)->patch(route('addons.toggle', $addon))
            ->assertRedirect()
            ->assertSessionHas('success', 'Add-on "Double Espresso Shot" unarchived.');
        $this->assertDatabaseHas('product_addons', ['id' => $addon->id, 'status' => 'active']);
    }

    public function test_addon_management_validates_names_and_prices(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        ProductAddon::create(['name' => 'Existing Add-on', 'price' => 5, 'status' => 'active']);

        $this->actingAs($manager)->from(route('addons.index'))->post(route('addons.store'), [
            'name' => 'Existing Add-on',
            'price' => '-1.00',
        ])->assertSessionHasErrors(['name', 'price']);

        $this->assertDatabaseCount('product_addons', 1);
    }

    public function test_only_unarchived_addons_are_offered_on_the_pos(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        ProductAddon::create(['name' => 'Available Topping', 'price' => 10, 'status' => 'active']);
        ProductAddon::create(['name' => 'Archived Topping', 'price' => 10, 'status' => 'inactive']);

        $this->actingAs($manager)->get(route('pos.index'))
            ->assertOk()
            ->assertSee('Available Topping')
            ->assertDontSee('Archived Topping');
    }

    public function test_cashier_cannot_manage_addons(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier)->get(route('addons.index'))->assertForbidden();
        $this->actingAs($cashier)->post(route('addons.store'), [
            'name' => 'Unauthorized Add-on',
            'price' => 10,
        ])->assertForbidden();

        $this->assertDatabaseCount('product_addons', 0);
    }

    public function test_manager_can_save_additive_and_substitute_ingredient_mappings(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $addon = ProductAddon::create(['name' => 'Oat Milk', 'price' => 0, 'status' => 'active']);
        $oatMilk = Ingredient::create(['name' => 'Oat Milk', 'unit' => 'ml', 'minimum_stock' => 5, 'status' => 'active']);
        $freshMilk = Ingredient::create(['name' => 'Fresh Milk', 'unit' => 'ml', 'minimum_stock' => 5, 'status' => 'active']);
        $syrup = Ingredient::create(['name' => 'Vanilla Syrup', 'unit' => 'ml', 'minimum_stock' => 5, 'status' => 'active']);

        $this->actingAs($manager)->put(route('addons.ingredients.update', $addon), [
            'ingredients' => [
                ['ingredient_id' => $oatMilk->id, 'quantity' => '180.000', 'mode' => 'substitute', 'replaces_ingredient_id' => $freshMilk->id],
                ['ingredient_id' => $syrup->id, 'quantity' => '2.500', 'mode' => 'additive'],
            ],
        ])->assertRedirect(route('addons.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('product_addon_ingredients', [
            'product_addon_id' => $addon->id,
            'ingredient_id' => $oatMilk->id,
            'quantity' => '180.000',
            'replaces_ingredient_id' => $freshMilk->id,
        ]);
        $this->assertDatabaseHas('product_addon_ingredients', [
            'product_addon_id' => $addon->id,
            'ingredient_id' => $syrup->id,
            'quantity' => '2.500',
            'replaces_ingredient_id' => null,
        ]);
    }

    public function test_addon_ingredient_mappings_reject_invalid_substitutions_and_duplicate_sources(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $addon = ProductAddon::create(['name' => 'Invalid Substitute', 'price' => 0, 'status' => 'active']);
        $oatMilk = Ingredient::create(['name' => 'Oat Milk', 'unit' => 'ml', 'minimum_stock' => 5, 'status' => 'active']);
        $freshMilk = Ingredient::create(['name' => 'Fresh Milk', 'unit' => 'ml', 'minimum_stock' => 5, 'status' => 'active']);
        $almondMilk = Ingredient::create(['name' => 'Almond Milk', 'unit' => 'ml', 'minimum_stock' => 5, 'status' => 'active']);

        $this->actingAs($manager)->from(route('addons.index'))->put(route('addons.ingredients.update', $addon), [
            'ingredients' => [
                ['ingredient_id' => $oatMilk->id, 'quantity' => '180', 'mode' => 'substitute', 'replaces_ingredient_id' => $freshMilk->id],
                ['ingredient_id' => $almondMilk->id, 'quantity' => '180', 'mode' => 'substitute', 'replaces_ingredient_id' => $freshMilk->id],
            ],
        ])->assertSessionHasErrors('ingredients.1.replaces_ingredient_id');

        $this->assertDatabaseCount('product_addon_ingredients', 0);
    }

    public function test_archived_ingredients_cannot_be_added_to_new_addon_mappings(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $addon = ProductAddon::create(['name' => 'Archived Ingredient Add-on', 'price' => 0, 'status' => 'active']);
        $inactiveIngredient = Ingredient::create(['name' => 'Archived Syrup', 'unit' => 'ml', 'minimum_stock' => 5, 'status' => 'inactive']);

        $this->actingAs($manager)->from(route('addons.index'))->put(route('addons.ingredients.update', $addon), [
            'ingredients' => [
                ['ingredient_id' => $inactiveIngredient->id, 'quantity' => '10', 'mode' => 'additive'],
            ],
        ])->assertSessionHasErrors('ingredients.0.ingredient_id');

        $this->assertDatabaseCount('product_addon_ingredients', 0);
    }

    public function test_substitution_mapping_requires_matching_stock_units(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $addon = ProductAddon::create(['name' => 'Unit Mismatch Substitute', 'price' => 0, 'status' => 'active']);
        $oatMilk = Ingredient::create(['name' => 'Oat Milk', 'unit' => 'L', 'minimum_stock' => 5, 'status' => 'active']);
        $freshMilk = Ingredient::create(['name' => 'Fresh Milk', 'unit' => 'ml', 'minimum_stock' => 5, 'status' => 'active']);

        $this->actingAs($manager)->from(route('addons.index'))->put(route('addons.ingredients.update', $addon), [
            'ingredients' => [
                ['ingredient_id' => $oatMilk->id, 'quantity' => '0.180', 'mode' => 'substitute', 'replaces_ingredient_id' => $freshMilk->id],
            ],
        ])->assertSessionHasErrors('ingredients.0.replaces_ingredient_id');

        $this->assertDatabaseCount('product_addon_ingredients', 0);
    }
}
