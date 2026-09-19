<?php

/**
 * Part of Omega - Validator Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Validator;

use Omega\Validator\Rule\Filter;
use Omega\Validator\Rule\Valid;

/**
 * Alias for validation rule,
 * return string validation rule.
 */
function vr(): Valid
{
    return new Valid();
}

/**
 * Alias for filter rule,
 * return string filter rule.
 */
function fr(): Filter
{
    return new Filter();
}

/**
 * Alias for validator.
 *
 * @param array<string, mixed> $field Field input
 */
function validate(array $field): Validator
{
    return new Validator($field);
}