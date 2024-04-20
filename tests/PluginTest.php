<?php
namespace Onspli\ComposerInjectRepositories;

use PHPUnit\Framework\TestCase;
use Composer\Composer;

final class PluginTest extends TestCase
{
    public function testAssertInterface(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(\Composer\Plugin\PluginInterface::class, $plugin);
        $this->assertInstanceOf(\Composer\EventDispatcher\EventSubscriberInterface::class, $plugin);
    }

    public function testSubscribedEvents()
    {
        $subscriptions = Plugin::getSubscribedEvents();
        $this->assertCount(1, $subscriptions);
        $this->assertArrayHasKey(\Composer\Plugin\PluginEvents::INIT, $subscriptions);
    }


}

