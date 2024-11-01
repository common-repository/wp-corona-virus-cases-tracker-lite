<?php
/**
 * Plugin Name:WP Corona Virus Cases Tracker Lite
 * Description:Use this shortcode [wp-cvct] and display Novel Coronavirus(COVID-19) outbreak live Updates in your Page,post or widget section 
 * Author:Rajthemes
 * Author URI:https://rajthemes.com/
 * Plugin URI:
 * Version:1.0
 * License: GPL2
 * Text Domain:wp_cvct
 * Domain Path: languages
 *
 *@package WP Corona Virus Cases Tracker*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'WP_CVCT_VERSION' ) ) {
	return;
}
/*
Defined constent for later use
*/
define( 'WP_CVCT_VERSION', '1.0' );
define( 'WP_CVCT_Cache_Timing', HOUR_IN_SECONDS );
define( 'WP_CVCT_FILE', __FILE__ );
define( 'WP_CVCT_DIR', plugin_dir_path( WP_CVCT_FILE ) );
define( 'WP_CVCT_URL', plugin_dir_url( WP_CVCT_FILE ) );

/**
 * Class Corona Virus Cases Tracker
 */
final class WP_Corona_Virus_Cases_Tracker_lite {

	/**
	 * Plugin instance.
	 *
	 * @var WP_Corona_Virus_Cases_Tracker_lite
	 * @access private
	 */
	private static $instance = null;

