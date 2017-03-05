<?php
namespace IPv4Subnets;

class DistinctSubnets {

    /**
     * @var Subnet[]
     */
    private $inputSubnets = [];

    /**
     * @var Subnet[]
     */
    private $distinctSubnets = [];

    /**
     * @var OverlappingSubnetsCollection
     */
    private $overlappingSubnetsCollection;

    public function __construct(array $subnets = [])
    {
        $this->overlappingSubnetsCollection = new OverlappingSubnetsCollection();

        foreach ($subnets as $subnet) {
            $this->add($subnet);
        }
    }

    static public function createFromCIDRs(array $CIDRs = [])
    {
        $subnets = [];

        foreach ($CIDRs as $CIDR) {
            $subnets[] = Subnet::createFromCidr($CIDR);
        }

        return new self($subnets);
    }

    public function add(Subnet $subnet)
    {
        $this->inputSubnets[$subnet->getIndex()] = $subnet;
        $this->classifySubnet($subnet);
    }

    public function addFromCIDR(string $CIDR, array $customData = [])
    {
        $this->add(Subnet::createFromCidr($CIDR, $customData));
    }

    private function classifySubnet(Subnet $subnet)
    {
        /**
         * Iterate through overlapping subnets
         *
         * @var $overlappingSubnet OverlappingSubnets
         */
        foreach ($this->overlappingSubnetsCollection->getOverlappingSubnets() as $overlappingSubnet) {

            /**
             * @var $distinctOverlappingSubnet Subnet
             */
            foreach ($overlappingSubnet->getDistinctSubnets() as $distinctOverlappingSubnet) {
                if ($subnet->within($distinctOverlappingSubnet)) {
                    $overlappingSubnet->addCoveredSubnet($subnet);
                    return;
                }
                if ($subnet->contains($distinctOverlappingSubnet)) {
                    $overlappingSubnet->removeDistinctSubnet($distinctOverlappingSubnet);
                    $overlappingSubnet->addDistinctSubnet($subnet);
                    return;
                }
            }
        }

        /**
         * Iterate through distinct subnets
         *
         * @var $distinctSubnet Subnet
         */
        foreach ($this->distinctSubnets as $distinctSubnet) {
            $overlappingStatus = $subnet->overlappingStatusFor($distinctSubnet);
            switch ($overlappingStatus) {
                case Subnet::OVERLAPPING_STATUS_CONTAINS:
                    $overlappingSubnet = new OverlappingSubnets();
                    $overlappingSubnet->addDistinctSubnet($subnet);
                    $overlappingSubnet->addCoveredSubnet($distinctSubnet);
                    $this->overlappingSubnetsCollection->addOverlappingSubnets($overlappingSubnet);
                    unset($this->distinctSubnets[$distinctSubnet->getIndex()]);
                    return;
                case Subnet::OVERLAPPING_STATUS_WITHIN:
                    $overlappingSubnet = new OverlappingSubnets();
                    $overlappingSubnet->addDistinctSubnet($distinctSubnet);
                    $overlappingSubnet->addCoveredSubnet($subnet);
                    $this->overlappingSubnetsCollection->addOverlappingSubnets($overlappingSubnet);
                    unset($this->distinctSubnets[$distinctSubnet->getIndex()]);
                    return;
                case Subnet::OVERLAPPING_STATUS_OVERLAPPING:
                    // TODO: add to overlapping subnets
                    return;
                case Subnet::OVERLAPPING_STATUS_DISTINCT:
                default:
                    break;
            }
        }

        $this->distinctSubnets[$subnet->getIndex()] = $subnet;

    }

    public function getDistinctSubnets() : array
    {
        return $this->distinctSubnets;
    }

    public function getOverlappingSubnetsCollection() : OverlappingSubnetsCollection
    {
        return $this->overlappingSubnetsCollection;
    }

    public function getAllDistinctSubnets() : array
    {
        $subnets = $this->getDistinctSubnets();

        foreach ($this->getOverlappingSubnetsCollection()->getDistinctSubnets() as $subnet) {
            $subnets[$subnet->getIndex()] = $subnet;
        }

        return $subnets;
    }

}
