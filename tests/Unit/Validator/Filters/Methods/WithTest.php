<?php

use Omega\Validator\Rule\Filter;

it('can create filter using with method', function () {
    $filter = Filter::with()->trim();

    expect($filter->getFilter())->toEqual('trim');
});

it('can create filter using with method from instance', function () {
    $filter = (new Filter())->with()->trim();

    expect($filter->getFilter())->toEqual('trim');
});