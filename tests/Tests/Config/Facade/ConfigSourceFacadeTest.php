<?php

declare(strict_types=1);

namespace Tests\Config\Facade;

use Omega\Application\Application;
use Omega\Config\ConfigSource as ConfigSourceService;
use Omega\Config\Facade\ConfigSource;
use Omega\Facade\AbstractFacade;
use Omega\Facade\Exceptions\FacadeObjectNotSetException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(ConfigSource::class)]
#[CoversClass(ConfigSourceService::class)]
final class ConfigSourceFacadeTest extends TestCase
{
    protected function setUp(): void
    {
        $this->app = new Application(__DIR__ . '/../fixtures/support/');
        ConfigSourceService::resetMacro();
    }

    protected function tearDown(): void
    {
        AbstractFacade::setFacadeBase(null);
        AbstractFacade::flushInstance();

        $this->app->flush();

        parent::tearDown();
    }

    public function testGetsTheFacadeAccessor(): void
    {
        $this->assertSame(ConfigSourceService::class, ConfigSource::getFacadeAccessor());
    }

    public function testForwardsAStaticCallToTheConfigSourceService(): void
    {
        AbstractFacade::setFacadeBase($this->app);

        $config = ConfigSource::fromArray(['app' => ['debug' => true]])->build();

        $this->assertSame(true, $config->get('app.debug'));
    }

    public function testMakesAStaticallyRegisteredMacroCallableViaTheFacade(): void
    {
        AbstractFacade::setFacadeBase($this->app);

        $service = new ConfigSourceService();

        $filter = self::stringKeyedContent(...);
        $yamlCall = function (array $content) use ($service, $filter): ConfigSourceService {
            return $service->fromArray($filter($content));
        };

        ConfigSource::__callStatic('macro', ['fromYaml', $yamlCall]);

        $yaml = ConfigSource::__callStatic('fromYaml', [['service' => 'yaml']]);
        $this->assertInstanceOf(ConfigSourceService::class, $yaml);

        $config = $yaml->build();

        $this->assertSame('yaml', $config->get('service'));
    }

    public function testThrowsWhenAStaticCallIsMadeWithoutAnApplication(): void
    {
        $this->expectException(FacadeObjectNotSetException::class);

        ConfigSource::fromArray(['key' => 'value']);
    }

    /**
     * Keep only the string-keyed entries of the payload.
     *
     * @param array<mixed> $content The macro payload.
     * @return array<string, mixed> The string-keyed subset of the payload.
     */
    private static function stringKeyedContent(array $content): array
    {
        $result = [];

        foreach ($content as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
