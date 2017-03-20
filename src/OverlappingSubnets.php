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

    /**
     * Adds distinct Subnet.
     *
     * @param Subnet $subnet
     */
    public function addDistinctSubnet(Subnet $subnet)
    {
        $this->distinctSubnets[$subnet->getIndex()] = $subnet;
        $this->addCoveredSubnet($subnet);
    }

    /**
     * Adds covered Subnet.
     *
     * @param Subnet $subnet
     */
    public function addCoveredSubnet(Subnet $subnet)
    {
        $this->coveredSubnets[$subnet->getIndex()] = $subnet;
    }

    /**
     * Removes distinct Subnet.
     *
     * @param Subnet $subnet
     */
    public function removeDistinctSubnet(Subnet $subnet)
    {
        unset($this->distinctSubnets[$subnet->getIndex()]);
    }

    /**
     * Removes covered Subnet.
     *
     * @param Subnet $subnet
     */
    public function removeCoveredSubnet(Subnet $subnet)
    {
        unset($this->coveredSubnets[$subnet->getIndex()]);
    }

    /**
     * Returns array of distinct subnets that are fully covering all covered subnets in this object.
     *
     * @return Subnet[]
     */
    public function getDistinctSubnets() : array
    {
        return $this->distinctSubnets;
    }

    /**
     * Returns array of all covered subnets in the collection.
     *
     * @return Subnet[]
     */
    public function getCoveredSubnets() : array
    {
        return $this->coveredSubnets;
    }

    /**
     * Returns TRUE if given Subnet is already covered in this object.
     *
     * @param Subnet $subnet
     * @return bool
     */
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