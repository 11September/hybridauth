<?php
/**
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
 * Reddit
 *
 * http://www.reddit.com/dev/api/oauth
 * https://github.com/reddit/reddit/wiki/OAuth2
 */
final class Reddit extends OAuth2
{
	/**
	* {@inheritdoc}
	*/
	protected $scope = "identity";

	/**
	* {@inheritdoc}
	*/
	protected $apiBaseUrl = 'https://oauth.reddit.com/api/v1/';

	/**
	* {@inheritdoc}
	*/
	protected $authorizeUrl = 'https://ssl.reddit.com/api/v1/authorize';

	/**
	* {@inheritdoc}
	*/
	protected $accessTokenUrl = 'https://ssl.reddit.com/api/v1/access_token';

	/**
	* {@inheritdoc}
	*/
	protected function initialize() 
	{
		parent::initialize();

		$this->tokenExchangeParameters = array(
			"client_id"     => $this->clientId,
			"grant_type"    => "authorization_code",
			"redirect_uri"  => $this->endpoint
		);

		$this->tokenExchangeHeaders = array( 'Authorization' => 'Basic ' . base64_encode( $this->clientId .  ':' . $this->clientSecret ) );

		$this->apiRequestHeaders = array( 'Authorization' => 'Bearer ' . $this->token( "access_token" ) );
	}

	/**
	* {@inheritdoc}
	*/
	protected function getAuthorizeUrl( $parameters = array() )
	{
		$addtionals = array( 'duration' => 'temporary' );

		$parameters = array_replace( $parameters, (array) $addtionals );

		return parent::getAuthorizeUrl( $parameters );
	}

	/**
	* {@inheritdoc}
	*/
	function getUserProfile()
	{ 
	print_r( $this->apiRequestHeaders  );
	print_r( $_SESSION  );
	print_r( $this->token( "access_token" ) . '---' );
		try
		{
			$response = $this->apiRequest( 'me.json' );

			$data = new Data\Collection( $response );
		}
		catch( Exception $e )
		{
			throw new Exception( 'User profile request failed! ' . $e->getMessage(), 6 );
		}

		$userProfile = new User\Profile();

		$userProfile->identifier  = $data->get( 'id' );
		$userProfile->displayName = $data->get( 'name' );
		$userProfile->profileURL  = 'https://www.reddit.com/user/' . $data->get( 'name' ) . '/';

		return $userProfile;
	}
}
