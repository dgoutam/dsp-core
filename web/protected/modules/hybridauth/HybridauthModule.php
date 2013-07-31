<?php
require_once \Kisma::get( 'app.vendor_path' ) . '/hybridauth/hybridauth/hybridauth/Hybrid/Auth.php';

/**
 * HybridauthModule
 */
class HybridauthModule extends \CWebModule
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var string
	 */
	protected $_baseUrl;
	/**
	 * @var array
	 */
	protected $_providers;
	/**
	 * @var string
	 */
	protected $_assetsUrl = '/protected';
	/**
	 * @var \Hybrid_Auth
	 */
	protected $_hybridAuth;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * {@InheritDoc}
	 */
	public function init()
	{
		$this->_hybridAuth = new \Hybrid_Auth( $this->getConfig() );
	}

	/**
	 * Convert configuration to an array for Hybrid_Auth, rather than object properties as supplied by Yii
	 *
	 * @return array
	 */
	public function getConfig()
	{
		return array(
			'baseUrl'   => $this->_baseUrl,
			'base_url'  => $this->_baseUrl . '/default/callback', // URL for Hybrid_Auth callback
			'providers' => $this->_providers,
		);
	}
}
