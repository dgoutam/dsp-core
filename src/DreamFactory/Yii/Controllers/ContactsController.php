<?php
/**
 * ContactsController.php
 * Confidential and Proprietary Code / Do Not Distribute
 *
 * Copyright 2011, StarPound Technologies, Inc.
 *
 * @copyright	 Copyright 2011, StarPound Technologies, Inc.
 * @package	   starpound.controllers
 * @filesource
 */
namespace Teledini\Controllers
{
	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\HttpFoundation\Response;
	use Silex\Application;
	use Silex\ControllerProviderInterface;
	use Silex\ControllerCollection;

	/**
	 * ContactsController
	 * Manages requests for individual contact storage
	 */
	class ContactsController extends \Teledini\Services\Storage\ContactStore implements \Silex\ControllerProviderInterface
	{
		//*************************************************************************
		//* Interface Requirements
		//*************************************************************************

		/**
		 * @param \Silex\Application $app
		 * @return \Silex\ControllerCollection
		 * @throws \InvalidArgumentException
		 */
		public function connect( Application $app )
		{
			$_controllers = new ControllerCollection();

			//*************************************************************************
			//* GET
			//*************************************************************************

			$_controllers->get( '/{id}', /**
				 * @param Application $app
				 * @param string $id
				 * @return \Symfony\Component\BrowserKit\Response|\Symfony\Component\HttpFoundation\Response
				 */
				function ( Application $app, $id )
				{
					if ( !isset( $id ) )
					{
						throw new \InvalidArgumentException( 'You must specify an "id".' );
					}

					//	Have to base64 encode id cuz it's funky
					$_id = base64_decode( urldecode( $id ) );

					\Kisma\Utility\Log::trace( 'get /contact/' . $_id );

					$_controller = new ContactsController();

					if ( null !== ( $_result = $_controller->findById( $_id ) ) )
					{
						return $_controller->createResponse( $_id, $_result );
					}

					return new Response( 'you suck Not found' );
				} );

			//*************************************************************************
			//* POST
			//*************************************************************************

			$_controllers->post( '/', /**
				 * @param Application $app
				 * @return \Symfony\Component\BrowserKit\Response|\Symfony\Component\HttpFoundation\Response
				 */
				function ( Application $app )
				{
					$_returnUrl = $app['request']->get( 'return_url' );
					$_id = $app['request']->get( 'id' );

					if ( !isset( $_id ) )
					{
						throw new \InvalidArgumentException( 'You must specify an "id".' );
					}

					$_id = base64_decode( urldecode( $_id ) );

					\Kisma\Utility\Log::trace( 'post /contact/' . ( $_id ? : '[NEW]' ) . print_r( $_POST, true ) );

					$_controller = new ContactsController();

					if ( null === ( $_result = $_controller->findById( $_id ) ) )
					{
						//	Ajax request? Return a JSON response
						if ( 'XMLHttpRequest' == $_SERVER['HTTP_X_REQUESTED_WITH'] )
						{
							return $_controller->createResponse( $_id, 'Contact not found!', 404 );
						}

						return 'Contact not found!';
					}

					//	Copy to id as well
					$_POST['id'] = $_id;

					try
					{
						$_result = $_controller->updateContact( new \Teledini\Components\Contacts\GenericContact( $_POST ) );
						\Kisma\Utility\Log::trace( 'post result: ' . print_r( $_result, true ) );
						$_SESSION['contact_post_result'] = $_result;
					}
					catch ( \Exception $_ex )
					{
						//	Error
						\Kisma\Utility\Log::error( 'Exception saving contact: ' . $_ex->getMessage() );
					}

					//	Ajax request? Return a JSON response
					if ( 'XMLHttpRequest' == $_SERVER['HTTP_X_REQUESTED_WITH'] )
					{
						return $_controller->createResponse( $_id, 'Contact updated.' );
					}

					if ( $_returnUrl )
					{
						//	Redirect
						header( 'Location: ' . $_returnUrl );
						die();
					}
				} );

			//*************************************************************************
			//* DELETE
			//*************************************************************************

			$_controllers->delete( '/{id}', /**
				 * @param Application $app
				 * @param string $id
				 * @return \Symfony\Component\BrowserKit\Response|\Symfony\Component\HttpFoundation\Response
				 */
				function ( Application $app, $id )
				{
					$_returnUrl = $app['request']->get( 'return_url' );
					$_id = $app['request']->get( 'id' );

					if ( !isset( $_id ) )
					{
						throw new \InvalidArgumentException( 'You must specify an "id".' );
					}

					$_id = base64_decode( urldecode( $_id ) );

					\Kisma\Utility\Log::trace( 'post /contact/' . ( $_id ? : '[NEW]' ) . print_r( $_POST, true ) );

					$_controller = new ContactsController();

					if ( null === ( $_result = $_controller->findById( $_id ) ) )
					{
						//	Ajax request? Return a JSON response
						if ( 'XMLHttpRequest' == $_SERVER['HTTP_X_REQUESTED_WITH'] )
						{
							return $_controller->createResponse( $_id, 'Contact not found!', 404 );
						}

						return 'Contact not found!';
					}

					//	Copy to id as well
					$_POST['id'] = $_id;

					try
					{
						$_result = $_controller->updateContact( new \Teledini\Components\Contacts\GenericContact( $_POST ) );
						\Kisma\Utility\Log::trace( 'post result: ' . print_r( $_result, true ) );
						$_SESSION['contact_post_result'] = $_result;
					}
					catch ( \Exception $_ex )
					{
						//	Error
						\Kisma\Utility\Log::error( 'Exception saving contact: ' . $_ex->getMessage() );
					}

					//	Ajax request? Return a JSON response
					if ( 'XMLHttpRequest' == $_SERVER['HTTP_X_REQUESTED_WITH'] )
					{
						return $_controller->createResponse( $_id, 'Contact updated.' );
					}

					if ( $_returnUrl )
					{
						//	Redirect
						header( 'Location: ' . $_returnUrl );
						die();
					}
				} );

			return $_controllers;
		}

