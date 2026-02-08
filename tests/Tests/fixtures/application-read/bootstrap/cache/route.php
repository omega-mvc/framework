<?php return array (
  0 => 
  array (
    'method' => 
    array (
      0 => 'get',
      1 => 'head',
    ),
    'uri' => '/test',
    'expression' => '/test',
    'function' => 
    array (
      0 => '',
      1 => '',
    ),
    'middleware' => 
    array (
      0 => 'test',
    ),
    'name' => 'test',
    'patterns' => 
    array (
    ),
  ),
  1 => 
  array (
    'method' => 
    array (
      0 => 'get',
      1 => 'head',
    ),
    'uri' => '/test/(:id)',
    'expression' => '/test/(\\d+)',
    'function' => 
    array (
      0 => '',
      1 => 'empty',
    ),
    'middleware' => 
    array (
    ),
    'name' => '',
    'patterns' => 
    array (
    ),
  ),
  2 => 
  array (
    'method' => 'post',
    'uri' => 'test//test/post',
    'expression' => 'test//test/post',
    'function' => 
    array (
      0 => '',
      1 => 'post',
    ),
    'middleware' => 
    array (
    ),
    'name' => 'post',
    'patterns' => 
    array (
    ),
  ),
);
