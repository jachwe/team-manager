<?php

require 'vendor/autoload.php';

use MatthiasMullie\Minify;

$minifier = new Minify\CSS();
$minifier->add('public/css/bootstrap.min.css');
$minifier->add('public/css/bootstrap-datetimepicker.min.css');
$minifier->add('public/css/sb-admin.css');
$minifier->add('public/font-awesome/css/font-awesome.min.css');
$minifier->add('public/summernote/summernote.css');
$minifier->add('public/css/main.css');


$minifier->minify('public/css/dist.css');

$minifier = new Minify\JS();
$minifier->add('public/js/jquery.js');
$minifier->add('public/js/bootstrap.min.js');
$minifier->add('public/js/bootstrap-datetimepicker.min.js');
$minifier->add('public/js/bootstrap-typeahead.js');
$minifier->add('public/js/list.js');
$minifier->add('public/summernote/summernote.min.js');
$minifier->add('public/summernote/lang/summernote-de-DE.js');
$minifier->add('public/js/main.js');


echo $minifier->minify('public/js/dist.js');

// save minified file to disk
// $minifiedPath = '/path/to/minified/css/file.css';
// $minifier->minify($minifiedPath);