<?php

/*
 * Author: Nabeel Sulieman
 * Description: This class handles communication with Sift
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( "WC_SiftScience_Comm" ) ) :
	require_once( 'class-wc-siftscience-options.php' );

    //need to update all the endpoints to 205
	class WC_SiftScience_Comm {
		private $options;
	    private $logger;
		private $event_url = 'https://api.sift.com/v205/events';
		private $labels_url = 'https://api.sift.com/v205/users/{user}/labels'; //private $user_dec_url = https://api.sift.com/v3/accounts/{accountId}/users/{userId}/decisions
		//private $order_dec_url = https://api.sift.com/v3/accounts/{accountId}/users/{userId}/orders/{orderId}/decision
		private $delete_url = 'https://api.sift.com/v204/users/{user}/labels/?api_key={api}&abuse_type=payment_abuse'; //Dont think I need this
		private $score_url = 'https://api.sift.com/v204/score/{user}/?api_key={api}'; //does this really need to score call all the time? probably not

		private $headers = array(
			'Accept'       => 'application/json',
			'Content-Type' => 'application/json',
		);

		public function __construct( WC_SiftScience_Options $options, WC_SiftScience_Logger $logger ) {
			$this->options = $options;
			$this->logger = $logger;
		}

		public function post_event( $data ) {
			$data[ '$api_key' ] = $this->options->get_api_key();

			$args = array(
				'headers' => $this->headers,
				'method'  => 'POST',
				'body'    => $data
			);

			return $this->send_request( $this->event_url, $args );
		}
		
		/*
		Trying to build the function for Order Level decisions to the Decisions API, using our default Payment decsion.
		 - I still need to  set the $account_id | $order_id 

		public function post_order_decision( $user_id, $decision_id, $order_id) {
			$data = array(
				'$api_key'    => $this->options->get_api_key(),
				'$decision_id' => ($decisionID ?  'order_looks_ok_payment_abuse' : 'order_looks_bad_payment_abuse' ),
				'$source' => 'manual_review'
			);

			$url = str_replace( '{accountId}', $account_id, str_replace( '{userId}', $user_id, str_replace('{orderId}', $order_id, $this->order_dec_url)));
			
			$args = array(
				'headers' => $this->headers,
				'method'  => 'POST',
				'body'    => $data
			);

			$response = $this->send_request($url, $args)

		}
		*/
		
		
		//need to edit for sending the USER LEVEL decision
		public function post_label( $user_id, $isBad ) {
			$data = array(
				'$api_key'    => $this->options->get_api_key(),
				'$is_bad'     => ( $isBad ? 'true' : 'false' ), //'$is_bad'     => ( $isBad ? 'true' : 'false' ),
				'$abuse_type' => 'payment_abuse'
			);

			$url = str_replace( '{user}' , urlencode( $user_id ), $this->labels_url ); //$this->decisions_url
			$args = array(
				'headers' => $this->headers,
				'method'  => 'POST',
				'body'    => $data
			);

			$response = $this->send_request( $url, $args );

			return $response;
		}
			//needs to be updated for Decision Status
		public function delete_label( $user ) {
			$api = $this->options->get_api_key();
			$url = str_replace( '{api}', $api, str_replace( '{user}', $user, $this->delete_url ) );
			$result = $this->send_request( $url, array( 'method' => 'DELETE' ) );

			return $result;
		}
			//should look at deleting this function
		public function get_user_score( $user_id ) {
			$api = $this->options->get_api_key();
			$url = str_replace( '{ap}', $api, str_replace( '{user}', $user_id, $this->score_url ) ); //broken api link

			$response = $this->send_request( $url );

			return json_decode( $response['body'] );
		}

		private function send_request( $url, $args = array() ) {
			$this->logger->log_info( "Sending Request to Sift API: $url" );
			$this->logger->log_info( $args );
			if ( ! isset( $args['method'] ) )
				$args['method'] = 'GET';

			$args['timeout'] = 10;

			if ( isset( $args['body'] ) && ! is_string( $args['body'] ) ) {
				$args['body'] = json_encode( $args['body'] );
			}

			$result = wp_remote_request( $url, $args );
			$this->logger->log_info( $result );
			return $result;
		}
	}

endif;
