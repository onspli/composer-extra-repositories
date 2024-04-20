<?php

namespace Onspli\ComposerInjectRepositories;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PluginEvents;
use Composer\Util\HttpDownloader;
use Composer\Util\ProcessExecutor;
use Composer\Util\Platform;
use Composer\Repository\Vcs\GitDriver;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    protected $composer;
    protected $io;
    public const PRIORITY = 1;
    public const EXTRA_KEY = 'inject-repositories';

    function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    function deactivate(Composer $composer, IOInterface $io)
    {
    }

    function uninstall(Composer $composer, IOInterface $io)
    {
    }

    public static function getSubscribedEvents()
    {
        return array(
            PluginEvents::INIT => array(
                array('onInit', self::PRIORITY)
            ),
        );
    }

    public function onInit()
    {
        $package = $this->composer->getPackage();
        $injections = $package->getExtra()[self::EXTRA_KEY] ?? [];
        foreach ($injections as $injection) {
            $this->processInjection($injection);
        }
    }

    private function prependRepository(array $config)
    {
        $repository_manager = $this->composer->getRepositoryManager();
        $type = $config['type'] or throw new \RuntimeException('Repository type is not set.');
        $repo = $repository_manager->createRepository($type, $config);
        $repository_manager->prependRepository($repo);
    }

    private function processInjection(array $options)
    {
        $this->io->notice('processing repositories injection ' . json_encode($options));

        $type = $options['type'] ?? 'unknown';
        $content = null;
        switch ($type) {
            case 'local':
                $content = $this->injectionLocal($options);
                break;
            case 'remote':
                $content = $this->injectionRemote($options);
                break;
            case 'git':
                $content = $this->injectionGit($options);
                break;
            default:
                throw new \RuntimeException('Repositories injection type ' . $type . ' not supported.');

        }

        if (!$content) {
            throw new \RuntimeException('Empty repositories injection file.');
        }

        $json = json_decode($content, true);
        if ($json === null) {
            throw new \RuntimeException('Invalid repositories injestion json.');
        }
        $repositories = $json['repositories'] ?? [];

        foreach ($repositories as $repository) {
            $this->io->notice('  injecting repository ' . json_encode($repository));
            $this->prependRepository($repository);
        }
    }

    private function injectionLocal($options)
    {
        $path = $options['path'] or throw new \RuntimeException('Option path is not set.');
        $path = Platform::expandPath($path);
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException('The file "' . $path . '" is not readable.');
        }
        return $content;
    }

    private function injectionRemote($options)
    {
        $url = $options['url'] or throw new \RuntimeException('Option url is not set.');
        $http_downloader = new HttpDownloader($this->io, $this->composer->getConfig());
        $response = $http_downloader->get($url);
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('GET "' . $url . '" returned status code ' . $response->getStatusCode());
        }
        $content = $response->getBody();
        return $content;
    }

    private function injectionGit($options)
    {
        $url = $options['url'] or throw new \RuntimeException('Option url is not set.');
        $file = $options['file'] or throw new \RuntimeException('Option file is not set.');

        $process_executor = new ProcessExecutor($this->io);
        $http_downloader = new HttpDownloader($this->io, $this->composer->getConfig());
        $driver = new GitDriver(['type' => 'git', 'url' => $url], $this->io, $this->composer->getConfig(), $http_downloader, $process_executor);
        $driver->initialize();

        $ref = $options['ref'] ?? $driver->getRootIdentifier();
        $content = $driver->getFileContent($file, $ref);
        if ($content == null) {
            throw new \RuntimeException('The file ' . $file . ' in repository ' . $url . ' does not exist.');
        }
        return $content;
    }

}
