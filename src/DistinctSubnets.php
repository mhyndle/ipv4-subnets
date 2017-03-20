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

    /**
     * DistinctSubnets constructor.
     *
     * @param Subnet[] $subnets
     */
    public function __construct(array $subnets = [])
    {
        $this->overlappingSubnetsCollection = new OverlappingSubnetsCollection();

        foreach ($subnets as $subnet) {
            $this->add($subnet);
        }
    }

    /**
     * Creates and returns DistinctSubnet object built from array of CIDR adresses.
     *
     * @param string[] $CIDRs
     * @return DistinctSubnets
     */
    static public function createFromCIDRs(array $CIDRs = [])
    {
        $subnets = [];

        foreach ($CIDRs as $CIDR) {
            $subnets[] = Subnet::createFromCidr($CIDR);
        }

        return new self($subnets);
    }

    /**
     * Adds Subnet to the collection.
     *
     * @param Subnet $subnet
     */
    public function add(Subnet $subnet)
    {
        $this->inputSubnets[$subnet->getIndex()] = $subnet;
        $this->classifySubnet($subnet);
    }

    /**
     * Adds
     *
     * @param string $CIDR
     * @param array $customData custom data that will be added to the Subnet object
     */
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
                $overlappingStatus = $subnet->overlappingStatusFor($distinctOverlappingSubnet);
                switch ($overlappingStatus) {
                    case Subnet::OVERLAPPING_STATUS_CONTAINS:
                        $overlappingSubnet->removeDistinctSubnet($distinctOverlappingSubnet);
                        $overlappingSubnet->addDistinctSubnet($subnet);
                        return;
                    case Subnet::OVERLAPPING_STATUS_WITHIN:
                        $overlappingSubnet->addCoveredSubnet($subnet);
                        return;
                    case Subnet::OVERLAPPING_STATUS_OVERLAPPING:
                        $lowestIp = IPTools::getLowestIP([$subnet->getIPAddress(), $distinctOverlappingSubnet->getIPAddress()]);
                        $highestIp = IPTools::getHighestIP([$subnet->getIPAddress(), $distinctOverlappingSubnet->getIPAddress()]);
                        $distinctCIDRs = IPRangeToCIDRConverter::ip_range_to_subnet_array($lowestIp, $highestIp);
                        $overlappingSubnet->removeDistinctSubnet($distinctOverlappingSubnet);
                        $overlappingSubnet->addCoveredSubnet($subnet);
                        foreach ($distinctCIDRs as $cidr) {
                            $overlappingSubnet->addDistinctSubnet(Subnet::createFromCidr($cidr));
                        }
                        return;
                    case Subnet::OVERLAPPING_STATUS_DISTINCT:
                    default:
                        break;
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
                    $overlappingSubnet = new OverlappingSubnets();
                    $lowestIp = IPTools::getLowestIP([$subnet->getIPAddress(), $distinctSubnet->getIPAddress()]);
                    $highestIp = IPTools::getHighestIP([$subnet->getIPAddress(), $distinctSubnet->getIPAddress()]);
                    $distinctCIDRs = IPRangeToCIDRConverter::ip_range_to_subnet_array($lowestIp, $highestIp);
                    $overlappingSubnet->removeDistinctSubnet($distinctSubnet);
                    $overlappingSubnet->addCoveredSubnet($subnet);
                    foreach ($distinctCIDRs as $cidr) {
                        $overlappingSubnet->addDistinctSubnet(Subnet::createFromCidr($cidr));
                    }
                    unset($this->distinctSubnets[$distinctSubnet->getIndex()]);
                    return;
                case Subnet::OVERLAPPING_STATUS_DISTINCT:
                default:
                    break;
            }
        }

        $this->distinctSubnets[$subnet->getIndex()] = $subnet;

    }

    /**
     * Returns array of subnets that are distinct and are not overlapping with any other subnet added to collection.
     *
     * @return Subnet[]
     */
    public function getDistinctSubnets() : array
    {
        return $this->distinctSubnets;
    }

    /**
     * Returns collection of subnets that are overlapping.
     *
     * @return OverlappingSubnetsCollection
     */
    public function getOverlappingSubnetsCollection() : OverlappingSubnetsCollection
    {
        return $this->overlappingSubnetsCollection;
    }

    /**
     * Returns array of distinct subnets that are fully covering all subnets added to collection.
     *
     * @return Subnet[]
     */
    public function getAllDistinctSubnets() : array
    {
        $subnets = $this->getDistinctSubnets();

        foreach ($this->getOverlappingSubnetsCollection()->getDistinctSubnets() as $subnet) {
            $subnets[$subnet->getIndex()] = $subnet;
        }

        return $subnets;
    }

}
