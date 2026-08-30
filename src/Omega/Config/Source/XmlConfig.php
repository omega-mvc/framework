<?php

/**
 * Part of Omega - Config Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Config\Source;

use Exception;
use Omega\Config\Exceptions\MalformedXmlException;

use function json_decode;
use function json_encode;
use function simplexml_load_string;

use const JSON_THROW_ON_ERROR;

/**
 * Configuration source that loads data from an XML file.
 *
 * This implementation reads an XML configuration file, parses its content, and
 * returns it as an associative array. It ensures the file is readable and properly
 * structured.
 *
 * @category   Omega
 * @package    Config
 * @subpackage Source
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class XmlConfig extends AbstractSource
{
    /**
     * {@inheritdoc}
     *
     * @return array<string, mixed>
     * @throws MalformedXmlException If unable to produce the content.
     */
    public function fetch(): array
    {
        try {
            $xml = simplexml_load_string($this->fetchContent());
            $decoded = json_decode(
                json_encode($xml, JSON_THROW_ON_ERROR),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            return $this->normalizeConfig($decoded);
        } catch (Exception) {
            throw new MalformedXmlException('Invalid XML format in configuration file.');
        }
    }
}
