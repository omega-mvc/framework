<?php

declare(strict_types=1);

/*
 * Part of Omega - Tests\Console\Commands Provision.
 *
 * @link      https://omegamvc.github.io
 * @author    Axel Magold <axel.magold@gmail.com>
 * @copyright 2025-2026 The Omega MVC Framework
 * @license   https://opensource.org/license/mit The MIT License
 * @version   2.0.0
 */

namespace Tests\Console\Commands\Fixtures;

use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * Helper registered under the `question` name that is not a QuestionHelper.
 */
final class FakeQuestionHelper implements HelperInterface
{
    private ?HelperSet $helperSet = null;

    public function setHelperSet(?HelperSet $helperSet): void
    {
        $this->helperSet = $helperSet;
    }

    public function getHelperSet(): ?HelperSet
    {
        return $this->helperSet;
    }

    public function getName(): string
    {
        return 'question';
    }
}
