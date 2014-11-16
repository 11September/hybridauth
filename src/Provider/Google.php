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
class Google extends OAuth2
{
	/**
	* {@inheritdoc}
	*/
	public $scope = 'profile https://www.googleapis.com/auth/plus.profile.emails.read';

	/**
	* {@inheritdoc}
	*/
	protected $apiBaseUrl = 'https://www.googleapis.com/plus/v1/';

	/**
	* {@inheritdoc}
	*/
	protected $authorizeUrl = 'https://accounts.google.com/o/oauth2/auth';

	/**
	* {@inheritdoc}
	*/
	protected $accessTokenUrl = 'https://accounts.google.com/o/oauth2/token';

	/**
	* {@inheritdoc}
	*/
	function getUserProfile()
	{
		try
		{
			$response = $this->apiRequest( 'people/me' );

			$data = new Data\Collection( $response );
		}
		catch( Exception $e )
		{
			throw new Exception( 'User profile request failed! ' . $e->getMessage(), 6 );
		}

		$userProfile = new User\Profile();

		$userProfile->identifier  = $data->get( 'id' );
		$userProfile->firstName   = $data->filter( 'name' )->get( 'givenName' );
		$userProfile->lastName    = $data->filter( 'name' )->get( 'familyName' );
		$userProfile->displayName = $data->get( 'displayName' );
		$userProfile->photoURL    = $data->get( 'image' );
		$userProfile->profileURL  = $data->get( 'url' );
		$userProfile->description = $data->get( 'aboutMe' );
		$userProfile->gender      = $data->get( 'gender' ); 
		$userProfile->language    = $data->get( 'language' );
		$userProfile->email       = $data->get( 'email' );
		$userProfile->phone       = $data->get( 'phone' );
		$userProfile->country     = $data->get( 'country' );
		$userProfile->region      = $data->get( 'region' );
		$userProfile->zip         = $data->get( 'zip' );

		if( $data->filter( 'image' )->exists( 'url' ) )
		{
			$userProfile->photoURL = substr( $data->filter( 'image' )->get( 'url' ), 0, -2 ) . 150;
		}

		$userProfile = $this->fetchUserEmail( $userProfile, $data );

		$userProfile = $this->fetchUserProfileUrl( $userProfile, $data );

		$userProfile = $this->fetchBirthday( $userProfile, $data->get( 'birthday' ), '-' );

		$userProfile->emailVerified = $data->get( 'verified' ) ? $userProfile->email : '';

		return $userProfile;
    }

	/**
	*
	*/
	protected function fetchUserEmail( $userProfile, $data )
	{
		if( $data->exists( 'emails' ) )
		{
			foreach( $data->filter( 'emails' )->all() as $email )
			{
				if( 'account' == $email->get( 'type' ) )
				{
					$userProfile->email = $email->get( 'value' );

					break;
				}
			}
		}

		return $userProfile;
 	}

	/**
	*
	*/
	protected function fetchUserProfileUrl( $userProfile, $data )
	{
		if( $data->exists( 'urls' ) )
		{
			foreach( $data->filter( 'urls' )->all() as $url )
			{
				if( $url->get( 'primary' ) )
				{
					$userProfile->webSiteURL = $url->get( 'value' );

					break;
				}
			}
		}

		return $userProfile;
 	}

	/**
	* {@inheritdoc}
	*/
	function getUserContacts()
	{
		try
		{
			// @fixme
			$extraParams = array( "max-results" => 500 );

			// Google Gmail and Android contacts
			if( false !== strpos( $this->scope, '/m8/feeds/' ) )
			{
				return $this->getGmailContacts( $extraParams );
			}

			// Google social contacts
			if( false !== strpos( $this->scope, '/auth/plus.login' ) )
			{
				return $this->getGplusContacts( $extraParams );
			}
		}
		catch( Exception $e )
		{
			throw new Exception( 'User contacts request failed! ' . $e->getMessage(), 6 );
		}
	}

	/**
	* Retrieve Gmail contacts 
	*
	*  ..
	*/
	protected function getGmailContacts($extraParams )
	{
		$contacts = array(); 

		$url = 'https://www.google.com/m8/feeds/contacts/default/full?' . http_build_query( array_merge( array('alt' => 'json', 'v' => '3.0'), $extraParams ) );

		$response = $this->apiRequest( $url );

		$data = new Data\Collection( $response );

		if( ! $data->filter( 'feed' )->filter( 'entry' )->count() )
		{
			return $contacts;
		}

		foreach( $data->filter( 'feed' )->filter( 'entry' )->all() as $idx => $entry )
		{
			$userContact = new User\Contact();

			$userContact->email       = $entry->filter( 'gd$email' )->filter( 0 )->get( 'address' );
			$userContact->displayName = $entry->filter( 'title' )->get( '$t' );
			$userContact->identifier  = $userContact->email;

			$contacts[] = $userContact;
		}

		return $contacts;
 	}

	/**
	* Retrieve Google plus contacts 
	*
	*  ..
	*/
	protected function getGplusContacts( $extraParams )
	{
		$contacts = array(); 

		$url = 'https://www.googleapis.com/plus/v1/people/me/people/visible?' . http_build_query( $extraParams );

		$response = $this->apiRequest( $url );

		$data = new Data\Collection( $response );

		if( ! $data->filter( 'feed' )->filter( 'entry' )->count() )
		{
			return$contacts;
		}

		foreach( $data->filter( 'items' )->all() as $idx => $item )
		{
			$userContact = new User\Contact();

			$userContact->identifier  = $item->get( 'id' ); 
			$userContact->email       = $item->get( 'email' );
			$userContact->displayName = $item->get( 'displayName' );
			$userContact->description = $item->get( 'objectType' );
			$userContact->photoURL    = $item->filter( 'image' )->get( 'url' );
			$userContact->profileURL  = $item->get( 'url' );

			$contacts[] = $userContact;
		}

		return $contacts;
 	}
}
