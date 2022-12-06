<?php

/*-----------------------------------------------------------------------------------------
  # HOW TO USE
  -----------------------------------------------------------------------------------------
  1]  DEFINE STRUCTURE?

  - Define below structure in module which you want.

  e.g.  array(
              "type" => "ult_img_single",
              "heading" => "Upload Image",
              "param_name" => "icon_image",
              "description" => __("description for image single.", "ultimate_vc"),
        ),
  -----------------------------------------------------------------------------------------
  2]  USE FILTER?

  - Return url, array or json.

  e.g.  apply_filters('ult_get_img_single', $PARAM_NAME, 'url', 'size');    // {size} [optional] - thumbnail, full, medium etc. - default: full

        apply_filters('ult_get_img_single', $PARAM_NAME, 'array');
        apply_filters('ult_get_img_single', $PARAM_NAME, 'json');

  -----------------------------------------------------------------------------------------
  3]  OUTPUT

  - Output of two image uploader fields.
    
    http://i.imgur.com/csfJvKV.png
-----------------------------------------------------------------------------------------*/

if(!class_exists('Ult_Image_Single'))
{
  class Ult_Image_Single
  {
    function __construct()
    {
      add_action( 'admin_enqueue_scripts', array( $this, 'image_single_scripts' ) );
      add_filter('ult_get_img_single', array( $this, 'ult_img_single_init'),10,3);

      if(function_exists('add_shortcode_param'))
      {
        add_shortcode_param('ult_img_single', array($this, 'ult_img_single_callback'), plugins_url('../admin/vc_extend/js/ultimate-image_single.js',__FILE__));
      }
      add_action('wp_ajax_ult_get_attachment_url', array($this, 'get_attachment_url_init') );
    }
    function get_attachment_url_init() {     
      $id = $_POST['attach_id'];
      $thumb = wp_get_attachment_image_src( $id, 'thumbnail' );
      //echo json_encode( $thumb );
      echo $thumb[0];

      die();
    }

    function ult_img_single_callback($settings, $value)
    {
        $dependency = vc_generate_dependencies_attributes($settings);
        
        $uid = 'ult-image_single-'. rand(1000, 9999);

        $html  = '<div class="ult-image_single" id="'.$uid.'">';

        $html .= '<div class="ult_selected_image">';
        $html .= '  <ul class="ult_selected_image_list">';
        $html .= '    <li class="">';
        $html .= '      <div class="inner" style="width: 75px; height: 75px; overflow: hidden;text-align: center;">';
        $html .= '        <div class="spinner ult_img_single_spinner"></div>';
        $html .= '        <img src="">';
        $html .= '      </div>';
        $html .= '      <a title="Remove Footer Image" href="javascript:;" id="remove-thumbnail" class="icon-remove"></a>';
        $html .= '    </li>';
        $html .= '  </ul>';
        $html .= '</div>';
        $html .= '<a class="ult_add_image" href="#" title="Add image">Add image</a>';
          
        $html .= '  <input type="hidden" name="'.$settings['param_name'].'" class="wpb_vc_param_value ult-image_single-value '.$settings['param_name'].' '.$settings['type'].'_field" value="'.$value.'" '.$dependency.' />';
        $html .= '</div>';
      return $html;
    }
    
    function image_single_scripts() {
      wp_enqueue_media();
      wp_enqueue_style( 'ultimate_image_single_css', plugins_url('../admin/vc_extend/css/ultimate_image_single.css', __FILE__ ));
    }

    /**   Filter for image uploader
     * 
     * @args    null|null
     *     or   null|URL
     *     or   ID|URL
     * @return  array|json
     *-------------------------------------------------*/
    function ult_img_single_init( $content = null, $data = '', $size = 'full' ){
      
      $final = ''; 

      if($content!='' && $content!='null|null') {

        //  Create an array
        $mainStr = explode('|', $content);
        $string = '';
        $mainArr = array();
        if( !empty($mainStr) && is_array($mainStr) ) {
          foreach ($mainStr as $key => $value) {
            if( !empty($value) ) {
              array_push($mainArr, $value);
            }
          }
        }

        if($data!='') {
          switch ($data) {
            case 'url':     // first  - priority for ID

                            if( !empty($mainArr[0]) && $mainArr[0] != 'null' ) {

                              $hasImage = wp_get_attachment_image_src( $mainArr[0], $size ); // returns an array
                              if( $hasImage ) {
                                $final = $hasImage[0];
                              } else {
                                //  second - priority for URL - if image not found in WP
                                if( !empty($mainArr[1]) && $mainArr[1] != 'null' ) {
                                  $final = $mainArr[1];
                                }
                              }
                            } else {
                              //  second - priority for URL - for {image from url}
                              if( !empty($mainArr[1]) && $mainArr[1] != 'null' ) {
                                $final = $mainArr[1];
                              }
                            }
              break;
            case 'json':
                          $final = json_encode($mainArr);
              break;
            case 'array':
            default:
                          $final = $mainArr;
              break;
          }
        }
      } 

      return $final;
    }
  }
}
if(class_exists('Ult_Image_Single'))
{
  $Ult_Image_Single = new Ult_Image_Single();
}