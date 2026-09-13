<?php

return array (
  0 =>
  array (
    'method' =>
    array (
      0 => 'get',
      1 => 'head',
    ),
    'uri' => '/test',
    'expression' => '/test',
    'function' => 'O:53:"Omega\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:44:"Omega\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:15:"fn () => \'test\'";s:5:"scope";N;s:4:"this";N;s:4:"self";s:32:"000000000000000a0000000000000000";}}',
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
    'function' => 'O:53:"Omega\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:44:"Omega\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:16:"fn () => \'empty\'";s:5:"scope";N;s:4:"this";N;s:4:"self";s:32:"000000000000001f0000000000000000";}}',
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
    'function' => 'O:53:"Omega\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:44:"Omega\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:15:"fn () => \'post\'";s:5:"scope";N;s:4:"this";N;s:4:"self";s:32:"00000000000000250000000000000000";}}',
    'middleware' =>
    array (
    ),
    'name' => 'post',
    'patterns' =>
    array (
    ),
  ),
);
