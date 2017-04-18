<?php

$this->respond('/?', function ($request, $response, $service) {
	
		checkLogin();
	
		$service->events = R::findAll('event', ' archived = 1' );
		$service->render('./views/archive.phtml');
	});