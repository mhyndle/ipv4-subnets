<?php
namespace IPv4Subnets;

class OverlappingSubnetsCollection {
    /**
     * @var OverlappingSubnets[]
     */
    private $overlappingSubnets = [];

    public function getOverlappingSubnets() : array
    {
        return $this->overlappingSubnets;
    }

    /**
     * @return Subnet[]
     */
    public function getDistinctSubnets() : array
    {
        $subnets = [];

        /**
         * @var $containingSubnet OverlappingSubnets
         */
        foreach ($this->overlappingSubnets as $overlappingSubnets) {
            foreach ($overlappingSubnets->getDistinctSubnets() as $subnet) {
                $subnets[$subnet->getIndex()] = $subnet;
            }
        }

        return $subnets;
    }

    public function addOverlappingSubnets(OverlappingSubnets $overlappingSubnets)
    {
        $this->overlappingSubnets[] = $overlappingSubnets;
    }
}