	/**
	 * Get plugin instance.
	 *
	 * @return Corona_Virus_Cases_Tracker
	 * @static
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 * @access private
	 */
	private function __construct() {

		// register activation/ deactivation hooks
		register_activation_hook( WP_CVCT_FILE, array( $this , 'wp_cvct_activate' ) );
		register_deactivation_hook( WP_CVCT_FILE, array( $this , 'wp_cvct_deactivate' ) );

		// load text domain for translation
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
    
    //main plugin shortcode for list widget
    add_shortcode( 'wp-cvct', array($this, 'wp_cvct_shortcode' ));
    add_shortcode( 'wp-cvct-tbl',array($this,'wp_cvct_tbl_shortcode'));
	}

/*
|--------------------------------------------------------------------------
| Crypto Widget Main Shortcode
|--------------------------------------------------------------------------
*/ 
public function  wp_cvct_shortcode( $atts, $content = null ) {
    $atts = shortcode_atts( array(
        'title'           => 'Global Stats',
        'country-code'    => 'all',
        'label-total'     => 'Total Cases',
        'label-deaths'    => 'Deaths',
        'label-recovered' => 'Recovered',
        'bg-color'        => '#ddd',
        'font-color'      => '#000',
    ), $atts, 'wp_cvct' );

    $countryCode =!empty($atts['country-code'])?$atts['country-code']:"all";
    $stats='';
    $output='';
    $tp_html='';
 	  $style='layout-1';
    $total_cases='';
    $total_recovered='';
    $total_deaths='';
    $title=$atts['title'];
    $label_total=$atts['label-total'];
    $label_deaths=$atts['label-deaths'];
    $label_recovered=$atts['label-recovered'];
    $style=!empty($atts['style'])?$atts['style']:"style-1";
    $bgColors=!empty($atts['bg-color'])?$atts['bg-color']:"#DDDDDD";
    $fontColors=!empty($atts['font-color'])?$atts['font-color']:"#000";
    $custom_style='';
    $custom_style .='background-color:'.$bgColors.';';
    $custom_style .='color:'.$fontColors.';';
    $stats_data='';
  
    if($countryCode=="all"){
        $stats_data=$this->wp_cvct_g_stats_data();
    }else{
        $stats_data= $this->wp_cvct_c_stats_data($countryCode);
        if(isset($stats_data['country'])){
            $title=$stats_data['country'].' '.$title;
        }
    }
  
   
   if(is_array($stats_data)&& count($stats_data)>0){
       $total=$stats_data['total_cases'];
       $recovered=$stats_data['total_recovered'];
       $deaths=$stats_data['total_deaths'];
        $total_cases=!empty($total)? number_format($total):"0";
        $total_recovered=!empty($recovered)?number_format($recovered):"0";
        $total_deaths=!empty($deaths)?number_format($deaths):"0";
   }
        $tp_html.='
        <div id="coronatracker-card" class="wp-cvct-style1" style="'.esc_attr($custom_style).'">
            <h2 style="width:85%;'.esc_attr($custom_style).'">'.esc_html($title).'</h2>
            <div class="wp-cvct-number">
                <span>'.esc_html($total_cases).'</span>
                <span>'.esc_html($label_total).'</span>
            </div>
            <div class="wp-cvct-number">
                <span>'.esc_html($total_deaths).'</span>
                <span>'.esc_html($label_deaths).'</span>
            </div>
            <div class="wp-cvct-number">
                <span>'.esc_html($total_recovered).'</span>
                <span>'.esc_html($label_recovered).'</span>
            </div>
        </div>';
    $css="<style>". esc_html($this->wp_cvct_load_styles($style))."</style>";
    $output.='<div class="wp-cvct-wrapper">'.$tp_html.'</div>';
    $wp_cvctv='<!-- Corona Virus Cases Tracker - Version:- '.WP_CVCT_VERSION.' By Cool Plugins (CoolPlugins.net) -->';	
    return  $wp_cvctv.$output.$css;	
}


/*
|--------------------------------------------------------------------------
| fetch global stats
|--------------------------------------------------------------------------
*/  
public function wp_cvct_g_stats_data(){
    $cache_name='wp_cvct_gs';
     $cache=get_transient($cache_name);
     $cache=false;
    $gstats_data='';
    $save_arr=array();
if($cache==false){
         $api_url = 'https://corona.lmao.ninja/all';
         $request = wp_remote_get($api_url, array('timeout' => 120));
         if (is_wp_error($request)) {
             return false; // Bail early
         }
         $body = wp_remote_retrieve_body($request);
         $gt_data = json_decode($body,true);
         if(is_array($gt_data ) && isset($gt_data['cases'])){
            $save_arr['total_cases']=$gt_data['cases'];
            $save_arr['total_recovered']=$gt_data['recovered'];
            $save_arr['total_deaths']=$gt_data['deaths'];
            set_transient($cache_name,
            $save_arr,WP_CVCT_Cache_Timing
             ); 
            update_option("wp_cvct_gs_updated",date('Y-m-d h:i:s') );   
            $gstats_data=$save_arr;
                 return $gstats_data;
         }else{
         	return false;
         }
     }else{
     return $gstats_data=get_transient($cache_name);
     }
}


/*
|--------------------------------------------------------------------------
| fetch country stats
|--------------------------------------------------------------------------
*/  
public function wp_cvct_c_stats_data($country_code){
    $cache_name='wp_cvct_cs_'.$country_code;
    $cache=get_transient($cache_name);
    $cstats_data='';
    $save_arr=[];
   if($cache==false){
         $api_url = 'https://corona.lmao.ninja/countries/'.$country_code;
         $request = wp_remote_get($api_url, array('timeout' => 120));
         if (is_wp_error($request)) {
             return false; // Bail early
         }
         $body = wp_remote_retrieve_body($request);
         $cs_data = json_decode($body);
         if(isset($cs_data)&& !empty($cs_data)){
                $save_arr['total_cases']=$cs_data->cases;
               $save_arr['total_recovered']=$cs_data->recovered;
               $save_arr['total_deaths']=$cs_data->deaths;
               $save_arr['country']=$cs_data->country;
           set_transient($cache_name,
           $save_arr,WP_CVCT_Cache_Timing);
            set_transient('api-source',
            'corona.lmao.ninja', WP_CVCT_Cache_Timing);
             update_option("wp_cvct_cs_updated",date('Y-m-d h:i:s') );   
             $cstats_data= $save_arr;
                 return $cstats_data;
         }else{
             return false;
         }
     }else{
       return $cstats_data=get_transient($cache_name);
     }
   }
   

/**
 * Table shortcode
 */
public function wp_cvct_tbl_shortcode($atts, $content = null ){
    $atts = shortcode_atts( array(
        'id'  => '',
        'layout'=>'layout-1',
        'show' =>"10" ,
        'label-confirmed'=>"Confirmed",
        'label-deaths'=>"Death",
         'label-recovered'=>"Recovered",
         'label-active'=>'Active',
         'label-country'=>'Country',
        'bg-color'=>'#222222',
        'font-color'=>'#f9f9f9'
    ), $atts, 'wp_cvct' );
    $style = !empty($atts['layout'])?$atts['layout']:'layout-1';
    $country = !empty($atts['label-country'])?$atts['label-country']:'Country';
    $confirmed = !empty($atts['label-confirmed'])?$atts['label-confirmed']:'Confirmed';
    $deaths = !empty($atts['label-deaths'])?$atts['label-deaths']:'Death';
    $recoverd = !empty($atts['label-recovered'])?$atts['label-recovered']:'Recovered';
    $active = !empty($atts['label-active'])?$atts['label-active']:'Active';
    $bgColors=!empty($atts['bg-color'])?$atts['bg-color']:"#222222";
    $fontColors=!empty($atts['font-color'])?$atts['font-color']:"#f9f9f9";
    $show_entry = !empty($atts['show'])?$atts['show']:'10';
    $wp_cvct_html = '';
    $stack_arr = array();
    $results = array();
    $count = 0;
    $wp_cvct_get_data = $this->wp_cvct_get_all_country_data();
if(is_array($wp_cvct_get_data)&& count($wp_cvct_get_data)>0){
        $wp_cvct_html.= '<table id="wp_cvct_table_layout" class="table-layout-1">
        <thead><tr>
            <th>'.__($country,'wp_cvct').'</th>
            <th>'.__($confirmed,'wp_cvct').'</th>
            <th>'.__($recoverd,'wp_cvct').'</th>
            <th>'.__($deaths,'wp_cvct').'</th>
            </tr> </thead><tbody>';
    foreach($wp_cvct_get_data as $wp_cvct_stats_data){
            $total = $wp_cvct_stats_data['cases'];
            $country_name = isset($wp_cvct_stats_data['country'])?$wp_cvct_stats_data['country']:'';
            $confirmed = $wp_cvct_stats_data['confirmed'];
            $recoverd = $wp_cvct_stats_data['recoverd'];
            $death = $wp_cvct_stats_data['deaths'];
            $active = $wp_cvct_stats_data['active'];
        
            $total_cases = !empty($total)?number_format($total):'0';
            $confirmed_cases = !empty($confirmed)?number_format($confirmed):'0';
            $recoverd_cases = !empty($recoverd)?number_format($recoverd):'0';
            $death_cases = !empty($death)?number_format($death):'0';
            $total_count =  $count++;
            if ($total_count == $show_entry) break;
            $title=$country_name;
            $i=1;
            $wp_cvct_html.= '<tr class="wp-cvct-style1-stats">';
            $wp_cvct_html.= '<td class="wp-cvct-country-title">'.$country_name.'</td>';
            $wp_cvct_html.= '<td class="wp-cvct-confirm-case">'.$confirmed_cases.'</td>
            <td class="wp-cvct-recoverd-case">'.$recoverd_cases.'</td>
            <td class="wp-cvct-death-case">'.$death_cases.'</td>
            </tr>';
    }
    $wp_cvct_html.=  '</tbody>
    </table>';
  }else{
    $wp_cvct_html.='<div>'.__('Something wrong With API', 'wp_cvct' ).'</div>'; 
  }

  $css='<style>
  table#wp_cvct_table_layout tr th, table#wp_cvct_table_id tr th {background-color:'.$bgColors.';color:'.$fontColors.';}
table#wp_cvct_table_layout tr td, table#wp_cvct_table_id tr td {background-color:'.$fontColors.';color:'.$bgColors.';}
  '.$this->wp_cvct_load_table_styles().'</style>';
  $wp_cvctv='<!-- Corona Virus Cases Tracker - Version:- '.WP_CVCT_VERSION.' By Cool Plugins (CoolPlugins.net) -->';
    return $wp_cvctv. '<div  class="wp-cvct-wrapper">' . $wp_cvct_html . '</div>' .$css;
  
  }

/*
|--------------------------------------------------------------------------
| loading required assets according to the widget type
|--------------------------------------------------------------------------
*/  
    function wp_cvct_load_styles($style){
        $css='#coronatracker-card, #coronatracker-card * {
            box-sizing: border-box;
        }
        #coronatracker-card h2 {
            margin: 0 0 10px 0;
            padding: 0;
            font-size: 20px;
            font-weight: bold;
        }.wp-cvct-number {
            width: calc(33.33% - 3px);
            display: inline-block;
           
            padding: 8px 4px 15px;
            padding-top:25px;
            text-align: center;
        }
        .wp-cvct-number span {
            width: 100%;
            display: inline-block;
            font-size: 14px;
        }
        .wp-cvct-number span:first-child {
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .wp-cvct-style1 {
            width: 100%;
            max-width: 420px;
            display: inline-block;
            background: #ddd url('.WP_CVCT_URL.'/assets/corona-virus.png);
            padding: 10px;
            border-radius: 8px;
			background-size: 75px;
			background-color:#ddd;
            background-position: right top;
            background-repeat: no-repeat;
            margin: 5px 0 10px;
        }';
     
        return $css;
    }

