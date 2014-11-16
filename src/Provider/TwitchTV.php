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
class TwitchTV  extends OAuth2
{
	/**
	* {@inheritdoc}
	*/
	protected $scope = 'user_read channel_read';

	/**
	* {@inheritdoc}
	*/
	protected $apiBaseUrl = 'https://api.twitch.tv/kraken/';

	/**
	* {@inheritdoc}
	*/
	protected $authorizeUrl = 'https://api.twitch.tv/kraken/oauth2/authorize';

	/**
	* {@inheritdoc}
	*/
	protected $accessTokenUrl = 'https://api.twitch.tv/kraken/oauth2/token';

	/**
	* {@inheritdoc}
	*/
	protected $accessTokenName = 'oauth_token';

	/**
	* {@inheritdoc}
	*/
	function getUserProfile()
	{
		try
		{
			$response = $this->apiRequest( 'user' );

			$data = new Data\Collection( $response );
		}
		catch( Exception $e )
		{
			throw new Exception( 'User profile request failed! ' . $e->getMessage(), 6 );
		}

		$userProfile = new User\Profile();

		$userProfile->identifier  = $data->get( 'id' );
		$userProfile->displayName = $data->get( 'display_name' );
		$userProfile->photoURL    = $data->get( 'logo' );
		$userProfile->email       = $data->get( 'email' );

		$userProfile->profileURL = 'http://www.twitch.tv/' . $data->get( 'name' );

		$userProfile->displayName = $userProfile->displayName ? $userProfile->displayName : $data->get( 'name' );

		return $userProfile;
	}
}
