<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Development\Business\Stability;

use ArrayObject;
use Spryker\Zed\Development\Business\DependencyTree\DependencyFilter\ClassNameFilter;
use Spryker\Zed\Development\Business\DependencyTree\DependencyFilter\DependencyFilter;
use Spryker\Zed\Development\Business\DependencyTree\DependencyFilter\DependencyFilterCompositeInterface;
use Spryker\Zed\Development\Business\DependencyTree\DependencyFilter\InTestDependencyFilter;
use Spryker\Zed\Development\Business\DependencyTree\DependencyFilter\TreeFilter;
use Spryker\Zed\Development\Business\DependencyTree\DependencyFilter\TreeFilterInterface;
use Spryker\Zed\Development\Business\DependencyTree\DependencyTree;

class StabilityCalculator implements StabilityCalculatorInterface
{

    /**
     * @var array
     */
    protected $bundles = [];

    /**
     * @var array
     */
    protected $bundlesDependencies;

    /**
     * @var TreeFilterInterface
     */
    protected $filter;

    public function __construct()
    {
        $filter = new TreeFilter();
        $filter->addFilter(new ClassNameFilter('/\\Dependency\\\(.*?)Interface/'))
            ->addFilter(new InTestDependencyFilter());

        $this->filter = $filter;
    }


    /**
     * @return array
     */
    public function calculateStability()
    {
        $bundlesDependencies = json_decode(file_get_contents(APPLICATION_ROOT_DIR . '/data/dependencyTree.json'), true);

        $this->bundlesDependencies = $this->filter($bundlesDependencies);

        foreach ($this->bundlesDependencies as $bundlesDependency) {
            $currentBundleName = $bundlesDependency['bundle'];
            $outgoingBundleName = $bundlesDependency['foreign bundle'];

            if (!isset($this->bundles[$currentBundleName])) {
                $this->addInfoStack($currentBundleName);
            }
            if (!isset($this->bundles[$outgoingBundleName])) {
                $this->addInfoStack($outgoingBundleName);
            }

            $this->bundles[$currentBundleName]['out'][$outgoingBundleName] = $outgoingBundleName;
            $this->bundles[$outgoingBundleName]['in'][$currentBundleName] = $currentBundleName;
        }

        $this->calculateBundlesStability();
        $this->calculateIndirectBundlesStability();
        $this->calculateSprykerStability();

        ksort($this->bundles);

        return $this->bundles;
    }

    /**
     * @param array $bundlesDependencies
     *
     * @return array
     */
    protected function filter(array $bundlesDependencies)
    {
        $callback = function (array $bundleDependency) {
            return ($bundleDependency[DependencyTree::META_FOREIGN_LAYER] !== 'external');
        };
        $bundlesDependencies = array_filter($bundlesDependencies, $callback);
        $bundlesDependencies = $this->filter->filter($bundlesDependencies);

        return $bundlesDependencies;
    }

    /**
     * @param string $bundle
     *
     * @return void
     */
    protected function addInfoStack($bundle)
    {
        $this->bundles[$bundle] = [
            'in' => [],
            'indirectIn' => [],
            'out' => [],
            'indirectOut' => [],
            'stability' => 0,
            'indirectStability' => 0,
            'sprykerStability' => 0,
        ];
    }

    /**
     * @return void
     */
    protected function calculateBundlesStability()
    {
        foreach ($this->bundles as &$bundle) {
            $stability = count($bundle['out']) / (count($bundle['in']) + count($bundle['out']));
            $bundle['stability'] = number_format($stability, 3);
        }
    }

    /**
     * @return void
     */
    protected function calculateIndirectBundlesStability()
    {
        foreach ($this->bundles as $bundle => $info) {
            $indirectOutgoingDependencies = new ArrayObject();
            $this->buildIndirectOutgoingDependencies($bundle, $indirectOutgoingDependencies);
            $this->bundles[$bundle]['indirectOut'] = $indirectOutgoingDependencies->getArrayCopy();

            $indirectIncomingDependencies = new ArrayObject();
            $this->buildIndirectIncomingDependencies($bundle, $indirectIncomingDependencies);
            $this->bundles[$bundle]['indirectIn'] = $indirectIncomingDependencies->getArrayCopy();

            $indirectStability = count($this->bundles[$bundle]['indirectOut']) / (count($this->bundles[$bundle]['indirectIn']) + count($this->bundles[$bundle]['indirectOut']));
            $this->bundles[$bundle]['indirectStability'] = number_format($indirectStability, 3);
        }
    }

    /**
     * @return void
     */
    protected function calculateSprykerStability()
    {
        foreach ($this->bundles as $bundle => $info) {
            $sprykerStability = (count($info['indirectIn']) * count($info['indirectOut'])) * (1 - abs(0.5 - $info['indirectStability']));
            $this->bundles[$bundle]['sprykerStability'] = number_format($sprykerStability, 3);
        }
    }

    /**
     * @param string $bundleName
     * @param \ArrayObject $indirectOutgoingDependencies
     *
     * @return void
     */
    protected function buildIndirectOutgoingDependencies($bundleName, ArrayObject $indirectOutgoingDependencies)
    {
        $dependencies = $this->bundles[$bundleName]['out'];

        $indirectOutgoingDependencies[$bundleName] = $dependencies;
        foreach ($dependencies as $dependentBundle) {
            if (array_key_exists($dependentBundle, $indirectOutgoingDependencies)) {
                continue;
            }
            $this->buildIndirectOutgoingDependencies($dependentBundle, $indirectOutgoingDependencies);
        }
    }

    /**
     * @param string $bundleName
     * @param \ArrayObject $indirectIncomingDependencies
     *
     * @return void
     */
    protected function buildIndirectIncomingDependencies($bundleName, ArrayObject $indirectIncomingDependencies)
    {
        $dependencies = $this->bundles[$bundleName]['in'];

        $indirectIncomingDependencies[$bundleName] = $dependencies;
        foreach ($dependencies as $dependentBundle) {
            if (array_key_exists($dependentBundle, $indirectIncomingDependencies)) {
                continue;
            }
            $this->buildIndirectIncomingDependencies($dependentBundle, $indirectIncomingDependencies);
        }
    }

}
