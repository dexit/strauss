<?php

namespace CoenJacobs\Mozart\Console\Commands;

use CoenJacobs\Mozart\Composer\Package;
use CoenJacobs\Mozart\Mover;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Compose extends Command
{
    protected function configure()
    {
        $this->setName('compose');
        $this->setDescription('Composes all dependencies as a package inside a WordPress plugin.');
        $this->setHelp('');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $workingDir = getcwd();

        $config = json_decode(file_get_contents($workingDir . '/composer.json'));
        $config = $config->extra->mozart;

        $mover = new Mover($workingDir, $config);
        $mover->deleteTargetDirs();

        $packages = $this->findPackages($workingDir, $config->packages, []);

        foreach( $packages as $package ) {
            $mover->movePackage($package);
        }

        $mover->replaceClassmapNames();
    }

    /**
     * Loops through all dependencies and their dependencies and so on...
     * will eventually return a list of all packages required by the full tree.
     */
    private function findPackages($workingDir, $slugs, $packages)
    {
        foreach ($slugs as $package_slug) {
            $packageDir = $workingDir . '/vendor/' . $package_slug .'/';

            if (! is_dir($packageDir) ) {
                continue;
            }

            $package = new Package($packageDir);
            $package->findAutoloaders();
            $packages[] = $package;

            $config = json_decode(file_get_contents($packageDir . 'composer.json'));
            $dependencies = array_keys( (array) $config->require);
            $packages = $this->findPackages($workingDir, $dependencies, $packages);
        }

        return $packages;
    }
}
