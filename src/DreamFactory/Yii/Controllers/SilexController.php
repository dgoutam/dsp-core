<?php
namespace DreamFactory\Yii\Controllers;

use DreamFactory\Yii\Events\ComponentEvent;
use DreamFactory\Yii\Events\ControllerEvent;
use Kisma\Core\Interfaces\HttpMethod;
use Kisma\Core\Seed;
use Kisma\Core\Utility\Inflector;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller
 * The base class for controllers
 *
 * Provides two event handlers:
 *
 * before_action and after_action which are called before and after the action is called, respectively.
 */
abstract class SilexController extends Seed implements ControllerProviderInterface
{
	//*************************************************************************
	//* Private Members
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
	 * @var string The name of this controller
	 */
	protected $_controllerName = null;
	/**
	 * @var string Storage key for this controller
	 */
	protected $_tag = null;
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
	 * @var \Silex\Application
	 */
	protected $_app = null;

	//*************************************************************************
	//* Public Methods
	//*************************************************************************

	/**
	 * actions requests to actions
	 *
	 * @param \Kisma|\Silex\Application $app
	 *
	 * @return ControllerCollection A ControllerCollection instance
	 */
	public function connect( Application $app )
	{
		$_tag = $this->_tag;
		$_defaultRoute = null;
		/** @var $_controllers ControllerCollection */
		$_controllers = $app['controller_factory'];
		$_actions = $this->_discoverActions();

		//	Set the controller into the app
		$app[$_tag] = $this->setApp( $app );

		//	Event handlers...
		$this->on( static::AfterConstruct, array( $this, 'onAfterConstruct' ) );
		$this->on( ControllerEvent::BeforeAction, array( $this, 'onBeforeAction' ) );
		$this->on( ControllerEvent::AfterAction, array( $this, 'onAfterAction' ) );

		//	Set up a route for each discovered action...
		foreach ( $_actions as $_action => $_method )
		{
			//	Build the route, along with default if specified...
			$_route = ( '/' != $_action ? '/' . $_action . '/' : '/' );

			$_controllers->match(
				$_route,
				function ( Application $app, Request $request ) use ( $_action, $_method, $_tag )
				{
					$_event = new ControllerEvent( $app[$_tag] );

					/** @var $app SilexController[] */
					$app[$_tag]->setIsPost( ( HttpMethod::Post == $request->getMethod() ) )->publish( ControllerEvent::BeforeAction, $_event );

					$_event->setResult(
						$_result = call_user_func( array( $app[$_tag], $_method ), $app, $request )
					);

					$app[$_tag]->publish( ControllerEvent::AfterAction, $_event );

					return $_result;
				}
			);
		}

		//	Return the collection...
		return $_controllers;
	}

	/**
	 * @return array
	 */
	protected function _discoverActions()
	{
		if ( null !== $this->_actions )
		{
			return $this->_actions;
		}

		$_actions = array();
		$_mirror = new \ReflectionClass( $this );

		foreach ( $_mirror->getMethods( \ReflectionMethod::IS_PUBLIC ) as $_method )
		{
			if ( 'action' == strtolower( substr( $_method->name, strlen( $_method->name ) - 6, 6 ) ) && 'on' != strtolower( substr( $_method->name, 0, 2 ) ) )
			{
				$_routeName = lcfirst( Inflector::camelize( str_ireplace( 'Action', null, $_method->name ) ) );

				$_actions[$_routeName] = $_method->name;

				//	Add a default action/route to the discovered list if wanted
				if ( !empty( $this->_defaultAction ) && 0 == strcasecmp( $this->_defaultAction, $_routeName ) )
				{
					$_actions['/'] = $_method->name;
				}
			}
		}

		$this->setControllerName(
			lcfirst( Inflector::camelize( str_ireplace( array( 'ControllerProvider', 'Controller' ), null, $_mirror->getShortName() ) ) )
		);

		return $this->_actions = $_actions;
	}

	//*************************************************************************
	//* Event Handlers
	//*************************************************************************

	/**
	 * @param ComponentEvent $event
	 *
	 * @return bool
	 */
	public function onAfterConstruct( ComponentEvent $event )
	{
		$this->_discoverActions();

		$this->_app = \Kisma::get( 'silex.app' );
		$this->setTag( 'controller.' . $this->_controllerName );
		$this->_tag = 'controller.' . $this->_controllerName;
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
	 * @param string $controllerName
	 *
	 * @return $this
	 */
	public function setControllerName( $controllerName )
	{
		$this->_controllerName = $controllerName;
		$this->_tag = 'controller.' . $controllerName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getControllerName()
	{
		return $this->_controllerName;
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