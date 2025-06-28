<?php
/**
 * Test CoCart Cart Controller
 *
 * Tests for CoCart cart API endpoints including cart management,
 * item operations, and cart calculations.
 *
 * @package CoCart\Tests\Unit
 */

/**
 * Test CoCart Cart Controller Class
 *
 * Tests the cart API endpoints which handle cart operations like
 * adding, removing, and updating items, as well as cart calculations.
 *
 * @package CoCart\Tests\Unit
 */
class Test_CoCart_Cart_Controller extends CoCart_API_V2_Test_Case {

	/**
	 * Test getting cart when empty.
	 *
	 * Verifies that the cart endpoint returns a proper response structure
	 * when the cart is empty, including empty items array and zero count.
	 *
	 * @return void
	 */
	public function test_get_cart_when_empty() {
		$response = $this->get_cart();

		$this->assert_rest_response_status( 200, $response );
		$this->assert_rest_response_content_type( 'application/json', $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'items', $data );
		$this->assertEmpty( $data['items'] );
		$this->assertArrayHasKey( 'item_count', $data );
		$this->assertEquals( 0, $data['item_count'] );
	}

	/**
	 * Test adding item to cart.
	 *
	 * Verifies that products can be successfully added to the cart
	 * with the specified quantity and that the response contains
	 * the correct item data.
	 *
	 * @return void
	 */
	public function test_add_item_to_cart() {
		$product = $this->create_product();

		$response = $this->add_item_to_cart( $product->get_id(), 2 );

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'items', $data );
		$this->assertCount( 1, $data['items'] );

		$item = $data['items'][0];
		$this->assertEquals( $product->get_id(), $item['product_id'] );
		$this->assertEquals( 2, $item['quantity'] );
	}

	/**
	 * Test adding invalid product to cart.
	 *
	 * Verifies that attempting to add a non-existent product to the cart
	 * returns an appropriate error response with the correct error code.
	 *
	 * @return void
	 */
	public function test_add_invalid_product_to_cart() {
		$response = $this->add_item_to_cart( 99999, 1 );

		$this->assert_rest_response_status( 400, $response );
		$this->assert_rest_response_error( 'cocart_product_not_found', $response );
	}

	/**
	 * Test removing item from cart.
	 *
	 * Verifies that items can be successfully removed from the cart
	 * using their item key and that the cart becomes empty after removal.
	 *
	 * @return void
	 */
	public function test_remove_item_from_cart() {
		$product = $this->create_product();

		// Add item to cart.
		$add_response = $this->add_item_to_cart( $product->get_id(), 1 );
		$this->assert_rest_response_status( 200, $add_response );

		$item_key = $this->get_item_key_from_response( $add_response );

		// Remove item from cart.
		$response = $this->remove_item_from_cart( $item_key );

		$this->assert_rest_response_status( 200, $response );

		// Verify cart is empty.
		$this->assert_cart_is_empty();
	}

	/**
	 * Test updating item quantity in cart.
	 *
	 * Verifies that item quantities can be successfully updated in the cart
	 * and that the response reflects the new quantity.
	 *
	 * @return void
	 */
	public function test_update_item_quantity_in_cart() {
		$product = $this->create_product();

		// Add item to cart.
		$add_response = $this->add_item_to_cart( $product->get_id(), 1 );
		$this->assert_rest_response_status( 200, $add_response );

		$item_key = $this->get_item_key_from_response( $add_response );

		// Update item quantity.
		$response = $this->update_item_in_cart( $item_key, 3 );

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'items', $data );
		$this->assertCount( 1, $data['items'] );

		$item = $data['items'][0];
		$this->assertEquals( 3, $item['quantity'] );
	}

	/**
	 * Test clearing cart.
	 *
	 * Verifies that the cart can be completely cleared of all items
	 * and that the cart becomes empty after the clear operation.
	 *
	 * @return void
	 */
	public function test_clear_cart() {
		$product1 = $this->create_product( array( 'name' => 'Product 1' ) );
		$product2 = $this->create_product( array( 'name' => 'Product 2' ) );

		// Add items to cart.
		$this->add_item_to_cart( $product1->get_id(), 1 );
		$this->add_item_to_cart( $product2->get_id(), 2 );

		// Verify cart has items.
		$this->assert_cart_has_items( 2 );

		// Clear cart.
		$response = $this->clear_cart();

		$this->assert_rest_response_status( 200, $response );

		// Verify cart is empty.
		$this->assert_cart_is_empty();
	}

	/**
	 * Test getting cart totals.
	 *
	 * Verifies that cart totals are calculated correctly based on
	 * the items in the cart and their quantities.
	 *
	 * @return void
	 */
	public function test_get_cart_totals() {
		$product = $this->create_product( array( 'regular_price' => '15.00' ) );

		// Add item to cart.
		$this->add_item_to_cart( $product->get_id(), 2 );

		// Get cart totals.
		$response = $this->get_cart_totals();

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'total', $data );
		$this->assertEquals( '30.00', $data['total'] );
	}

	/**
	 * Test getting cart count.
	 *
	 * Verifies that the cart count endpoint returns the correct
	 * total quantity of all items in the cart.
	 *
	 * @return void
	 */
	public function test_get_cart_count() {
		$product1 = $this->create_product( array( 'name' => 'Product 1' ) );
		$product2 = $this->create_product( array( 'name' => 'Product 2' ) );

		// Add items to cart.
		$this->add_item_to_cart( $product1->get_id(), 1 );
		$this->add_item_to_cart( $product2->get_id(), 3 );

		// Get cart count.
		$response = $this->get_cart_count();

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'count', $data );
		$this->assertEquals( 4, $data['count'] );
	}

	/**
	 * Test cart with multiple items.
	 *
	 * Verifies that the cart can handle multiple items with different
	 * quantities and that totals are calculated correctly across all items.
	 *
	 * @return void
	 */
	public function test_cart_with_multiple_items() {
		$product1 = $this->create_product( array( 'name' => 'Product 1', 'regular_price' => '10.00' ) );
		$product2 = $this->create_product( array( 'name' => 'Product 2', 'regular_price' => '20.00' ) );

		// Add items to cart.
		$this->add_item_to_cart( $product1->get_id(), 2 );
		$this->add_item_to_cart( $product2->get_id(), 1 );

		// Get cart.
		$response = $this->get_cart();

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'items', $data );
		$this->assertCount( 2, $data['items'] );

		// Verify total.
		$this->assertArrayHasKey( 'totals', $data );
		$this->assertEquals( '40.00', $data['totals']['total'] );
	}
} 