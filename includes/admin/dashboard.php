<?php
/**
 *
 * @package     EDD\SalesMetrics\Dashboard
 * @since       0.0.1
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

// Sample output: Array ( [range] => last_week [year] => 2016 [year_end] => 2016 [m_start] => 6 [m_end] => 6 [day] => 20 [day_end] => 26 )
$dates = edd_sm_get_dates();


// Get date range. Default to this month.
// Sample output: Array ( [date] => Array ( [start] => 6/20/2016 [end] => 6/26/2016 ) )
$date_range = array();
$date_range_previous = array();

$date_range = edd_sm_get_date_range( $dates );
$date_range_previous = edd_sm_get_date_range_previous( $dates ); // previous dates range


// customer

$customer_date_range = $date_range;
$customer_date_range['number']=-1;  // max number of customers count returned

$customer_date_range_prev = $date_range_previous;
$customer_date_range_prev['number']=-1;

$customers  = EDD()->customers->get_customers( $customer_date_range );
$customers_prev = EDD()->customers->get_customers( $customer_date_range_prev );

// sort the customer (array of Objects by date);
usort($customers, function($a, $b) {
    return strtotime($a->date_created) - strtotime($b->date_created);
});

function edd_sm_get_counts_by_date( $EDD_object = array(), $date_range = 'this_week' ){

    $counts_by_date = array();

    $begin = new DateTime( $date_range['date']['start'] );
    $end = new DateTime( $date_range['date']['end'] );
    $end = $end->modify( '+1 day' ); 

    $interval = new DateInterval( 'P1D' );
    $daterange = new DatePeriod( $begin, $interval ,$end );

    // get count by purchased date
    foreach( $daterange as $date ){

        $date = $date->format( "Y-m-d" );
        $count = 0;

        foreach($EDD_object as $EDD_obj){
            
            $date_created = new DateTime( $EDD_obj->date_created );
            $date_created = $date_created->format( "Y-m-d" );

            if( $date_created == $date ){

                $count++;
            }
        } 

        // echo "$date - $count"."<br />";
        $counts_by_date[] = $count;
    }

    return $counts_by_date;
}


// customer trend - e.g.((16/19)-1)*100
if(count($customers)==0){
    $customer_trend = 0;
}else{
    $customer_trend = (count($customers) / (float) count($customers_prev)) - 1;
}




// trend interval
function edd_sm_trend_interval( $range ){
    $trend_interval = 0;

    switch( $range ){
        case 'today':
            $trend_interval = 1;
            break;
        case 'yesterday':
            $trend_interval = 1;
            break;
        case 'this_week':
            $trend_interval = 7;
            break;
        case 'last_week':
            $trend_interval = 7;
        break;
        case 'this_month':
            $trend_interval = 30;
            break;
        case 'last_month':
            $trend_interval = 30;
            break;
        case 'this_quarter':
            $trend_interval = 90;
            break;
        case 'last_quarter':
            $trend_interval = 90;
            break;
        case 'this_year':
            $trend_interval = 365;
            break;
        case 'last_year':
            $trend_interval = 365;
            break;
    }

    return $trend_interval;

}




$stats = new EDD_Payment_Stats;

// revenue
$earnings = $stats->get_earnings( 0, $dates['range'] );

// sales
$sales = $stats->get_sales( 0, $dates['range'] );




// shameless stolen from function edd_reports_graph() from graphing.php
function edd_sm_sales_report( $sales_date ){
    
    // Retrieve the queried dates
    $_dates = $sales_date; // edd_get_report_dates();

    // Determine graph options
    switch ( $_dates['range'] ) :
        case 'today' :
        case 'yesterday' :
            $day_by_day = true;
            break;
        case 'last_year' :
        case 'this_year' :
        case 'last_quarter' :
        case 'this_quarter' :
            $day_by_day = false;
            break;
        case 'other' :
            if( $_dates['m_end'] - $_dates['m_start'] >= 2 || ( $_dates['year_end'] > $_dates['year'] && ( $_dates['m_start'] - $_dates['m_end'] ) != 11 ) ) {
                $day_by_day = false;
            } else {
                $day_by_day = true;
            }
            break;
        default:
            $day_by_day = true;
            break;
    endswitch;

    $earnings_totals = 0.00; // Total earnings for time period shown
    $sales_totals    = 0;    // Total sales for time period shown

    $include_taxes = true; // empty( $_GET['exclude_taxes'] ) ? true : false;
    $earnings_data = array();
    $sales_data    = array();

    if( $_dates['range'] == 'today' || $_dates['range'] == 'yesterday' ) {
        // Hour by hour
        $hour  = 1;
        $month = $_dates['m_start'];
        while ( $hour <= 23 ) {

            $sales    = edd_get_sales_by_date( $_dates['day'], $month, $_dates['year'], $hour );
            $earnings = edd_get_earnings_by_date( $_dates['day'], $month, $_dates['year'], $hour, $include_taxes );

            $sales_totals    += $sales;
            $earnings_totals += $earnings;

            $date            = mktime( $hour, 0, 0, $month, $_dates['day'], $_dates['year'] ) * 1000;
            $sales_data[]    = array( $date, $sales );
            $earnings_data[] = array( $date, $earnings );

            $hour++;
        }

    } elseif ( $_dates['range'] == 'this_week' || $_dates['range'] == 'last_week' ) {

        $num_of_days = cal_days_in_month( CAL_GREGORIAN, $_dates['m_start'], $_dates['year'] );

        $report_dates = array();
        $i = 0;
        while ( $i <= 6 ) {

            if ( ( $_dates['day'] + $i ) <= $num_of_days ) {
                $report_dates[ $i ] = array(
                    'day'   => (string) $_dates['day'] + $i,
                    'month' => $_dates['m_start'],
                    'year'  => $_dates['year'],
                );
            } else {
                $report_dates[ $i ] = array(
                    'day'   => (string) $i,
                    'month' => $_dates['m_end'],
                    'year'  => $_dates['year_end'],
                );
            }

            $i++;
        }

        foreach ( $report_dates as $report_date ) {
            $sales = edd_get_sales_by_date( $report_date['day'], $report_date['month'], $report_date['year'] );
            $sales_totals += $sales;

            $earnings        = edd_get_earnings_by_date( $report_date['day'], $report_date['month'], $report_date['year'] , null, $include_taxes );
            $earnings_totals += $earnings;

            $date            = mktime( 0, 0, 0,  $report_date['month'], $report_date['day'], $report_date['year']  ) * 1000;
            $sales_data[]    = array( $date, $sales );
            $earnings_data[] = array( $date, $earnings );
        }

    } else {

        $y = $_dates['year'];

        while( $y <= $_dates['year_end'] ) {

            $last_year = false;

            if( $_dates['year'] == $_dates['year_end'] ) {
                $month_start = $_dates['m_start'];
                $month_end   = $_dates['m_end'];
                $last_year   = true;
            } elseif( $y == $_dates['year'] ) {
                $month_start = $_dates['m_start'];
                $month_end   = 12;
            } elseif ( $y == $_dates['year_end'] ) {
                $month_start = 1;
                $month_end   = $_dates['m_end'];
            } else {
                $month_start = 1;
                $month_end   = 12;
            }

            $i = $month_start;
            while ( $i <= $month_end ) {

                if ( $day_by_day ) {

                    $d = $_dates['day'];

                    if( $i == $month_end ) {

                        $num_of_days = $_dates['day_end'];

                        if ( $month_start < $month_end ) {

                            $d = 1;

                        }

                    } else {

                        $num_of_days = cal_days_in_month( CAL_GREGORIAN, $i, $y );

                    }




                    while ( $d <= $num_of_days ) {

                        $sales = edd_get_sales_by_date( $d, $i, $y );
                        $sales_totals += $sales;

                        $earnings = edd_get_earnings_by_date( $d, $i, $y, null, $include_taxes );
                        $earnings_totals += $earnings;

                        $date = mktime( 0, 0, 0, $i, $d, $y ) * 1000;
                        $sales_data[] = array( $date, $sales );
                        $earnings_data[] = array( $date, $earnings );
                        $d++;

                    }

                } else {

                    $sales = edd_get_sales_by_date( null, $i, $y );
                    $sales_totals += $sales;

                    $earnings = edd_get_earnings_by_date( null, $i, $y, null, $include_taxes );
                    $earnings_totals += $earnings;

                    if( $i == $month_end && $last_year ) {

                        $num_of_days = cal_days_in_month( CAL_GREGORIAN, $i, $y );

                    } else {

                        $num_of_days = 1;

                    }

                    $date = mktime( 0, 0, 0, $i, $num_of_days, $y ) * 1000;
                    $sales_data[] = array( $date, $sales );
                    $earnings_data[] = array( $date, $earnings );

                }

                $i++;

            }

            $y++;
        }

    }

    $_data = array(); 

    $_data['sales'] = $sales_data;
    $_data['earnings'] = $earnings_data;
    
    return $_data;

   // print_r($sales_data);
   // print_r($earnings_data);
}
$sales_report = edd_sm_sales_report( $dates );





// top seller
$top_sellers = $stats->get_best_selling( 3 );

$top_sellers_download = array();
$top_sellers_count = array();

if( is_array( $top_sellers ) && count( $top_sellers )>0){
    foreach($top_sellers as $seller){
        $top_sellers_download[] = edd_get_download( $seller->download_id ); 
        $top_sellers_count[] = $seller->sales;
    }
}




// payment counts
$args['start-date'] = $date_range['date']['start'];
$args['end-date']     = $date_range['date']['end'];
$payment_counts = (array)edd_count_payments( $args );    // must cast to array first 
// print_r($payment_counts);




// payment details
$payments = new EDD_Payments_Query;
$payments->args['start_date']     = $date_range['date']['start'];
$payments->args['end_date']     = $date_range['date']['end'];
$payment_status = $payments->args['status'];
$payments_details = $payments->get_payments();


// HTTP GET dates from url
function edd_sm_get_dates() {
    $dates = array();

    $current_time = current_time( 'timestamp' );

    $dates['range']      = isset( $_GET['range'] )   ? $_GET['range']   : 'this_month';
    $dates['year']       = isset( $_GET['year'] )    ? $_GET['year']    : date( 'Y' );
    $dates['year_end']   = isset( $_GET['year_end'] )? $_GET['year_end']: date( 'Y' );
    $dates['m_start']    = isset( $_GET['m_start'] ) ? $_GET['m_start'] : 1;
    $dates['m_end']      = isset( $_GET['m_end'] )   ? $_GET['m_end']   : 12;
    $dates['day']        = isset( $_GET['day'] )     ? $_GET['day']     : 1;
    $dates['day_end']    = isset( $_GET['day_end'] ) ? $_GET['day_end'] : cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );

    // Modify dates based on predefined ranges
    switch ( $dates['range'] ) :

        case 'this_month' :
            $dates['m_start']  = date( 'n', $current_time );
            $dates['m_end']    = date( 'n', $current_time );
            $dates['day']      = 1;
            $dates['day_end']  = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
            $dates['year']     = date( 'Y' );
            $dates['year_end'] = date( 'Y' );
        break;

        case 'last_month' :
            if( date( 'n' ) == 1 ) {
                $dates['m_start'] = 12;
                $dates['m_end']      = 12;
                $dates['year']    = date( 'Y', $current_time ) - 1;
                $dates['year_end']= date( 'Y', $current_time ) - 1;
            } else {
                $dates['m_start'] = date( 'n' ) - 1;
                $dates['m_end']      = date( 'n' ) - 1;
                $dates['year_end']= $dates['year'];
            }
            $dates['day_end'] = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
        break;

        case 'today' :
            $dates['day']        = date( 'd', $current_time );
            $dates['m_start']     = date( 'n', $current_time );
            $dates['m_end']        = date( 'n', $current_time );
            $dates['year']        = date( 'Y', $current_time );
        break;

        case 'yesterday' :

            $year               = date( 'Y', $current_time );
            $month              = date( 'n', $current_time );
            $day                = date( 'd', $current_time );

            if ( $month == 1 && $day == 1 ) {

                $year -= 1;
                $month = 12;
                $day   = cal_days_in_month( CAL_GREGORIAN, $month, $year );

            } elseif ( $month > 1 && $day == 1 ) {

                $month -= 1;
                $day   = cal_days_in_month( CAL_GREGORIAN, $month, $year );

            } else {

                $day -= 1;

            }

            $dates['day']       = $day;
            $dates['m_start']   = $month;
            $dates['m_end']     = $month;
            $dates['year']      = $year;
            $dates['year_end']      = $year;
        break;

        case 'this_week' :
            $dates['day']       = date( 'd', $current_time - ( date( 'w', $current_time ) - 1 ) *60*60*24 ) - 1;
            $dates['day']      += get_option( 'start_of_week' );
            $dates['day_end']   = $dates['day'];
            $dates['m_start']     = date( 'n', $current_time );
            $dates['m_end']        = date( 'n', $current_time );
            $dates['year']        = date( 'Y', $current_time );
        break;

        case 'last_week' :
            $dates['day']       = date( 'd', $current_time - ( date( 'w' ) - 1 ) *60*60*24 ) - 8;
            $dates['day']      += get_option( 'start_of_week' );
            $dates['day_end']   = $dates['day'] + 6;
            $dates['year']        = date( 'Y' );

            if( date( 'j', $current_time ) <= 7 ) {
                $dates['m_start']     = date( 'n', $current_time ) - 1;
                $dates['m_end']        = date( 'n', $current_time ) - 1;
                if( $dates['m_start'] <= 1 ) {
                    $dates['year'] = date( 'Y', $current_time ) - 1;
                    $dates['year_end'] = date( 'Y', $current_time ) - 1;
                }
            } else {
                $dates['m_start']     = date( 'n', $current_time );
                $dates['m_end']        = date( 'n', $current_time );
            }
        break;

        case 'this_quarter' :
            $month_now = date( 'n', $current_time );

            if ( $month_now <= 3 ) {

                $dates['m_start']     = 1;
                $dates['m_end']        = 4;
                $dates['year']        = date( 'Y', $current_time );

            } else if ( $month_now <= 6 ) {

                $dates['m_start']     = 4;
                $dates['m_end']        = 7;
                $dates['year']        = date( 'Y', $current_time );

            } else if ( $month_now <= 9 ) {

                $dates['m_start']     = 7;
                $dates['m_end']        = 10;
                $dates['year']        = date( 'Y', $current_time );

            } else {

                $dates['m_start']     = 10;
                $dates['m_end']        = 1;
                $dates['year']        = date( 'Y', $current_time );
                $dates['year_end']  = date( 'Y', $current_time ) + 1;

            }
        break;

        case 'last_quarter' :
            $month_now = date( 'n' );

            if ( $month_now <= 3 ) {

                $dates['m_start']   = 10;
                $dates['m_end']     = 12;
                $dates['year']      = date( 'Y', $current_time ) - 1; // Previous year
                $dates['year_end']  = date( 'Y', $current_time ) - 1; // Previous year

            } else if ( $month_now <= 6 ) {

                $dates['m_start']     = 1;
                $dates['m_end']        = 3;
                $dates['year']        = date( 'Y', $current_time );

            } else if ( $month_now <= 9 ) {

                $dates['m_start']     = 4;
                $dates['m_end']        = 6;
                $dates['year']        = date( 'Y', $current_time );

            } else {

                $dates['m_start']     = 7;
                $dates['m_end']        = 9;
                $dates['year']        = date( 'Y', $current_time );

            }
        break;

        case 'this_year' :
            $dates['m_start']     = 1;
            $dates['m_end']        = 12;
            $dates['year']        = date( 'Y', $current_time );
        break;

        case 'last_year' :
            $dates['m_start']     = 1;
            $dates['m_end']        = 12;
            $dates['year']        = date( 'Y', $current_time ) - 1;
            $dates['year_end']  = date( 'Y', $current_time ) - 1;
        break;

    endswitch;

    return apply_filters( 'edd_sales_metrics_dates', $dates );
}

// Get generate url parameters for dates
function edd_sm_dates_url_params( $range = 'this_month' ){
    // $start_date     = date( 'm-d-Y', $EDD_SM_Stats->start_date);
    // $end_date     = date( 'm-d-Y', $EDD_SM_Stats->end_date);
    $EDD_SM_Stats = new EDD_Sales_Metrics_Stats();
    $EDD_SM_Stats->setup_dates( $range );

    $dates['range']     = $range;
    $dates['year']      = date( 'Y', $EDD_SM_Stats->start_date );
    $dates['year_end']    = date( 'Y', $EDD_SM_Stats->end_date );
    $dates['m_start']     = date( 'm', $EDD_SM_Stats->start_date );
    $dates['m_end']       = date( 'm', $EDD_SM_Stats->end_date );
    $dates['day']         = date( 'd', $EDD_SM_Stats->start_date );
    $dates['day_end']     = date( 'd', $EDD_SM_Stats->end_date );

    $date_url_params = '';
    foreach( $dates as $key=>$value ){
        $date_url_params .= '&'. $key .'='. $value;
    }

    return apply_filters( 'edd_sales_metrics_dates_url_params', $date_url_params );

}

// Get date range. 
function edd_sm_get_date_range( $d = array() ){
    $d_range = array();

    $d_range['date']['start']        = $d['m_start']    .'/'.$d['day']        .'/'.$d['year'];
    $d_range['date']['end']         = $d['m_end']    .'/'.$d['day_end']    .'/'.$d['year_end'];

    return $d_range;
}

// Get previous date range to calculate trend
function edd_sm_get_date_range_previous( $d = array() ){
    $d_range_previous = array();
    $d_range = array();

    $d_range['date']['start'] = $d['m_start']    .'/'.$d['day']        .'/'.$d['year'];
    $d_range['date']['end']   = $d['m_end']      .'/'.$d['day_end']    .'/'.$d['year_end'];

    switch( $d['range'] ){
        case 'today':
            $d_range_previous_start = new DateTime($d_range['date']['start']);
            $d_range_previous_start->sub(new DateInterval('P1D'));
            $d_range_previous_end = new DateTime($d_range['date']['end']);
            $d_range_previous_end->sub(new DateInterval('P1D'));
            $d_range_previous[] = $d_range_previous_start; 
            $d_range_previous[] = $d_range_previous_end; 
            
            break;
        case 'yesterday':
            $d_range_previous_start = new DateTime($d_range['date']['start']);
            $d_range_previous_start->sub(new DateInterval('P2D'));
            $d_range_previous_end = new DateTime($d_range['date']['end']);
            $d_range_previous_end->sub(new DateInterval('P2D'));
            $d_range_previous[] = $d_range_previous_start; 
            $d_range_previous[] = $d_range_previous_end; 
            
            break;
        case 'this_week':
            $d_range_previous_start = new DateTime($d_range['date']['start']);
            $d_range_previous_start->sub(new DateInterval('P1W'));
            $d_range_previous_end = new DateTime($d_range['date']['end']);
            $d_range_previous_end->sub(new DateInterval('P1W'));
            $d_range_previous[] = $d_range_previous_start; 
            $d_range_previous[] = $d_range_previous_end; 
            
            break;
        case 'last_week':
            $d_range_previous_start = new DateTime($d_range['date']['start']);
            $d_range_previous_start->sub(new DateInterval('P2W'));
            $d_range_previous_end = new DateTime($d_range['date']['end']);
            $d_range_previous_end->sub(new DateInterval('P2W'));
            $d_range_previous[] = $d_range_previous_start; 
            $d_range_previous[] = $d_range_previous_end; 

            break;
        case 'this_month':
            $d_range_previous_start = new DateTime($d_range['date']['start']);
            $d_range_previous_start->sub(new DateInterval('P1M'));
            $d_range_previous_end = new DateTime($d_range['date']['end']);
            $d_range_previous_end->sub(new DateInterval('P1M'));
            $d_range_previous[] = $d_range_previous_start; 
            $d_range_previous[] = $d_range_previous_end; 

            break;
        case 'last_month':
            $d_range_previous_start = new DateTime($d_range['date']['start']);
            $d_range_previous_start->sub(new DateInterval('P2M'));
            $d_range_previous_end = new DateTime($d_range['date']['end']);
            $d_range_previous_end->sub(new DateInterval('P2M'));
            $d_range_previous[] = $d_range_previous_start; 
            $d_range_previous[] = $d_range_previous_end; 

            break;
        case 'this_quarter':

            $current_month = date('m');
            $current_year = date('Y');

            if($current_month>=1 && $current_month<=3)
            {
                $start_date = strtotime('1-January-'.$current_year);  // timestamp or 1-Januray 12:00:00 AM
                $end_date = strtotime('1-April-'.$current_year);  // timestamp or 1-April 12:00:00 AM means end of 31 March
            }
            elseif($current_month>=4 && $current_month<=6)
            {
                $start_date = strtotime('1-April-'.$current_year);  // timestamp or 1-April 12:00:00 AM
                $end_date = strtotime('1-July-'.$current_year);  // timestamp or 1-July 12:00:00 AM means end of 30 June
            }
            elseif($current_month>=7 && $current_month<=9)
            {
                $start_date = strtotime('1-July-'.$current_year);  // timestamp or 1-July 12:00:00 AM
                $end_date = strtotime('1-October-'.$current_year);  // timestamp or 1-October 12:00:00 AM means end of 30 September
            }
            elseif($current_month>=10 && $current_month<=12)
            {
                $start_date = strtotime('1-October-'.$current_year);  // timestamp or 1-October 12:00:00 AM
                $end_date = strtotime('1-January-'.($current_year+1));  // timestamp or 1-January Next year 12:00:00 AM means end of 31 December this year
            }
            $d_range_previous[] = $start_date; 
            $d_range_previous[] = $end_date; 

            break;
        case 'last_quarter':

            $current_month = date('m');
            $current_year = date('Y');

            if($current_month>=1 && $current_month<=3)
            {
                $start_date = strtotime('1-October-'.($current_year-1));  // timestamp or 1-October Last Year 12:00:00 AM
                $end_date = strtotime('1-January-'.$current_year);  // // timestamp or 1-January  12:00:00 AM means end of 31 December Last year
            } 
            elseif($current_month>=4 && $current_month<=6)
            {
                $start_date = strtotime('1-January-'.$current_year);  // timestamp or 1-Januray 12:00:00 AM
                $end_date = strtotime('1-April-'.$current_year);  // timestamp or 1-April 12:00:00 AM means end of 31 March
            }
            elseif($current_month>=7 && $current_month<=9)
            {
                $start_date = strtotime('1-April-'.$current_year);  // timestamp or 1-April 12:00:00 AM
                $end_date = strtotime('1-July-'.$current_year);  // timestamp or 1-July 12:00:00 AM means end of 30 June
            }
            elseif($current_month>=10 && $current_month<=12)
            {
                $start_date = strtotime('1-July-'.$current_year);  // timestamp or 1-July 12:00:00 AM
                $end_date = strtotime('1-October-'.$current_year);  // timestamp or 1-October 12:00:00 AM means end of 30 September
            }
            $d_range_previous[] = $start_date; 
            $d_range_previous[] = $end_date; 
            
            break;

        case 'this_year':
        
            break;
        case 'last_year':
            
            break;
    }

    return $d_range_previous;

}

// jqPlot script to generate chart
function edd_sm_dashboard_chart($chart_id, $data_points = array(), $chart_type = 'LINE'){

    switch($chart_type){
        case 'LINE':
            echo '<div class="graph" style="opacity: 1;">
    
                    <div id="bind_'. $chart_id .'">
                        <span id="bind_span_label_'. $chart_id .'"></span>
                        <span id="bind_span_data_'. $chart_id .'"></span>
                    </div>
                    <div id="resizable">
                        <div id="'. $chart_id .'" class="plot jqplot-target" style="width:0px;height:160px;"></div>
                    </div>

                    <script  language="javascript" type="text/javascript"> 
                    var _'. $chart_id .'_plot_properties;
                    jQuery(document).ready(function($){ 
                        _'. $chart_id .'_plot_properties = {"grid":{"background":"white","borderColor":"#000000","borderWidth":0,"drawBorder":false,"shadow":false},"axes":{"xaxis":{"properties":"xaxis","drawMajorGridlines":false},"yaxis":{"properties":"yaxis","drawMajorGridlines":false}},"seriesDefaults":{"fill":true,"color":"#E8F1FF","shadow":false,"rendererOptions":{"smooth":true}},"axesDefaults":{"showTicks":false,"rendererOptions":{"drawBaseline":false}},"stackSeries":false,"animate":true,"animateReplot":true}

                        $.jqplot.config.enablePlugins = true;
                        $.jqplot.config.defaultHeight = 300;
                        $.jqplot.config.defaultWidth  = 400;
                         _'. $chart_id .'= $.jqplot("'. $chart_id .'", '. json_encode($data_points) .', _'. $chart_id .'_plot_properties);
                    });
                    </script>

                    <div style="clear:both;">&nbsp;</div>                        
                    
                    <style>
                        .jqplot-target{
                            position: relative !important;
                            width: auto !important;
                        }
                    </style>
                    <script>
                    jQuery(document).ready(function($){
                        $(window).on("resize",function(event){
                            $.each(_'. $chart_id .'.series, function(index, series) { series.barWidth = undefined; });
                            _'. $chart_id .'.destroy(); // Destroy the chart..
                            _'. $chart_id .'.replot(); // Replot the chart with new/old values..
                        });
                    });
                    </script>

                </div>';
            break;

        case 'PIE':

            echo '<div class="graph" style="opacity: 1;">

                <div id="bind_'. $chart_id .'">
                    <span id="bind_span_label_'. $chart_id .'"></span><span id="bind_span_data_'. $chart_id .'"></span>
                </div>
                <div id="resizable">
                    <div id="'. $chart_id .'" class="plot jqplot-target" style="width:0px;height:160px;"></div>
                </div>

                <script  language="javascript" type="text/javascript"> 
                var _'. $chart_id .'_plot_properties;
                jQuery(document).ready(function($){ 
                    _'. $chart_id .'_plot_properties = {"grid":{"background":"white","borderColor":"#000000","borderWidth":0,"drawBorder":false,"shadow":false},"axes":{"xaxis":{"properties":"xaxis","drawMajorGridlines":false},"yaxis":{"properties":"yaxis","drawMajorGridlines":false}},"seriesDefaults":{"renderer":$.jqplot.PieRenderer,"rendererOptions":{"barPadding":1,"barMargin":40}},"axesDefaults":{"showTicks":false,"rendererOptions":{"drawBaseline":false}},"stackSeries":false,"animate":true,"animateReplot":true}

                        $.jqplot.config.enablePlugins = true;
                        $.jqplot.config.defaultHeight = 300;
                        $.jqplot.config.defaultWidth  = 400;
                         _'. $chart_id .'= $.jqplot("'. $chart_id .'", '. json_encode($data_points) .', _'. $chart_id .'_plot_properties);
                });
                </script>

                <div style="clear:both;">&nbsp;</div>                        
                <style>
                    .jqplot-target{
                        position: relative !important;
                        width: auto !important;
                    }
                </style>
                <script>
                jQuery(document).ready(function($){
                    $(window).on("resize",function(event){
                        $.each(_'. $chart_id .'.series, function(index, series) { series.barWidth = undefined; });
                        _'. $chart_id .'.destroy(); // Destroy the chart..
                        _'. $chart_id .'.replot(); // Replot the chart with new/old values..
                    });
                });
                </script>

            </div>';

            break;
    }
    
}
?>




<!-- jqPlot required libraries. No need to include jQuery as it's provided by WP already -->
<!--[if lt IE 9]><script language="javascript" type="text/javascript" src="<?php echo plugins_url('libraries/jqplot/excanvas.min.js', dirname(__FILE__)) ?>"></script><![endif]-->
<link rel="stylesheet" type="text/css" href="<?php echo plugins_url('libraries/jqplot/jquery.jqplot.min.css', dirname(__FILE__)) ?>" />
<script language="javascript" type="text/javascript" src="<?php echo plugins_url('libraries/jqplot/jquery.jqplot.min.js', dirname(__FILE__)) ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo plugins_url('libraries/jqplot/plugins/jqplot.pierenderer.min.js', dirname(__FILE__)) ?>"></script>





<!-- Sales Metrics -->    
<h1><?php _e('Easy Digital Downloads - Sales Metrics', 'edd-salesmetrics')?></h1>

<div id="edd-sm">
    <div class="header" style="display:block">
        <div class="container">
            <h1 class="header-heading"></h1>
            <form method="post">
                
                <?php if(isset($_GET['range']) && $_GET['range'] == 'today'): ?>
                    <span class="current-range">Today</span> |
                <?php else: ?>
                    <a href="<?php echo admin_url(); ?>admin.php?page=edd-sales-metrics<?php echo edd_sm_dates_url_params( 'today' ); ?>">Today</a> | 
                <?php endif; ?>

                <?php if(isset($_GET['range']) && $_GET['range'] == 'yesterday'): ?>
                    <span class="current-range">Yesterday</span> |
                <?php else: ?>
                    <a href="<?php echo admin_url(); ?>admin.php?page=edd-sales-metrics<?php echo edd_sm_dates_url_params( 'yesterday' ); ?>">Yesterday</a> | 
                <?php endif; ?>

                <?php if(isset($_GET['range']) && $_GET['range'] == 'this_week'): ?>
                    <span class="current-range">This Week</span> |
                <?php else: ?>
                    <a href="<?php echo admin_url(); ?>admin.php?page=edd-sales-metrics<?php echo edd_sm_dates_url_params( 'this_week' ); ?>">This Week</a> | 
                <?php endif; ?>

                <?php if(isset($_GET['range']) && $_GET['range'] == 'last_week'): ?>
                    <span class="current-range">Last Week</span> |
                <?php else: ?>
                    <a href="<?php echo admin_url(); ?>admin.php?page=edd-sales-metrics<?php echo edd_sm_dates_url_params( 'last_week' ); ?>">Last Week</a> | 
                <?php endif; ?>
                
                <?php if( (isset($_GET['range']) && $_GET['range'] == 'this_month') || !isset($_GET['range'] )): ?>
                    <span class="current-range">This Month</span> |
                <?php else: ?>
                    <a href="<?php echo admin_url(); ?>admin.php?page=edd-sales-metrics<?php echo edd_sm_dates_url_params( 'this_month' ); ?>">This Month</a> | 
                <?php endif; ?>
                
                <?php if(isset($_GET['range']) && $_GET['range'] == 'last_month'): ?>
                    <span class="current-range">Last Month</span> |
                <?php else: ?>
                    <a href="<?php echo admin_url(); ?>admin.php?page=edd-sales-metrics<?php echo edd_sm_dates_url_params( 'last_month' ); ?>">Last Month</a> | 
                <?php endif; ?>
                
                <?php if(isset($_GET['range']) && $_GET['range'] == 'this_quarter'): ?>
                    <span class="current-range">This Quarter</span> |
                <?php else: ?>
                    <a href="<?php echo admin_url(); ?>admin.php?page=edd-sales-metrics<?php echo edd_sm_dates_url_params( 'this_quarter' ); ?>">This Quarter</a> | 
                <?php endif; ?>
                
                <?php if(isset($_GET['range']) && $_GET['range'] == 'last_quarter'): ?>
                    <span class="current-range">Last Quarter</span> |
                <?php else: ?>                        
                    <a href="<?php echo admin_url(); ?>admin.php?page=edd-sales-metrics<?php echo edd_sm_dates_url_params( 'last_quarter' ); ?>">Last Quarter</a> | 
                <?php endif; ?>
                
                <?php if(isset($_GET['range']) && $_GET['range'] == 'this_year'): ?>
                    <span class="current-range">This Year</span> |
                <?php else: ?>
                    <a href="<?php echo admin_url(); ?>admin.php?page=edd-sales-metrics<?php echo edd_sm_dates_url_params( 'this_year' ); ?>">This Year</a> | 
                <?php endif; ?>
                
                <?php if(isset($_GET['range']) && $_GET['range'] == 'last_year'): ?>
                    <span class="current-range">Last Year</span> |
                <?php else: ?>                
                    <a href="<?php echo admin_url(); ?>admin.php?page=edd-sales-metrics<?php echo edd_sm_dates_url_params( 'last_year' ); ?>">Last Year</a>
                <?php endif; ?>

            </form>
        </div>
    </div>
    <div class="nav-bar" style="display:none">
        <div class="container">
            <ul class="nav">
                <li><a href="#">Nav item 1</a></li>
                <li><a href="#">Nav item 2</a></li>
                <li><a href="#">Nav item 3</a></li>
            </ul>
        </div>
    </div>

    <div class="content">
        <div class="container">
            <div class="col1">
                <!-- Paragraphs -->
                <article class="metric-box" data-metric="mrr">
                    <span class="view-details" style="opacity: 1;display:none">View Details </span>
                    <h1 class="primary" style="opacity: 1;z-index:10"><?php echo count( $customers ); ?></h1>
                    <div class="trend" style="opacity: 1;z-index:10">
                        <h2 class="percent up <?php echo (($customer_trend >= 0) ? 'green' : 'red' ); ?>">
                            <?php echo round($customer_trend*100, 2).'%'?></h2><span><?php echo edd_sm_trend_interval( $dates['range'] ); ?> Days Ago</span>
                    </div>
                        
                    <?php 
                    $customers_count_by_date = edd_sm_get_counts_by_date($customers, $customer_date_range);
                    
                    echo edd_sm_dashboard_chart('customer_chart', array(array_map('intval', $customers_count_by_date)));
                    ?>

                    <h3 class="title" style="opacity: 1;z-index:10"><?php _e('Total Customers', 'edd-salesmetrics')?></h3>
                </article>

                <article class="metric-box" data-metric="mrr">
                    <span class="view-details" style="opacity: 1;display:none">View Details </span>
                    <h1 class="primary" style="opacity: 1;1;z-index:10">$<?php echo $earnings; ?></h1>
                    <div class="trend" style="opacity: 1;1;z-index:10">
                        <h2 class="percent up green">--%</h2><span><?php echo edd_sm_trend_interval( $dates['range'] ); ?> Days Ago</span>
                    </div>
                    
                    <?php
                    $earnings_amt = array();

                    foreach($sales_report['earnings'] as $tmp){
                        $earnings_amt[] = $tmp[1];
                    }

                     echo edd_sm_dashboard_chart('revenue_chart', array(array_map('intval', $earnings_amt)));
                    ?>

                    <h3 class="title" style="opacity: 1;1;z-index:10"><?php _e('Total Revenue', 'edd-salesmetrics')?></h3>
                </article>

                <article class="metric-box" data-metric="mrr">
                    <span class="view-details" style="opacity: 1;display:none">View Details </span>
                    <h1 class="primary" style="opacity: 1;z-index:10"><?php echo $sales; ?></h1>
                    <div class="trend" style="opacity: 1;z-index:10">
                        <h2 class="percent up red">--%</h2><span><?php echo edd_sm_trend_interval( $dates['range'] ); ?> Days Ago</span>
                    </div>

                    <?php
                    $sales_cnt = array();

                    foreach($sales_report['sales'] as $tmp){
                        $sales_cnt[] = $tmp[1];
                    }

                    echo edd_sm_dashboard_chart('sales_chart', array(array_map('intval', $sales_cnt)));
                    ?>
                    
                    <h3 class="title" style="opacity: 1;z-index:10"><?php _e('Total Sales', 'edd-salesmetrics')?></h3>
                </article>

            </div>
            <div class="col2">

                <article class="metric-box" data-metric="mrr">
                    <span class="view-details" style="opacity: 1;display:none">View Details </span>
                    <h1 class="primary" style="opacity: 1;z-index:10"><?php echo $payment_counts['abandoned']; ?></h1>
                    <div class="trend" style="opacity: 1;z-index:10">
                        <h2 class="percent up green">--%</h2><span><?php echo edd_sm_trend_interval( $dates['range'] ); ?> Days Ago</span>
                    </div>

                    <?php
                    echo edd_sm_dashboard_chart('abandoned_chart', array(array_map('intval', array(0, 12,22,11, 23, 55, 2, 64))));
                    ?>

                    <h3 class="title" style="opacity: 1;z-index:10"><?php _e('Abandoned Cart', 'edd-salesmetrics')?></h3>
                </article>





                <article class="metric-box" data-metric="mrr">
                    <span class="view-details" style="opacity: 1;display:none">View Details </span>
                    <h1 class="primary" style="opacity: 1;z-index:10"><?php echo $payment_counts['refunded']; ?></h1>
                    <div class="trend" style="opacity: 1;z-index:10">
                        <h2 class="percent up green">--%</h2><span><?php echo edd_sm_trend_interval( $dates['range'] ); ?> Days Ago</span>
                    </div>
                    
                    <?php
                    echo edd_sm_dashboard_chart('refund_chart', array(array_map('intval', array(0, 2,0,1, 3, 0, 0, 1))));
                    ?>

                    <h3 class="title" style="opacity: 1;z-index:10"><?php _e('Refund Count', 'edd-salesmetrics')?></h3>
                </article>

                <article class="metric-box" data-metric="mrr">
                    <span class="view-details" style="opacity: 1;display:none">View Details </span>
                    <?php for($i = 0; $i < count($top_sellers_download); $i++): ?>
                        <h1 class="primary truncate top-seller" style="opacity: 1;z-index:10"><?php echo '#'.($i+1) . ': '. $top_sellers_download[$i]->post_title; ?></h3>
                    <?php endfor; ?>
                    <div class="trend" style="opacity: 1;z-index:10">
                        <h2 class="percent up green"></h2><span></span>
                    </div>

                    <?php
                    echo edd_sm_dashboard_chart('topseller_chart', array(array_map('intval', $top_sellers_count)), 'PIE');
                    ?>

                    <h3 class="title" style="opacity: 1;z-index:10"><?php _e('Top Seller', 'edd-salesmetrics')?></h3>
                </article>

            </div>
            <div class="col3">
                <article class="stream">
                <h2><?php _e('Purchases Made in ', 'edd-salesmetrics')?><?php _e(ucwords(str_replace('_', ' ', $dates['range'])), 'edd-salesmetrics'); ?> </h2>
                <div class="stream__filter" style="display:none">
                <ul class="filter__cat">
                    <li class="cat__title">Everything</li>
                    <li><a class="all" href="everything">Everything</a></li>
                    <li><a class="light" href="new">New Customers</a></li>
                    <li><a class="blue" href="upgrades">Upgrades</a></li>
                    <li><a class="green" href="charges">Charges</a></li>
                    <li><a class="yellow" href="downgrades">Downgrades</a></li>
                    <li><a class="orange" href="refunds">Refunds</a></li>
                    <li><a class="red" href="failed">Failed Charges</a></li>
                    <li><a class="black" href="cancellations">Cancellations</a></li>
                    </ul>
                </div>
                <ol>
                    <?php 
                    foreach($payments_details as $p){
                        if( $p->post_status == 'publish' || 
                            $p->post_status == 'refunded' ||
                            $p->post_status == 'edd_subscription' ){

                            switch($p->post_status){
                                case 'refunded':
                                    $status = '<span class="type refunded">Refunded</span>';
                                    break;
                                case 'edd_subscription':
                                    $status = '<span class="type subscription">Subscription</span>';
                                    break;
                                default:
                                    $status = '<span class="type charge">Charge</span>'; // publish
                            }


                            echo '<li>';
                            echo '<p>';
                            echo $status;
                            echo '<b><a href="'. admin_url() .'edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id='. $p->ID .'">$'. $p->total.'</a></b> from <b>'. $p->user_info['first_name'] .' '. $p->user_info['last_name'] .'</b> ';
                            echo '<time datetime="'. $p->date .'" class="timeago"> '. EDD_Sales_Metrics_Util::distanceOfTime($p->date) .'</time>';
                            echo '</p>';
                            echo '</li>';
                        }    
                    }
                    ?>

                </ol>

                <hr />

                    <div class="newsletter">
                        <h3>Keep me posted!</h3>
                        <p>
                            If you're interested in hearing about updates and new reporting features enter your email address below.
                        </p>

                        <!-- Begin MailChimp Signup Form -->
                        <link href="//cdn-images.mailchimp.com/embedcode/classic-10_7.css" rel="stylesheet" type="text/css">
                        <style type="text/css">
                            #mc_embed_signup{background:#fff; clear:left; font:14px Helvetica,Arial,sans-serif; }
                            /* Add your own MailChimp form style overrides in your site stylesheet or in this style block.
                               We recommend moving this block and the preceding CSS link to the HEAD of your HTML file. */
                        </style>
                        <div id="mc_embed_signup">
                        <form action="//phpcontrols.us13.list-manage.com/subscribe/post?u=c955de88d067f5ff317feaf2a&amp;id=7bdd44b9ab" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
                            <div id="mc_embed_signup_scroll">
                            
                            <div class="mc-field-group">
                                <label for="mce-EMAIL"></label>
                                <input type="email" value="" name="EMAIL" class="required email" placeholder="Email address" id="mce-EMAIL">
                            </div>
                                <div id="mce-responses" class="clear">
                                    <div class="response" id="mce-error-response" style="display:none"></div>
                                    <div class="response" id="mce-success-response" style="display:none"></div>
                                </div>    <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
                                <div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_c955de88d067f5ff317feaf2a_7bdd44b9ab" tabindex="-1" value=""></div>
                                <div class="clear"><input type="submit" value="Submit" name="subscribe" id="mc-embedded-subscribe" class="button"></div>
                                </div>
                        </form>
                        </div>
                        <script type='text/javascript' src='//s3.amazonaws.com/downloads.mailchimp.com/js/mc-validate.js'></script><script type='text/javascript'>(function($) {window.fnames = new Array(); window.ftypes = new Array();fnames[0]='EMAIL';ftypes[0]='email';fnames[1]='FNAME';ftypes[1]='text';fnames[2]='LNAME';ftypes[2]='text';}(jQuery));var $mcj = jQuery.noConflict(true);</script>
                        <!--End mc_embed_signup-->

                        <h3>Found a bug?</h3>
                        <p>
                            You can also send feature requests and report bugs directly to <a href="mailto:info@eddsm.com">info@eddsm.com</a> please.
                        </p>
                    </div>

                </article>


            </div>
        </div>
    </div>
    <div class="footer" style="display:none">
        <div class="container">
            &copy; Copyright 2015
        </div>
    </div>
</div>

    









