<?php
namespace IPv4Subnets;

class OverlappingSubnets {
    /**
     * @var Subnet[]
     */
    private $distinctSubnets = [];

    /**
     * @var Subnet[]
     */
    private $coveredSubnets = [];

    public function addDistinctSubnet(Subnet $subnet)
    {
        $this->distinctSubnets[$subnet->getIndex()] = $subnet;
        $this->addCoveredSubnet($subnet);
    }

    public function addCoveredSubnet(Subnet $subnet)
    {
        $this->coveredSubnets[$subnet->getIndex()] = $subnet;
    }

    public function removeDistinctSubnet(Subnet $subnet)
    {
        unset($this->distinctSubnets[$subnet->getIndex()]);
    }

    public function removeCoveredSubnet(Subnet $subnet)
    {
        unset($this->coveredSubnets[$subnet->getIndex()]);
    }

    public function getDistinctSubnets() : array
    {
        return $this->distinctSubnets;
    }

    public function getCoveredSubnets() : array
    {
        return $this->coveredSubnets;
    }

    public function coversSubnet(Subnet $subnet) : bool
    {
        foreach ($this->coveredSubnets as $coveredSubnet) {
            if ($coveredSubnet->getIndex() === $subnet->getIndex()) {
                return true;
            }
        }

        return false;
    }

}