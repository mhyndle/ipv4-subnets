<?php
namespace IPv4Subnets;

use IPv4\SubnetCalculator;

class Subnet extends SubnetCalculator {
    const OVERLAPPING_STATUS_CONTAINS = 'contains';
    const OVERLAPPING_STATUS_WITHIN = 'within';
    const OVERLAPPING_STATUS_OVERLAPPING = 'overlapping';
    const OVERLAPPING_STATUS_DISTINCT = 'distinct';

    /**
     * @var array
     */
    private $customData = [];

    public function __construct($ip, $network_size, array $customData = [])
    {
        parent::__construct($ip, $network_size);
        $this->setCustomData($customData);
    }

    /**
     * Creates Subnet basing on given CIDR address.
     *
     * @param string $cidr
     * @param array $customData
     * @return Subnet
     */
    static public function createFromCidr(string $cidr, array $customData = [] ) : self
    {
        $cidrParts = explode('/', $cidr);

        return new self($cidrParts[0], $cidrParts[1], $customData);
    }

    /**
     * Sets custom data for Subnet.
     *
     * @param array $customData
     */
    public function setCustomData(array $customData)
    {
        $this->customData = $customData;
    }

    /**
     * Returns custom data for Subnet.
     *
     * @return array
     */
    public function getCustomData()
    {
        return $this->customData;
    }

    /**
     * Returns overlapping status of this Subnet object for given Subnet.
     *
     * @param Subnet $subnet
     * @return string
     */
    public function overlappingStatusFor(Subnet $subnet) : string
    {
        if ($this->contains($subnet)) return self::OVERLAPPING_STATUS_CONTAINS;
        if ($this->within($subnet)) return self::OVERLAPPING_STATUS_WITHIN;
        if ($this->overlapping($subnet)) return self::OVERLAPPING_STATUS_OVERLAPPING;

        return self::OVERLAPPING_STATUS_DISTINCT;
    }

    /**
     * Returns TRUE if given Subnet is fully included in this object Subnet. Otherwise FALSE is returned.
     *
     * @param Subnet $subnet
     * @return bool
     */
    public function contains(Subnet $subnet) : bool
    {
        return ($this->firstIpIsLowerOrEqual($subnet->getIPAddress()) && $this->broadcastIpIsGreaterOrEqual($subnet->getBroadcastAddress()));
    }

    /**
     * Return TRUE if given Subnet is containing this object Subnet. Otherwise FALSE is returned.
     *
     * @param Subnet $subnet
     * @return bool
     */
    public function within(Subnet $subnet) : bool
    {
        return ($this->firstIpIsGreaterOrEqual($subnet->getIPAddress()) && $this->broadcastIpIsLowerOrEqual($subnet->getBroadcastAddress()));
    }

    /**
     * Returns TRUE if given Subnet is overlapping with this object Subnet. Otherwise FALSE is returned.
     * Please notice, that TRUE will be returned for equal subnets and for subnets where one is containing another.
     * FALSE will be returned only if subnets are totally separate.
     *
     * @param Subnet $subnet
     * @return bool
     */
    public function overlapping(Subnet $subnet) : bool
    {
        if ($this->firstIpIsLowerThan($subnet->getIPAddress())
            && $this->broadcastIpIsGreaterOrEqual($subnet->getIPAddress())
            && $this->broadcastIpIsLowerOrEqual($subnet->getBroadcastAddress())) {
            return true;
        }
        if ($this->broadcastIpIsGreaterThan($subnet->getBroadcastAddress())
            && $this->firstIpIsGreaterOrEqual($subnet->getIPAddress())
            && $this->firstIpIsLowerOrEqual($subnet->getBroadcastAddress())) {
            return true;
        }

        return false;
    }

    private function firstIpIsEqual(string $ip) : bool
    {
        return ($this->getIPAddress() === $ip);
    }

    private function broadcastIpIsEqual(string $ip) : bool
    {
        return ($this->getBroadcastAddress() === $ip);
    }

    private function firstIpIsLowerThan(string $ip) : bool
    {
        return IPTools::ipIsLowerThan($this->getIPAddress(), $ip);
    }

    private function broadcastIpIsLowerThan(string $ip) : bool
    {
        return IPTools::ipIsLowerThan($this->getBroadcastAddress(), $ip);
    }

    private function firstIpIsGreaterThan(string $ip) : bool
    {
        return !(IPTools::ipIsLowerThan($this->getIPAddress(), $ip) || $this->firstIpIsEqual($ip));
    }

    private function broadcastIpIsGreaterThan(string $ip) : bool
    {
        return !(IPTools::ipIsLowerThan($this->getBroadcastAddress(), $ip) || $this->broadcastIpIsEqual($ip));
    }

    private function firstIpIsLowerOrEqual(string $ip) : bool
    {
        return ($this->firstIpIsLowerThan($ip) || $this->firstIpIsEqual($ip));
    }

    private function broadcastIpIsLowerOrEqual(string $ip) : bool
    {
        return ($this->broadcastIpIsLowerThan($ip) || $this->broadcastIpIsEqual($ip));
    }

    private function firstIpIsGreaterOrEqual(string $ip) : bool
    {
        return ($this->firstIpIsGreaterThan($ip) || $this->firstIpIsEqual($ip));
    }

    private function broadcastIpIsGreaterOrEqual(string $ip) : bool
    {
        return ($this->broadcastIpIsGreaterThan($ip) || $this->firstIpIsEqual($ip));
    }

    /**
     * Returns index of this object Subnet. Index is something that identifies unique subnet.
     *
     * @return string
     */
    public function getIndex() : string
    {
        return $this->getCidr() . '-' . md5(json_encode($this->getCustomData()));
    }

    /**
     * Returns CIDR address of this object Subnet.
     *
     * @return string
     */
    public function getCidr() : string
    {
        return $this->getIPAddress() . '/' . $this->getNetworkSize();
    }
}