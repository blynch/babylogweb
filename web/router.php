<?php

  require_once('config/Configuration.php');

  $config = new Configuration();

  // Create a context to inject into the request (dependency injection vs. Singletons)
  $ctx = new Context();
  $ctx->config = $config;

  $log = Logger::getLogger("appname");
  $ctx->log = $log;

  // Grab the incoming url
  $requestURI = $_SERVER['REQUEST_URI'];

  $routes = $config->get("_ROUTES");

  // Strip the query params
  $queryPosition = strpos($requestURI, "?", 0);
  if($queryPosition)
    $requestURI = substr($requestURI, 0, $queryPosition);

  $position = strpos($requestURI, "/", 1);
  $identifier = false;
  if($position) {
  	$requestRoot = substr($requestURI, 0, $position);
    $identifier = substr($requestURI, $position + 1);
  }
  else
  	$requestRoot = $requestURI;

  $postdata = file_get_contents("php://input");
  if($postdata) {
    $data = json_decode($postdata, true);
    $data['user_id'] = 1;
    $ctx->data = $data;
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
           $ctx->log->debug('JSON - No errors');
        break;
        case JSON_ERROR_DEPTH:
            $ctx->log->error('JSON  - Maximum stack depth exceeded');
        break;
        case JSON_ERROR_STATE_MISMATCH:
            $ctx->log->error('JSON - Underflow or the modes mismatch');
        break;
        case JSON_ERROR_CTRL_CHAR:
            $ctx->log->error('JSON - Unexpected control character found');
        break;
        case JSON_ERROR_SYNTAX:
            $ctx->log->error('JSON - Syntax error, malformed JSON');
            $ctx->log->error($postdata);
        break;
        case JSON_ERROR_UTF8:
            $ctx->log->error('JSON - Malformed UTF-8 characters, possibly incorrectly encoded');
        break;
    }
  }

  $ctx->identifier = $identifier;

  $ctx->requestURI = $requestURI;
  $ctx->requestRoot = $requestRoot;

  $ctx->path = "router.php;";
  $ctx->method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : "GET";

  $ctx->log->debug(serialize($ctx));
  $ctx->log->debug("Request URI: $requestURI");
  //$ctx->log->debug(serialize($_SERVER));

  $queryString = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : "";

  if(strlen($queryString) > 0) {
    parse_str($queryString, $params);
    $ctx->options = $params;
  }

  $checkURI = "web".$requestURI;
  if(array_key_exists($requestRoot, $routes)) {
    $class = $routes[$requestRoot];
    $controller = new $class;
    $controller->dispatch($ctx);

  }
  else if(strlen($requestURI) > 1 && file_exists($checkURI)) {
    require($checkURI);
  }
  else { // Hand it off to the default handler
    $controller = new DefaultController;
    $controller->dispatch($ctx);
  }

  //$ctx->log->warn("Path followed: ".$ctx->path);

  
?>
