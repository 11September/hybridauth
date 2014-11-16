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
class GitHub extends OAuth2
{
	/**
	* {@inheritdoc}
	*/
	public $scope = 'user:email';

	/**
	* {@inheritdoc}
	*/
	protected $apiBaseUrl = 'https://api.github.com/';

	/**
	* {@inheritdoc}
	*/
	protected $authorizeUrl = 'https://github.com/login/oauth/authorize';

	/**
	* {@inheritdoc}
	*/
	protected $accessTokenUrl = 'https://github.com/login/oauth/access_token';

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
			throw new Exception( 'User profile request failed! ' . $e->getMessage() );
		}

		$userProfile = new User\Profile();

		$userProfile->identifier  = $data->get( 'id' );
		$userProfile->displayName = $data->get( 'name' );
		$userProfile->description = $data->get( 'bio' );
		$userProfile->photoURL    = $data->get( 'avatar_url' );
		$userProfile->profileURL  = $data->get( 'html_url' );
		$userProfile->email       = $data->get( 'email' );
		$userProfile->webSiteURL  = $data->get( 'blog' );
		$userProfile->region      = $data->get( 'location' );

		$userProfile->displayName = $userProfile->displayName ? $userProfile->displayName : $data->get( 'login' );

		if( empty( $userProfile->email ) && strpos( $this->scope, 'user:email' ) !== false )
		{
			$userProfile = $this->requestUserEmail( $userProfile );
		}

		return $userProfile;
	}

	/**
	*
	* https://developer.github.com/v3/users/emails/
	*/
	protected function requestUserEmail( $userProfile )
	{
		try
		{
			$response = $this->apiRequest( 'user/emails' );

			foreach( $response as $idx => $item )
			{
				if( ! empty( $item->primary ) && $item->primary == 1 )
				{
					$userProfile->email = $item->email;

					if( ! empty( $item->verified ) && $item->verified == 1 )
					{
						$userProfile->emailVerified = $userProfile->email;
					}

					break;
				}
			}
		}

		// user email is not mandatory so we keep it quite
		catch( Exception $e ){}

		return $userProfile;
 	}	
}
