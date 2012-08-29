<?php

	class EventController {

		function __construct() {}

		public function dispatch(&$ctx) {
			$requestURI = $ctx->requestURI;
			$ctx->path = $ctx->path . "EventController::dispatch;";


			$ctx->log->warn("reached the event controller");

			switch($ctx->method) {
				case "GET":
					return $this->getEvents($ctx);
					break;
				case "PUT":
					return $this->updateEvent($ctx);
					break;
				case "POST":
					return $this->createEvent($ctx);
					break;
				case "DELETE":
					return $this->deleteEvent($ctx);
					break;
			}

		}

		public function getEvents(&$ctx) {
			$ctx->log->debug("getting events");

			$query = "";
			$dm = new DataManager($ctx);

			$identifier = false;
			if($ctx->identifier) {
				$identifier = $dm->escape($ctx->identifier);
				$dm->addQueryParam($query, "event_id = '$identifier'");
			}

			$options = $ctx->options;
			if(count($options) > 0) {		
				if(isset($options['since_id'])) {
					$sinceId = $options['since_id'];
					$rows = $dm->getRowsByKey("events", $sinceId, "event_id");
					$id = false;
					if(count($rows) > 0) {
						$id = $rows[0]['id'];
					}
					if($id) {
						$dm->addQueryParam($query, "id > $id");
					}
				}
			}

			$query = "select * from events $query ";
			$query .= "order by event_date desc";

			$rows = $dm->queryGetRows($query);

			$response = array("events" => $rows);
			$responseJSON = json_encode($response);

			header('Content-type:application/json;charset=UTF-8');
			echo $responseJSON;
		}

		public function createEvent(&$ctx) {
			$ctx->log->debug("creating event");


			// Check the incoming parameters
			$data = $ctx->data;
			if(!isset($data['event_date']))
				$data['event_date'] = array("now()");

			if(isset($data['id']))
				unset($data['id']);

			// TODO: Check for existing item & update it
			$dm = new DataManager($ctx);
			$response = $dm->insertRow("events", $data, false, $data);
				
			if($response) {
				$id = $response->insert_id;
				$indexedRows = $dm->getRows("events", $id);
				$rows = array_values($indexedRows);
				// Use singular event key for one event returned
				$response = array("events" => $rows);
				$responseJSON = json_encode($response);
				header('Content-type:application/json;charset=UTF-8');
				$ctx->log->debug("RESPONSE: $responseJSON");
				echo $responseJSON;
			}
			else {
				echo "Insert failed";
				$ctx->log->error("Bad response for insert");
				// TODO: send back error
			}
		}

		public function updateEvent(&$ctx) {
			$ctx->log->debug("updating event");

			$response = false;
			$id = false;

			// Check the incoming parameters
			$data = $ctx->data;
			if(!isset($data['event_date']))
				$data['event_date'] = array("now()");

			if(isset($data['id'])) {
				$id = $data['id'];
				unset($data['id']);
			}

			if(isset($data['event_id'])) {
				unset($data['event_id']);
			}

			if($ctx->identifier) {
				$dm = new DataManager($ctx);
				$rows = $dm->getRowsByKey("events", $ctx->identifier, "event_id");
				$id = false;
				if(count($rows) > 0) {
					$id = $rows[0]['id'];
				}
			}

			if($id) {
				$response = $dm->updateRows("events", $data, array($id));
			}

			if($response && $id) {
				$indexedRows = $dm->getRows("events", $id);
				$rows = array_values($indexedRows);
				$response = array("events" => $rows);
				
				$responseJSON = json_encode($response);
				header('Content-type:application/json;charset=UTF-8');
				echo $responseJSON;

			}
			else {
				echo "Update failure";
			}

		}

		public function deleteEvent(&$ctx) {

			$ctx->log->debug("deleting event");

		}




	};

?>