/*  
|--------------------------------------------------------------------------
| loading required assets according to the widget type
|--------------------------------------------------------------------------
*/  
    function wp_cvct_load_table_styles(){
      $css = '';
          $css=' table#wp_cvct_table_layout,
            table#wp_cvct_states_table_id,
            table#wp_cvct_table_id {
            table-layout: fixed;
            border-collapse: collapse;
            border-radius: 5px;
            overflow: hidden;
            }
            table#wp_cvct_states_table_id tr th,
            table#wp_cvct_states_table_id tr td,
            table#wp_cvct_table_layout tr th,
            table#wp_cvct_table_layout tr td,
            table#wp_cvct_table_id tr th,
            table#wp_cvct_table_id tr td {
            text-align: center;
            vertical-align: middle;
            font-size:14px;
            line-height:16px;
            text-transform:capitalize;
            border: 1px solid rgba(0, 0, 0, 0.15);
            width: 110px;
            padding: 12px 4px;
            }
            table#wp_cvct_table_layout tr th:first-child,
            table#wp_cvct_table_layout tr td:first-child,
            table#wp_cvct_table_layout tr th:first-child,
            table#wp_cvct_table_layout tr td:first-child {
            text-align: left;
            }
            table#wp_cvct_table_layout tr td img {
            margin: 0 4px 2px 0;
            padding: 0;
            vertical-align: middle;
            }
            div#wp_cvct_table_id_wrapper input,
            div#wp_cvct_table_id_wrapper select {
            display: inline-block !IMPORTANT;
            vertical-align: top;
            margin: 0 2px 20px !IMPORTANT;
            width: auto !IMPORTANT;
            min-width: 60px;
            } ';
      return $css;
    }

	/**
	 * Code you want to run when all other plugins loaded.
	 */
	public function load_textdomain() {
		load_plugin_textdomain('wp_cvct', false, basename(dirname(__FILE__)) . '/languages/' );
    }
    
    /*
|--------------------------------------------------------------------------
| fetches covid-19 all countries stats data
|--------------------------------------------------------------------------
*/ 
function wp_cvct_get_all_country_data(){
    $cache_name='wp_cvct_countries_data';
   // $cache=get_transient($cache_name);
   $cache=false;
    $country_stats_data = array();
    $data_arr = array();
      if($cache==false){
       $api_url = 'https://corona.lmao.ninja/countries?sort=cases';
       $api_req = wp_remote_get($api_url,array('timeout' => 120));
       if (is_wp_error($api_req)) {
        return false; // Bail early
    }
    $body = wp_remote_retrieve_body($api_req);
    $cs_data = json_decode($body);

     if(isset($cs_data)&& !empty($cs_data)){
    foreach($cs_data as  $all_country_data){
        $data_arr['country'] = $all_country_data->country;
        $data_arr['cases'] = $all_country_data->cases;
        $data_arr['active'] = $all_country_data->active;
        $data_arr['country'] =  $all_country_data->country;
        $data_arr['confirmed'] = $all_country_data->cases;
        $data_arr['recoverd'] = $all_country_data->recovered;
        $data_arr['deaths'] = $all_country_data->deaths;
       $country_stats_data[] = $data_arr;
      }
    set_transient($cache_name,
    $country_stats_data,
    WP_CVCT_Cache_Timing);
   return $country_stats_data;
  }
 else{
     return false;
 }
  }
  else{
    return $country_stats_data =get_transient($cache_name);
  }
}
	/**
	 * Run when activate plugin.
	 */
	public function wp_cvct_activate() {
		update_option("wp-cvct-type","FREE");
		update_option("wp_cvct_activation_time",date('Y-m-d h:i:s') );
		update_option("wp-cvct-alreadyRated","no");
	}
	public function wp_cvct_deactivate(){
		delete_transient('wp_cvct_gs');
	}
}

function WP_Corona_Virus_Cases_Tracker_lite() {
	return WP_Corona_Virus_Cases_Tracker_lite::get_instance();
}

WP_Corona_Virus_Cases_Tracker_lite();