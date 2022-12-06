<?php

!is_admin() AND define('PLUGIN_CAN_EXECUTE', NULL);

class init
{
	protected static $instance = NULL;

	public function __construct()
	{
		add_filter('generate_rewrite_rules', array($this, 'taxonomy_slug_rewrite'), 9999);
		$this->options = get_option( 'ns_rewrite' );
	}
    public function getOption($name)
    {

        if (!isset($this->options[$name]))
        {
            $result = 0;
        }
        else
        {
            $result = $this->options[$name];
        }
        
        return $result;
    }
    public static function instance()
    {
        if(init::$instance === NULL)
            init::$instance = new init;

        return init::$instance;
    }

	public function taxonomy_slug_rewrite($wp_rewrite)
	{
		$redirect = wcRewrite::target();

		if($this->instance()->getOption('redirect_enabled'))
		{
			count($redirect['target']) and wcRewrite::redirect($redirect['target']); 

			$redirect['type'] == 'category' and $rewrite[$redirect['url']['path'] . '/?$'] = 'index.php?product_cat=' . str_replace('/page/' . end($redirect['url']['slicedPath']), '', $redirect['url']['path']) . $redirect['url']['pagination'];
			$redirect['type'] == 'product' and $rewrite[$redirect['url']['path'] . '/?$'] = 'index.php?product=' . end($redirect['url']['slicedPath']);
			$redirect['type'] != 'content' and $wp_rewrite->rules = $rewrite + $wp_rewrite->rules;
		}

		if($this->instance()->getOption('rewrite_enabled'))
		{
			$rewrite = wcRewrite::permalinks();
			$wp_rewrite->extra_permastructs['product']['struct'] = $rewrite['product']. '/%product%';
			$wp_rewrite->extra_permastructs['product_cat']['struct'] = $rewrite['category']. '/%product_cat%';
		}
    	return $wp_rewrite;
	}
}
add_action('init', function()
{
    flush_rewrite_rules();
});