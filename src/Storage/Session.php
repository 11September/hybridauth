<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

namespace Hybridauth\Storage;

use Hybridauth\Exception\RuntimeException;
use Hybridauth\Deprecated\DeprecatedStorageTrait;

/**
 * HybridAuth storage manager
 */
class Session implements StorageInterface
{
	use DeprecatedStorageTrait;

	/**
	* Initiate a new session
	*
	* @throws RuntimeException
	*/
	function __construct()
	{
		if( session_id() )
		{
			return true;
		}

		if( headers_sent() )
		{
			throw new RuntimeException( 'Hybridauth wasn\'t able to start PHP session. HTTP headers already sent.' );
		}

		if( ! session_start() )
		{
			throw new RuntimeException( 'PHP session failed to start.' );
		}
	}

	/**
	* {@inheritdoc}
	*/
	function get( $key ) 
	{
		$key = 'hauth_session.' . strtolower( $key );

		if( isset( $_SESSION["HA::STORE"], $_SESSION["HA::STORE"][$key] ) )
		{ 
			return unserialize( $_SESSION["HA::STORE"][$key] );
		}

		return null;
	}

	/**
	* {@inheritdoc}
	*/
	function set( $key, $value )
	{
		$key = 'hauth_session.' . strtolower( $key );

		$_SESSION["HA::STORE"][$key] = serialize( $value );
	}

	/**
	* {@inheritdoc}
	*/
	function clear()
	{ 
		$_SESSION["HA::STORE"] = [];
	}

	/**
	* {@inheritdoc}
	*/
	function delete( $key )
	{
		$key = 'hauth_session.' . strtolower( $key );

		if( isset( $_SESSION["HA::STORE"], $_SESSION["HA::STORE"][$key] ) )
		{
			$tmp = $_SESSION['HA::STORE'];

			unset( $tmp[$key] );

			$_SESSION["HA::STORE"] = $tmp;
		} 
	}

	/**
	* {@inheritdoc}
	*/
	function deleteMatch( $key )
	{
		$key = 'hauth_session.' . strtolower( $key );

		if( isset( $_SESSION["HA::STORE"] ) && count( $_SESSION["HA::STORE"] ) )
		{
			$tmp = $_SESSION['HA::STORE'];

			foreach( $tmp as $k => $v )
			{ 
				if( strstr( $k, $key ) )
				{
					unset( $tmp[ $k ] ); 
				}
			}

			$_SESSION["HA::STORE"] = $tmp;
		}
	}
}
