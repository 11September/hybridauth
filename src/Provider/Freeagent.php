<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception;
use Hybridauth\Data;
use Hybridauth\User;

/**
 *
 */
class Freeagent extends OAuth2
{
	/**
	* {@inheritdoc}
	*/
	protected $apiBaseUrl = 'https://api.freeagent.com/v2/';

	/**
	* {@inheritdoc}
	*/
	protected $authorizeUrl = 'https://api.freeagent.com/v2/approve_app';

	/**
	* {@inheritdoc}
	*/
	protected $accessTokenUrl = 'https://api.freeagent.com/v2/token_endpoint';

	/**
	* {@inheritdoc}
	*/
	protected function initialize() 
	{
		parent::initialize();

		$this->apiRequestParameters = array( 'Authorization' => 'Bearer ' . $this->token( "access_token" ) );
	}

	/**
	* {@inheritdoc}
	*/
	function getUserProfile()
	{
		try
		{
			$response = $this->apiRequest( 'users/me' );

			$data = new Data\Collection( $response );
		}
		catch( Exception $e )
		{
			throw new Exception( 'User profile request failed! ' . $e->getMessage(), 6 );
		}

		if( ! $data->exists( 'user' ) )
		{
			throw new Exception( 'User profile request failed! Provider returned an invalid response.', 6 );
		}

		$userProfile = new User\Profile();

		$data = $data->get( 'user' );

		$userProfile->identifier  = str_ireplace( $this->apiBaseUrl .'users/', '', $data->get( 'url' ) );
		$userProfile->description = $data->get( 'role' );
		$userProfile->email       = $data->get( 'email' ); 
		$userProfile->firstName   = $data->get( 'first_name' );
		$userProfile->lastName    = $data->get( 'last_name' );
		$userProfile->displayName = trim( $$data->get( 'first_name' ) . ' ' . $data->get( 'last_name' ) );

		return $userProfile;
	}
}