		/**
		 * @param Application $app
		 * @return \Symfony\Component\BrowserKit\Response|\Symfony\Component\HttpFoundation\Response
		 */
		protected function _get( $app )
		{
		}

		protected function _post( $app )
		{
		}

		/**
		 * @param string $id
		 * @param string|mixed|null $response
		 * @param int $responseCode
		 * @param string|null $errorMessage
		 * @internal param int|null|string $errorCode
		 * @return \Symfony\Component\HttpFoundation\Response
		 */
		public function createResponse( $id, $response = null, $responseCode = 200, $errorMessage = null )
		{
			$_result = array(
				'id' => $id,
				'response' => $response,
				'error' => $errorMessage ? : null,
				'code' => $responseCode ? : null,
			);

			return new Response( json_encode( $_result ), $responseCode, array( 'Content-type' => 'application/json' ) );
		}

		//*************************************************************************
		//* Public Actions
		//*************************************************************************

		/**
		 * @param $parentId
		 * @return array
		 */
		public function findAllByParentId( $parentId )
		{
			$_results = array();

			\Kisma\Utility\Log::trace( 'Getting contact list for parent: ' . $parentId );

			//	Construct query to get by parent and sorted
			$_items = $this->getView( '/_design/document/_view/by_parent_id', $parentId );/* array(
					$parentId
				), array(
					$parentId,
					'{}'
				)
			);*/

			if ( isset( $_items, $_items->body, $_items->body->rows ) && !empty( $_items->body->rows ) )
			{
				foreach ( $_items->body->rows as $_row )
				{
					$_results[] = array(
						'parent_id' => $_row->key[0],
						'id' => $_row->id,
						'encoded_id' => base64_encode( $_row->id ),
						'display_name' => $_row->value->displayName,
					);
				}
			}

			\Kisma\Utility\Log::trace( 'Pulled contacts for user "' . $parentId . '". ' . count( $_results ) . ' row(s) returned.' );

			return $_results;
		}

		/**
		 * @param string $id
		 * @return \stdClass
		 */
		public function findById( $id )
		{
			//	Get single contact
			$_key = $this->createKey( $id );
			$_contact = $this->get( $_key );

			if ( isset( $_contact, $_contact->body ) && 404 !== $_contact->status )
			{
				\Kisma\Utility\Log::trace( 'Found id "' . $id . '" with key "' . $_key . '"' );
				return $_contact->body;
			}

			\Kisma\Utility\Log::trace( 'Did NOT find id "' . $id . '" with key "' . $_key . '"' );
			return null;
		}

		/**
		 * Stores a contact
		 *
		 * @param $data
		 * @return mixed
		 */
		public function update( $data )
		{
			//	Inbound contact
			$_contactData = \Kisma\Utility\FilterInput::get( INPUT_POST, 'contact_data', array() );

			if ( empty( $_contact ) )
			{
				throw new \HttpException( 'Bad request', 400 );
			}

			//	Save contact to outbound queue...
			$_contact = new \Teledini\Components\Contacts\GenericContact( $_contactData );
			$_contact->save();
			$_store = new \Teledini\Services\Storage\ContactStore();
			return $_store->put( $_contact->getId(), $_contact->getDocument() );
		}

	}
}
