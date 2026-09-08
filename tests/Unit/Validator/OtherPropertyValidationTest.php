<?php

use Omega\Validator\Validator;

// other property
it('property \'not\' same result with method \'not()\'', function () {
    $val = new Validator();

    $val->field('test')->not->required();
    $val->test2->not->required();

    expect($val->isValid())->toBeTrue();
});
