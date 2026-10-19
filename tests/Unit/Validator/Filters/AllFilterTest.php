<?php

declare(strict_types=1);

namespace Tests\Validator\Filters;

use Omega\Validator\Rule\Filter;
use Tests\TestCase;

final class AllFilterTest extends TestCase
{
    public function testCanRenderAllFilterStringUsingChainMethod(): void
    {
        $rule = new Filter();

        $rule
            ->noise_words()
            ->rmpunctuation()
            ->urlencode()
            ->sanitize_email()
            ->sanitize_numbers()
            ->sanitize_floats()
            ->sanitize_string()
            ->boolean()
            ->basic_tags()
            ->whole_number()
            ->ms_word_characters()
            ->lower_case()
            ->upper_case()
            ->slug()
            ->trim();

        $this->assertEquals('noise_words|rmpunctuation|urlencode|sanitize_email|sanitize_numbers|sanitize_floats|sanitize_string|boolean|basic_tags|whole_number|ms_word_characters|lower_case|upper_case|slug|trim', $rule->getFilter());
    }
}
