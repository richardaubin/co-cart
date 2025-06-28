<?php
/**
 * Test CoCart Products Controller
 *
 * Tests for CoCart products API endpoints including product listing,
 * individual product retrieval, and product-related data with comprehensive
 * parameter testing.
 *
 * @package CoCart\Tests\Unit
 */

/**
 * Test CoCart Products Controller Class
 *
 * Tests the products API endpoints which handle product operations like
 * listing products, retrieving individual products, and accessing
 * product categories and attributes with extensive parameter coverage.
 *
 * @package CoCart\Tests\Unit
 */
class Test_CoCart_Products_Controller extends CoCart_API_V2_Test_Case {

	/**
	 * Test getting products list.
	 *
	 * Verifies that the products endpoint returns a list of all available
	 * products with the correct response structure.
	 *
	 * @return void
	 */
	public function test_get_products() {
		// Create test products.
		$product1 = $this->create_product( array( 'name' => 'Product 1' ) );
		$product2 = $this->create_product( array( 'name' => 'Product 2' ) );

		$response = $this->get_products();

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'products', $data );
		$this->assertGreaterThanOrEqual( 2, count( $data['products'] ) );
	}

	/**
	 * Test getting a single product.
	 *
	 * Verifies that individual products can be retrieved by ID and that
	 * the response contains the correct product data including name and price.
	 *
	 * @return void
	 */
	public function test_get_single_product() {
		$product = $this->create_product( array(
			'name'          => 'Test Product',
			'regular_price' => '25.00',
		) );

		$response = $this->get_product( $product->get_id() );

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'id', $data );
		$this->assertEquals( $product->get_id(), $data['id'] );
		$this->assertEquals( 'Test Product', $data['name'] );
		$this->assertEquals( '25.00', $data['price'] );
	}

	/**
	 * Test getting non-existent product.
	 *
	 * Verifies that requesting a non-existent product returns a 404
	 * Not Found status.
	 *
	 * @return void
	 */
	public function test_get_nonexistent_product() {
		$response = $this->get_product( 99999 );

		$this->assert_rest_response_status( 404, $response );
	}

	/**
	 * Test getting products with pagination.
	 *
	 * Verifies that the products endpoint supports pagination parameters
	 * and returns the correct number of products per page.
	 *
	 * @return void
	 */
	public function test_get_products_with_pagination() {
		// Create multiple products.
		for ( $i = 1; $i <= 5; $i++ ) {
			$this->create_product( array( 'name' => "Product {$i}" ) );
		}

		$response = $this->get_products( array(
			'per_page' => 2,
			'page'     => 1,
		) );

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'products', $data );
		$this->assertCount( 2, $data['products'] );
	}

	/**
	 * Test getting products with search.
	 *
	 * Verifies that the products endpoint supports search functionality
	 * and returns only products matching the search term.
	 *
	 * @return void
	 */
	public function test_get_products_with_search() {
		$product1 = $this->create_product( array( 'name' => 'Red Shirt' ) );
		$product2 = $this->create_product( array( 'name' => 'Blue Pants' ) );

		$response = $this->get_products( array(
			'search' => 'Red',
		) );

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'products', $data );
		$this->assertCount( 1, $data['products'] );
		$this->assertEquals( 'Red Shirt', $data['products'][0]['name'] );
	}

	/**
	 * Test getting products with category filter.
	 *
	 * Verifies that the products endpoint supports category filtering
	 * and returns only products belonging to the specified category.
	 *
	 * @return void
	 */
	public function test_get_products_with_category() {
		// Create category.
		$category = wp_insert_term( 'Test Category', 'product_cat' );

		// Create product with category.
		$product = $this->create_product( array( 'name' => 'Categorized Product' ) );
		wp_set_object_terms( $product->get_id(), $category['term_id'], 'product_cat' );

		$response = $this->get_products( array(
			'category' => $category['term_id'],
		) );

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'products', $data );
		$this->assertCount( 1, $data['products'] );
		$this->assertEquals( 'Categorized Product', $data['products'][0]['name'] );
	}

	/**
	 * Test getting products with status filter.
	 *
	 * Verifies that the products endpoint supports status filtering
	 * and returns only products with the specified status.
	 *
	 * @return void
	 */
	public function test_get_products_with_status() {
		// Create published product.
		$published_product = $this->create_product( array( 'name' => 'Published Product' ) );
		
		// Create draft product.
		$draft_product = $this->create_product( array( 'name' => 'Draft Product' ) );
		wp_update_post( array(
			'ID'          => $draft_product->get_id(),
			'post_status' => 'draft',
		) );

		$response = $this->get_products( array(
			'status' => 'publish',
		) );

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'products', $data );
		
		// Check that only published products are returned.
		foreach ( $data['products'] as $product ) {
			$this->assertEquals( 'publish', $product['status'] );
		}
	}

	/**
	 * Test getting products with price range filter.
	 *
	 * Verifies that the products endpoint supports price range filtering
	 * and returns only products within the specified price range.
	 *
	 * @return void
	 */
	public function test_get_products_with_price_range() {
		$product1 = $this->create_product( array(
			'name'          => 'Cheap Product',
			'regular_price' => '10.00',
		) );
		$product2 = $this->create_product( array(
			'name'          => 'Expensive Product',
			'regular_price' => '50.00',
		) );

		$response = $this->get_products( array(
			'min_price' => '5.00',
			'max_price' => '20.00',
		) );

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'products', $data );
		
		// Check that only products within price range are returned.
		foreach ( $data['products'] as $product ) {
			$price = floatval( $product['price'] );
			$this->assertGreaterThanOrEqual( 5.00, $price );
			$this->assertLessThanOrEqual( 20.00, $price );
		}
	}

	/**
	 * Test getting products with ordering.
	 *
	 * Verifies that the products endpoint supports different ordering
	 * options and returns products in the correct order.
	 *
	 * @return void
	 */
	public function test_get_products_with_ordering() {
		$product1 = $this->create_product( array(
			'name'          => 'A Product',
			'regular_price' => '10.00',
		) );
		$product2 = $this->create_product( array(
			'name'          => 'Z Product',
			'regular_price' => '50.00',
		) );

		// Test ordering by name ascending.
		$response = $this->get_products( array(
			'orderby' => 'title',
			'order'   => 'asc',
		) );

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'products', $data );
		
		// Check that products are ordered by name ascending.
		$product_names = wp_list_pluck( $data['products'], 'name' );
		$sorted_names = $product_names;
		sort( $sorted_names );
		$this->assertEquals( $sorted_names, $product_names );
	}

	/**
	 * Test getting products with featured filter.
	 *
	 * Verifies that the products endpoint supports featured product filtering
	 * and returns only featured products.
	 *
	 * @return void
	 */
	public function test_get_products_with_featured_filter() {
		// Create regular product.
		$regular_product = $this->create_product( array( 'name' => 'Regular Product' ) );
		
		// Create featured product.
		$featured_product = $this->create_product( array( 'name' => 'Featured Product' ) );
		update_post_meta( $featured_product->get_id(), '_featured', 'yes' );

		$response = $this->get_products( array(
			'featured' => true,
		) );

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'products', $data );
		
		// Check that only featured products are returned.
		foreach ( $data['products'] as $product ) {
			$this->assertTrue( $product['featured'] );
		}
	}

	/**
	 * Test getting products with on sale filter.
	 *
	 * Verifies that the products endpoint supports on-sale product filtering
	 * and returns only products that are currently on sale.
	 *
	 * @return void
	 */
	public function test_get_products_with_on_sale_filter() {
		// Create regular product.
		$regular_product = $this->create_product( array(
			'name'          => 'Regular Product',
			'regular_price' => '20.00',
		) );
		
		// Create on-sale product.
		$sale_product = $this->create_product( array(
			'name'          => 'Sale Product',
			'regular_price' => '20.00',
			'sale_price'    => '15.00',
		) );

		$response = $this->get_products( array(
			'on_sale' => true,
		) );

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'products', $data );
		
		// Check that only on-sale products are returned.
		foreach ( $data['products'] as $product ) {
			$this->assertTrue( $product['on_sale'] );
		}
	}

	/**
	 * Test getting products with stock status filter.
	 *
	 * Verifies that the products endpoint supports stock status filtering
	 * and returns only products with the specified stock status.
	 *
	 * @return void
	 */
	public function test_get_products_with_stock_status() {
		// Create in-stock product.
		$in_stock_product = $this->create_product( array(
			'name'        => 'In Stock Product',
			'stock_status' => 'instock',
		) );
		
		// Create out-of-stock product.
		$out_of_stock_product = $this->create_product( array(
			'name'        => 'Out of Stock Product',
			'stock_status' => 'outofstock',
		) );

		$response = $this->get_products( array(
			'stock_status' => 'instock',
		) );

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'products', $data );
		
		// Check that only in-stock products are returned.
		foreach ( $data['products'] as $product ) {
			$this->assertEquals( 'instock', $product['stock_status'] );
		}
	}

	/**
	 * Test getting products with tag filter.
	 *
	 * Verifies that the products endpoint supports tag filtering
	 * and returns only products with the specified tag.
	 *
	 * @return void
	 */
	public function test_get_products_with_tag_filter() {
		// Create tag.
		$tag = wp_insert_term( 'Test Tag', 'product_tag' );

		// Create product with tag.
		$product = $this->create_product( array( 'name' => 'Tagged Product' ) );
		wp_set_object_terms( $product->get_id(), $tag['term_id'], 'product_tag' );

		$response = $this->get_products( array(
			'tag' => $tag['term_id'],
		) );

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'products', $data );
		$this->assertCount( 1, $data['products'] );
		$this->assertEquals( 'Tagged Product', $data['products'][0]['name'] );
	}

	/**
	 * Test getting products with attribute filter.
	 *
	 * Verifies that the products endpoint supports attribute filtering
	 * and returns only products with the specified attribute value.
	 *
	 * @return void
	 */
	public function test_get_products_with_attribute_filter() {
		// Create attribute.
		$attribute = wc_create_attribute( array(
			'name'         => 'Color',
			'slug'         => 'color',
			'type'         => 'select',
			'order_by'     => 'menu_order',
			'has_archives' => false,
		) );

		// Create attribute term.
		$term = wp_insert_term( 'Red', 'pa_color' );

		// Create product with attribute.
		$product = $this->create_product( array( 'name' => 'Red Product' ) );
		wp_set_object_terms( $product->get_id(), $term['term_id'], 'pa_color' );

		$response = $this->get_products( array(
			'attribute' => 'color',
			'attribute_term' => $term['term_id'],
		) );

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'products', $data );
		$this->assertCount( 1, $data['products'] );
		$this->assertEquals( 'Red Product', $data['products'][0]['name'] );
	}

	/**
	 * Test getting products with multiple filters combined.
	 *
	 * Verifies that the products endpoint supports combining multiple
	 * filters and returns products matching all criteria.
	 *
	 * @return void
	 */
	public function test_get_products_with_multiple_filters() {
		// Create category.
		$category = wp_insert_term( 'Test Category', 'product_cat' );

		// Create product matching multiple criteria.
		$product = $this->create_product( array(
			'name'          => 'Multi Filter Product',
			'regular_price' => '15.00',
		) );
		wp_set_object_terms( $product->get_id(), $category['term_id'], 'product_cat' );

		$response = $this->get_products( array(
			'category'  => $category['term_id'],
			'min_price' => '10.00',
			'max_price' => '20.00',
			'search'    => 'Multi Filter',
		) );

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'products', $data );
		$this->assertCount( 1, $data['products'] );
		$this->assertEquals( 'Multi Filter Product', $data['products'][0]['name'] );
	}

	/**
	 * Test getting product categories.
	 *
	 * Verifies that the product categories endpoint returns a list
	 * of all available product categories.
	 *
	 * @return void
	 */
	public function test_get_product_categories() {
		// Create categories.
		wp_insert_term( 'Category 1', 'product_cat' );
		wp_insert_term( 'Category 2', 'product_cat' );

		$response = $this->get_product_categories();

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'categories', $data );
		$this->assertGreaterThanOrEqual( 2, count( $data['categories'] ) );
	}

	/**
	 * Test getting product attributes.
	 *
	 * Verifies that the product attributes endpoint returns a list
	 * of all available product attributes.
	 *
	 * @return void
	 */
	public function test_get_product_attributes() {
		// Create attribute.
		$attribute = wc_create_attribute( array(
			'name'         => 'Color',
			'slug'         => 'color',
			'type'         => 'select',
			'order_by'     => 'menu_order',
			'has_archives' => false,
		) );

		$response = $this->get_product_attributes();

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'attributes', $data );
		$this->assertGreaterThanOrEqual( 1, count( $data['attributes'] ) );
	}

	/**
	 * Test getting product variations.
	 *
	 * Verifies that the product variations endpoint returns a list
	 * of variations for a variable product.
	 *
	 * @return void
	 */
	public function test_get_product_variations() {
		// Create variable product.
		$product = new WC_Product_Variable();
		$product->set_name( 'Variable Product' );
		$product->save();

		// Create variation.
		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $product->get_id() );
		$variation->set_regular_price( '15.00' );
		$variation->save();

		$response = $this->get_product_variations( $product->get_id() );

		$this->assert_rest_response_status( 200, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'variations', $data );
		$this->assertCount( 1, $data['variations'] );
	}

	/**
	 * Test getting products with context parameter.
	 *
	 * Verifies that the products endpoint supports different context
	 * parameters and returns appropriate data for each context.
	 *
	 * @return void
	 */
	public function test_get_products_with_context() {
		$product = $this->create_product( array( 'name' => 'Context Test Product' ) );

		// Test with view context (default).
		$response = $this->get_products( array( 'context' => 'view' ) );
		$this->assert_rest_response_status( 200, $response );

		// Test with embed context.
		$response = $this->get_products( array( 'context' => 'embed' ) );
		$this->assert_rest_response_status( 200, $response );

		// Test with edit context (should fail for non-authenticated users).
		$response = $this->get_products( array( 'context' => 'edit' ) );
		$this->assert_rest_response_status( 401, $response );
	}

	/**
	 * Test getting products with invalid parameters.
	 *
	 * Verifies that the products endpoint properly handles invalid
	 * parameters and returns appropriate error responses.
	 *
	 * @return void
	 */
	public function test_get_products_with_invalid_parameters() {
		// Test with invalid per_page value.
		$response = $this->get_products( array( 'per_page' => -1 ) );
		$this->assert_rest_response_status( 400, $response );

		// Test with invalid page value.
		$response = $this->get_products( array( 'page' => 0 ) );
		$this->assert_rest_response_status( 400, $response );

		// Test with invalid order value.
		$response = $this->get_products( array( 'order' => 'invalid' ) );
		$this->assert_rest_response_status( 400, $response );
	}
} 