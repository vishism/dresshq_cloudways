<?php
/**
 * Cron
 v1.1.6
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

//*****************************
//v1.1.6.1 Refactored
//*****************************

class VTPRD_Cron {

	public function __construct() {

    add_filter( 'cron_schedules', array( &$this, 'vtprd_add_schedules'   ) ); 
    add_filter( 'cron_schedules', array( &$this, 'vtprd_add_schedules2'  ) ); //v2.0.2.0  
     
    // https://wordpress.stackexchange.com/questions/179694/wp-schedule-event-every-day-at-specific-time
    
    //AS the CLASS is instantiated in the INIT hook, you can't use that hook or an earlier one here.
    // wp_loaded follows INIT but is still early enough to be useful!
		
    //v2.0.0.2  added the IF - once this is scheduled, no reason to do this again.
    if ( ! wp_next_scheduled( 'vtprd_twice_daily_scheduled_events' ) ) {
      add_action( 'wp_loaded', array( &$this, 'vtprd_schedule_events' ) );
    }
    
    //**************
    //v2.0.2.0 begin
    if ( ! wp_next_scheduled( 'vtprd_once_daily_scheduled_events' ) ) {
      add_action( 'wp_loaded', array( &$this, 'vtprd_schedule_events2' ) );
    }
    //v2.0.2.0 end
    //**************
    
  //  add_action( 'init', array( &$this, 'vtprd_schedule_events' ) ); //does not work
  //  add_action( 'admin_init', array( &$this, 'vtprd_schedule_events' ) );  //WORKS, but only on the admin side
  //  add_action( 'wp', array( &$this, 'vtprd_schedule_events' ) ); //does not work
 
	}


	public function vtprd_add_schedules( $schedules = array() ) {
 //error_log( print_r(  'BEGIN vtprd_add_schedules' , true ) ); 	
  	// Adds to the existing schedules.
/* v2.0.0.2
		$schedules['vtprd_thrice_daily'] = array(
			'interval' => 28800,
			'display'  => __( 'Every Eight Hours', 'vtprd' )
		);
 */   
    //v1.1.6.1 added
		$schedules['vtprd_twice_daily'] = array(
			'interval' => 43200,
			'display'  => __( 'Every 12 Hours', 'vtprd' )
		);    
		return $schedules;
	}

    //**************
	//v2.0.2.0 new function 
    //**************
    public function vtprd_add_schedules2( $schedules = array() ) {
 //error_log( print_r(  'BEGIN vtprd_add_schedules2' , true ) ); 	
		$schedules['vtprd_once_daily'] = array(
			'interval' => 86400,
			'display'  => __( 'Every 24 Hours', 'vtprd' )
		);
		return $schedules;
	}


	public function vtprd_schedule_events() {
 //error_log( print_r(  'BEGIN vtprd_schedule_events' , true ) ); 
	//	$this->vtprd_thrice_daily(); v2.0.0.2
		$this->vtprd_twice_daily();
 
	}

    //**************
    //v2.0.2.0 new function 
    //**************
	public function vtprd_schedule_events2() {
 //error_log( print_r(  'BEGIN vtprd_schedule_events2' , true ) ); 
		$this->vtprd_once_daily();
 
	}    
    
 /* v2.0.0.2 removed
	private function vtprd_thrice_daily() {
 //error_log( print_r(  'BEGIN vtprd_thrice_daily' , true ) );  
		if ( ! wp_next_scheduled( 'vtprd_thrice_daily_scheduled_events' ) ) {
			wp_schedule_event( current_time( 'timestamp' ), 'vtprd_thrice_daily', 'vtprd_thrice_daily_scheduled_events' );
 //error_log( print_r(  'Scheduled vtprd_thrice_daily' , true ) );      
		}
	}
*/  
	private function vtprd_twice_daily() {
 //error_log( print_r(  'BEGIN vtprd_twice_daily' , true ) );
		//v1.1.6.1 added
    if ( ! wp_next_scheduled( 'vtprd_twice_daily_scheduled_events' ) ) {
			wp_schedule_event( current_time( 'timestamp' ), 'vtprd_twice_daily', 'vtprd_twice_daily_scheduled_events' );
 //error_log( print_r(  'Scheduled vtprd_twice_daily' , true ) );      
		}
	}  

    //**************
    //v2.0.2.0 new function
    //**************
	private function vtprd_once_daily() {
 //error_log( print_r(  'BEGIN vtprd_twice_daily' , true ) );
		//v1.1.6.1 added
    if ( ! wp_next_scheduled( 'vtprd_once_daily_scheduled_events' ) ) {
			wp_schedule_event( current_time( 'timestamp' ), 'vtprd_once_daily', 'vtprd_once_daily_scheduled_events' );
 //error_log( print_r(  'Scheduled vtprd_twice_daily' , true ) );      
		}
	}               

}
$vtprd_cron = new VTPRD_Cron;

//cron job run out of license-options.php
// add_action( 'vtprd_thrice_daily_scheduled_events', 'vtprd_maybe_recheck_license_activation' );
