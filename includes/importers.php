<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get random date
 *
 * @param  integer $days_from
 * @param  integer $days_to
 * @return string date
 */
function rda_get_random_date( $days_from = 30, $days_to = 0 ) {
    // 1 day in seconds is 86400
    $from = $days_from * rand( 10000, 99999 );

    // $days_from should always be less than $days_to
    if ( $days_to > $days_from ) {
        $days_to = $days_from - 1;
    }

    $to        = $days_to * rand( 10000, 99999 );
    $date_from = time() - $from;
    $date_to   = time() - $to;

    return date( 'Y-m-d H:i:s', rand( $date_from, $date_to ) );
}
