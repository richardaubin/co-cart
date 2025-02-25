<?php
/**
 * Test cases for Cart API v2.
 */
class CoCart_Cart_API_v2_Tests extends CoCart_Test_Case {

	public function test_add_to_cart_success() {
		// Create a product.
		$product = $this->create_product();

		// Simulate adding product to cart via CoCart API.
		$response = $this->add_to_cart( $product->get_id() );

		// Check if the product was added to the cart.
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'cart', $data );
		$this->assertArrayHasKey( 'items', $data['cart'] );
		$this->assertCount( 1, $data['cart']['items'] );
		$this->assertEquals( $product->get_id(), $data['cart']['items'][0]['product_id'] );
	}

	public function test_add_to_cart_failure() {
		// Simulate adding a non-existent product to cart via CoCart API.
		$response = $this->add_to_cart( 999999 );

		// Check if the response indicates failure.
		$this->assertEquals( 404, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'woocommerce_rest_invalid_product_id', $data['code'] );
	}

	private function create_product() {
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 10 );
		$product->save();
		return $product;
	}

	private function add_to_cart( $product_id ) {
		// Simulate API request to add product to cart via CoCart API.
		$request = new WP_REST_Request( 'POST', '/cocart/v2/cart/add-item' );
		$request->set_body_params( array(
			'id'       => $product_id,
			'quantity' => 1,
		) );
		$server = rest_get_server();
		return $server->dispatch( $request );
	}

	// Add more tests as needed!
}
