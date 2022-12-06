<?php

class wcRewrite
{

	public static function url()
	{
		$subfolder = parse_url(get_site_url(),PHP_URL_PATH);

		$host = str_replace($subfolder, NULL, get_site_url());
		$fullPath = strtok($_SERVER["REQUEST_URI"], '?');
		count($subfolder) and $path = untrailingslashit(str_replace(trailingslashit($subfolder), NULL, $fullPath))
		or $path = untrailingslashit(ltrim($fullPath, '/'));
		$slicedPath = explode('/', $path);
		$pagination = NULL;
		count($slicedPath) > 2 and $slicedPath[sizeof($slicedPath)-2] == 'page' and $pagination = self::setPagination($path, $slicedPath);
		$pagination = strstr($pagination, '&');

		$url = array(			'subfolder' => $subfolder,
			'host' => $host,
			'fullPath' => $fullPath,
			'path' => $path,
			'main' => $host . $fullPath,
			'slicedPath' => $slicedPath,
			'pagination' => $pagination
		);

		return $url;
	}

	public static function slug()
	{
		$wcPermalinks = get_option( 'woocommerce_permalinks' );

		strlen($wcPermalinks['product_base']) and $productSlug = preg_replace('/%[^>]*%/', '', $wcPermalinks['product_base']) or $productSlug = '/' . _x('product', 'slug', 'woocommerce');
		strlen($wcPermalinks['category_base']) and $categorySlug = preg_replace('/%[^>]*%/', '', $wcPermalinks['category_base']) or $categorySlug = '/' . _x('product-category', 'slug', 'woocommerce');
	
		return array(
			'product' => trailingslashit($productSlug),
			'category' => trailingslashit($categorySlug)
			);
		
	}

	public static function target()
	{
		$url = self::url();
		$slug = self::slug();
		$type = self::type($url);
		$target = array();

		strpos($url['main'], $url['subfolder'] . $slug['category']) and $target['type'] = 'category' and $target['url'] = str_replace($url['subfolder'] . $slug['category'], $url['subfolder'] . '/', $url['main']) and $target['rewrite'] = $url['path']
		or strpos($url['main'], $url['subfolder'] . $slug['product']) and $target['type'] = 'product' and $target['url'] = str_replace($url['subfolder'] . $slug['product'], $url['subfolder'] . '/', $url['main']) and $target['rewrite'] = $url['path'];
		
		return array('target' => $target, 'url' => $url, 'type' => $type);
	}
 
	public static function redirect($target)
	{
		wp_redirect($target['url'], 301);
		exit;
	}

	public static function type($url)
	{
		$path = explode('/', $url['path']);

        $taxonomies = array( 
			'product_cat'
        );
        $parent = '';
        $categories = NULL;
        foreach ($path as $slug)
        {
			$arguments = array(
	        	'hide_empty'        => false, 
	            'slug'              => $slug,
	            'parent'			=> $parent
	        );

			$category = get_terms($taxonomies, $arguments);
			count($category) and $parent = $category[0]->term_id and $categories[] = $category;			
		}
		$less = 0;
		count($path) > 2 and $path[sizeof($path)-2] == 'page' and $less = 2;
		count(wcRewrite::getPostIdByName($slug)) and $type = 'product' or
		count($categories) == count($path) - $less and $type = 'category'		
		or $type = 'content';

		return $type;
	}

	public static function getPostIdByName($name)
	{
		global $wpdb;
		$post = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_name = '$name' AND post_type = 'product'");

		return $post;
	}

	public static function permalinks()
	{
		$permalinks = get_option('woocommerce_permalinks');
		
		$product = str_replace('/'._x('product','slug','woocommerce'), NULL, $permalinks['product_base']);
		$category = str_replace('/'._x('product-category','slug','woocommerce').'/', NULL, $permalinks['category_base']);

		return array('product' => $product, 'category' => $category);
	}

	public static function setPagination($path, $slicedPath)
	{
		$page = end($slicedPath);
	    $path = explode('/', $path);
	    array_pop($path);
	    array_pop($path);
	    $path = implode('/', $path);

	    return $path . '&paged=' . $page;
	}
}