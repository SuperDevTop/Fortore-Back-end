<?php

namespace Tests\Feature\API;

use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PosTest extends TestCase
{
    use WithFaker;

    /**
     * A basic feature test example.
     *
     * @return void
     * @test
     */
    public function it_reject_invalid_post_access_key()
    {
        $response = $this->getJson('/api/pos/validate_pin_code');

        $response->assertForbidden();
    }

    /**
     * A basic feature test example.
     *
     * @return void
     * @test
     */
    public function it_reject_invalid_user_pin_code()
    {
        $response = $this->getJson('/api/pos/validate_pin_code', [
            'POS_APP_ACCESS_KEY' => config('services.pos_app.access_key')
        ]);

        $response->assertStatus(422);
    }


    /**
     * A basic feature test example.
     *
     * @return void
     * @test
     */
    public function it_fetch_user_loyalty_details()
    {
        $user = User::factory()->create([
            'pin_code' => $this->faker->postcode
        ]);
        $response = $this->getJson('/api/pos/validate_pin_code?pin_code=' . $user->pin_code, [
            'POS_APP_ACCESS_KEY' => config('services.pos_app.access_key'),
        ]);

        $response->assertOk()->assertJson([
            'username' => $user->username,
            'balance' => $user->loyaltyPointsBalance()
        ]);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     * @test
     */
    public function it_sub_loyalty_points_transaction()
    {
        $amount = $this->faker->randomFloat(2);
        $user = User::factory()->create([
            'pin_code' => $this->faker->postcode
        ]);
        $user->loyaltyPoints()->create([
            'pos_order_id' => 0,
            'amount' => $amount * 2,
            'type' => 'add'
        ]);

        $response = $this->postJson('/api/pos/loyalty_points', [
            'pin_code' => $user->pin_code,
            'amount' => $amount,
            'order_id' => $this->faker->randomDigit(),
            'type' => "sub"
        ], [
            'POS_APP_ACCESS_KEY' => config('services.pos_app.access_key'),
        ]);

        $response->assertCreated();
    }

    /**
     * A basic feature test example.
     *
     * @return void
     * @test
     */
    public function it_add_loyalty_points_transaction()
    {
        $user = User::factory()->create([
            'pin_code' => $this->faker->postcode
        ]);
        $response = $this->postJson('/api/pos/loyalty_points', [
            'pin_code' => $user->pin_code,
            'amount' => $this->faker->randomFloat(2, 1000, 2000),
            'order_id' => $this->faker->randomDigit(),
            'type' => "add"
        ], [
            'POS_APP_ACCESS_KEY' => config('services.pos_app.access_key'),
        ]);

        $response->assertCreated();
    }
}
