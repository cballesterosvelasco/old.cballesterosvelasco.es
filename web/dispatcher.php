<?php

date_default_timezone_set('Europe/Madrid');

require_once(__DIR__ . '/render.php');
require_once(__DIR__ . '/cache.php');

$requestUri = @$_SERVER['REQUEST_URI'];

if (preg_match('@^/(i|iicon)/@', $requestUri)) {
	return false;
}

switch ($requestUri) {
	case '/':
		header('Location: /es');
		exit;
	break;
	case '/es':
	case '/en':
	case '/ja':
		$locale = trim($requestUri, '/');
	
		$cacheEntry = Cache::getOrGenerateContent(
			'render.' . $locale . '.html',
			array(
				__DIR__ . '/info.xml',
				__DIR__ . '/render.php',
				__DIR__ . '/templates/index.html',
			),
			function() use ($locale) {
				return render($locale);
			}
		);
        
		$last_modified_time = $cacheEntry->time; 
		$etag = md5($cacheEntry->data); 

		header('Content-Type: text/html; charset=utf-8');
		header("Last-Modified: " . gmdate("D, d M Y H:i:s", $last_modified_time) . " GMT"); 
		header("Etag: {$etag}"); 

		if (strtotime(@$_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified_time || trim(@$_SERVER['HTTP_IF_NONE_MATCH']) == $etag) { 
			header("HTTP/1.1 304 Not Modified"); 
			exit; 
		} 

		echo $cacheEntry->data;
		exit;
	break;
	case '/info.xml':
		header('Content-Type: text/xml; charset=utf-8');
		readfile(__DIR__ . '/' . $requestUri);
		exit;
	break;
	case '/templates/index.html':
		header('Content-Type: text/plain; charset=utf-8');
		readfile(__DIR__ . '/' . $requestUri);
		exit;
	break;
	case '/humans.txt':
	case '/robots.txt':
		header('Content-Type: text/plain; charset=utf-8');
		readfile(__DIR__ . '/' . basename($requestUri));
		exit;
	break;
	default:
		header('HTTP/1.1 404 Not Found');
		header('Content-Type: text/html; charset=utf-8');
		die('<h1>404 Not Found</h1>');
	break;
}