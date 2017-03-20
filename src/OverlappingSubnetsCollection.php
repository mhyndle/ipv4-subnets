<?php
namespace IPv4Subnets;

class OverlappingSubnetsCollection {
    /**
     * @var OverlappingSubnets[]
     */
    private $overlappingSubnets = [];

    /**
     * Returns array of OverlappingSubnets objects.
     *
     * @return OverlappingSubnets[]
     */
    public function getOverlappingSubnets() : array
    {
        return $this->overlappingSubnets;
    }

    /**
     * Returns array of distinct subnets that are fully covering all subnets added in OverlappingSubnets objects.
     *
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

    /**
     * Adds OverlappingSubnets object to the collection.
     *
     * @param OverlappingSubnets $overlappingSubnets
     */
    public function addOverlappingSubnets(OverlappingSubnets $overlappingSubnets)
    {
        $this->overlappingSubnets[] = $overlappingSubnets;
    }
}