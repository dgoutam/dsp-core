<?php
namespace DreamFactory\Yii\Controllers;

use DreamFactory\Yii\Interfaces\ControllerLike;
use Kisma\Core\Seed;
use Kisma\Core\Utility\Inflector;

/**
 * ClosureController.php
 * The base class for routing calls in frameworks like Silex, Slim, Laravel, etc.
 *
 * Provides two event handlers:
 *
 * pre_process and post_process which are called before and after the action is called, respectively.
 */
abstract class ClosureController extends Seed implements ControllerLike
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var string The default action when none specified
	 */
	protected $_defaultAction = 'index';
	/**
	 * @var array The actions of this controller
	 */
	protected $_actions = null;
	/**
	 * @var null|array The custom routes for this controller
	 * @todo implement
	 */
	protected $_routes = null;
	/**
	 * @var bool True if request was a post vs. get
	 */
	protected $_isPost = false;
	/**
	 * @var Application
	 */
	protected $_app = null;
	/**
	 * @var string The pattern to look for when discovering route handlers at run time. Set to false to disable auto-discovery
	 */
	protected $_discoveryPattern = 'route';

	//*************************************************************************
	//* Public Methods
	//*************************************************************************

	/**
	 * actions requests to actions
	 *
	 * @param \DreamFactory\Yii\Controllers\Application|\Silex\Application $app
	 *
	 * @return ControllerCollection
	 */
	public function connect( Application $app )
	{
		$_tag = $this->_tag;
		$_defaultRoute = null;
		$_controllers = array();
		$_actions = $this->_discoverActions();

//		//	Set the controller into the app
//		$app[$_tag] = $this->setApp( $app );
//
//		//	Set up a route for each discovered action...
//		foreach ( $_actions as $_action => $_method )
//		{
//			//	Build the route, along with default if specified...
//			$_route = ( '/' != $_action ? '/' . $_action . '/' : '/' );
//
//			$_controllers->match( $_route,
//				function ( Application $app, Request $request ) use ( $_action, $_method, $_tag )
//				{
//					$_event = new \Kisma\Event\ControllerEvent( $app[$_tag] );
//
//					$app[$_tag]->setIsPost(
//						( \Kisma\HttpMethod::Post == $request->getMethod() )
//					)->dispatch( \Kisma\Event\ControllerEvent::BeforeAction, $_event );
//
//					$_event->setResult(
//						$_result = call_user_func( array( $app[$_tag], $_method ), $app, $request )
//					);
//
//					$app[$_tag]->dispatch(
//						\Kisma\Event\ControllerEvent::AfterAction,
//						$_event
//					);
//
//					return $_result;
//				}
//			);
//		}

		//	Return the collection...
		return $_controllers;
	}

	/**
	 * Discover the actions defined in a particular controller.
	 *
	 * @return array
	 */
	protected function _discoverActions()
	{
		//	Only once per run
		if ( null !== $this->_actions )
		{
			return $this->_actions;
		}

		$_actions = array();

		$_mirror = new \ReflectionClass( $this );
		$_space = strlen( $this->_discoveryPattern );
		$_pattern = trim( strtolower( $this->_discoveryPattern ) );

		foreach ( $_mirror->getMethods( \ReflectionMethod::IS_PUBLIC ) as $_method )
		{
			$_compare = trim(
				strtolower(
					substr( $_method->name, strlen( $_method->name ) - $_space, $_space )
				)
			);

			if ( $_compare == $_pattern )
			{
				$_routeName =
					lcfirst( Inflector::camelize( str_ireplace( $_pattern, null, $_method->name ) ) );

				$_actions[$_routeName] = $_method->name;

				//	Add a default action/route to the discovered list if wanted
				if ( !empty( $this->_defaultAction ) && 0 == strcasecmp( $this->_defaultAction, $_routeName ) )
				{
					$_actions['/'] = $_method->name;
				}
			}
		}

		$this->setName(
			lcfirst(
				Inflector::camelize(
					str_ireplace(
						array( 'ControllerProvider', 'Controller' ),
						null,
						$_mirror->getShortName()
					)
				)
			)
		);

		return $this->_actions = $_actions;
	}

	//*************************************************************************
	//* Properties
	//*************************************************************************

	/**
	 * @param array $actions
	 *
	 * @return $this
	 */
	public function setActions( $actions )
	{
		$this->_actions = $actions;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getActions()
	{
		return $this->_actions;
	}

	/**
	 * @param array|null $routes
	 *
	 * @return $this
	 */
	public function setRoutes( $routes )
	{
		$this->_routes = $routes;

		return $this;
	}

	/**
	 * @return array|null
	 */
	public function getRoutes()
	{
		return $this->_routes;
	}

	/**
	 * @param boolean $isPost
	 *
	 * @return $this
	 */
	public function setIsPost( $isPost )
	{
		$this->_isPost = $isPost;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getIsPost()
	{
		return $this->_isPost;
	}

	/**
	 * @return boolean
	 */
	public function isPost()
	{
		return $this->_isPost;
	}

	/**
	 * @param string $defaultAction
	 *
	 * @return $this
	 */
	public function setDefaultAction( $defaultAction )
	{
		$this->_defaultAction = $defaultAction;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDefaultAction()
	{
		return $this->_defaultAction;
	}

	/**
	 * @param \Silex\Application $app
	 *
	 * @return $this
	 */
	public function setApp( $app )
	{
		$this->_app = $app;

		return $this;
	}

	/**
	 * @return \Silex\Application
	 */
	public function getApp()
	{
		return $this->_app;
	}

	/**
	 * @param string $tag
	 *
	 * @return $this
	 */
	public function setTag( $tag )
	{
		$this->_tag = $tag;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTag()
	{
		return $this->_tag;
	}
}