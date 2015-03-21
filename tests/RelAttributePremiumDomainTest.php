<?php
require_once('utils.php');

if (class_exists('WPMPTestsUtils')) {
	
	class RelAttributePremiumDomainTest extends WP_UnitTestCase {
	
		protected $old_current_user;
		protected static $test_subdomain = 'myapp.domaintest.com';
		
		function setUp() {
			
			parent::setUp();
			
			// create admin user that can modify the plugin settings
			$this->old_current_user = get_current_user_id();
			
			$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
			wp_set_current_user( $user_id );
			
			// enable connection with the API key
			$arrData = array(
				'premium_api_key' => 'apikeytest',
				'premium_active'  => 1
			);
				
			WMobilePack::wmp_update_settings($arrData);
		}
		
		
		function tearDown(){
			
			// disable connection with the API key
			$arrData = array(
				'premium_api_key' => '',
				'premium_active'  => 0
			);
			
			// save options
			WMobilePack::wmp_update_settings($arrData);
			
			wp_set_current_user( $this->old_current_user );
			
			parent::tearDown();
		}
		
		
		/**
		 *
		 * Rel alternate as {domain_url}/#articleUrl/{encoded_article_url}
		 *
		 */
		function test_post() {
			
			if (WMobilePack::wmp_get_setting('premium_active') == 1 && WMobilePack::wmp_get_setting('premium_api_key') != '') {
			
				$post_id = 1241;
				$request_url = WPMPTestsUtils::get_furl(home_url().'/?p='.$post_id);
	
				$response = WPMPTestsUtils::make_request($request_url, false);
				
				$encoded_url = rawurlencode($request_url);
				$encoded_url = str_replace('.','%2E',$encoded_url);
				
				$rel_tag = '<link rel="alternate" media="only screen and (max-width: 640px)" href="http://'.self::$test_subdomain.'/#articleUrl/'.$encoded_url.'" />';
				
				$this->assertContains($rel_tag, $response['content']);
			}
		}
		
		
		
		/**
		 *
		 * Rel alternate as {domain_url}/#pageUrl/{encoded_page_url}
		 *
		 */
		function test_page() {
			
			if (WMobilePack::wmp_get_setting('premium_active') == 1 && WMobilePack::wmp_get_setting('premium_api_key') != '') {
			
				$page_id = 1086;
				$request_url = WPMPTestsUtils::get_furl(home_url().'/?page_id='.$page_id);
				
				$response = WPMPTestsUtils::make_request($request_url, false);
				
				$encoded_url = rawurlencode($request_url);
				$encoded_url = str_replace('.','%2E',$encoded_url);
				
				$rel_tag = '<link rel="alternate" media="only screen and (max-width: 640px)" href="http://'.self::$test_subdomain.'/#pageUrl/'.$encoded_url.'" />';
				
				$this->assertContains($rel_tag, $response['content']);
			}
		}
		
	}
}

