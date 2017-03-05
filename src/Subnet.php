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

    static public function createFromCidr(string $cidr, array $customData = [] ) : self
    {
        $cidrParts = explode('/', $cidr);

        return new self($cidrParts[0], $cidrParts[1], $customData);
    }

    public function setCustomData(array $customData)
    {
        $this->customData = $customData;
    }

    public function getCustomData()
    {
        return $this->customData;
    }

    public function overlappingStatusFor(Subnet $subnet) : string
    {
        if ($this->contains($subnet)) return self::OVERLAPPING_STATUS_CONTAINS;
        if ($this->within($subnet)) return self::OVERLAPPING_STATUS_WITHIN;
        if ($this->overlapping($subnet)) return self::OVERLAPPING_STATUS_OVERLAPPING;

        return self::OVERLAPPING_STATUS_DISTINCT;
    }

    public function contains(Subnet $subnet) : bool
    {
        return ($this->firstIpIsLowerOrEqual($subnet->getIPAddress()) && $this->broadcastIpIsGreaterOrEqual($subnet->getBroadcastAddress()));
    }

    public function within(Subnet $subnet) : bool
    {
        return ($this->firstIpIsGreaterOrEqual($subnet->getIPAddress()) && $this->broadcastIpIsLowerOrEqual($subnet->getBroadcastAddress()));
    }

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
        return self::ipIsLowerThan($this->getIPAddress(), $ip);
    }

    private function broadcastIpIsLowerThan(string $ip) : bool
    {
        return self::ipIsLowerThan($this->getBroadcastAddress(), $ip);
    }

    private function firstIpIsGreaterThan(string $ip) : bool
    {
        return !(self::ipIsLowerThan($this->getIPAddress(), $ip) || $this->firstIpIsEqual($ip));
    }

    private function broadcastIpIsGreaterThan(string $ip) : bool
    {
        return !(self::ipIsLowerThan($this->getBroadcastAddress(), $ip) || $this->broadcastIpIsEqual($ip));
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

    static private function ipIsLowerThan(string $ip1, string $ip2) : bool
    {
        $quads = self::getIpQuads($ip1);
        $comparisonQuads = self::getIpQuads($ip2);

        if ($quads[0] < $comparisonQuads[0]) {
            return true;
        } elseif ($quads[0] > $comparisonQuads[0]) {
            return false;
        }

        if ($quads[1] < $comparisonQuads[1]) {
            return true;
        } elseif ($quads[1] > $comparisonQuads[1]) {
            return false;
        }

        if ($quads[2] < $comparisonQuads[2]) {
            return true;
        } elseif ($quads[2] > $comparisonQuads[2]) {
            return false;
        }

        if ($quads[3] < $comparisonQuads[3]) {
            return true;
        } elseif ($quads[3] > $comparisonQuads[3]) {
            return false;
        }

        return false;
    }

    static private function getIpQuads(string $ip) : array
    {
        return explode('.', $ip);
    }

    public function getIndex() : string
    {
        return $this->getCidr();
    }

    public function getCidr() : string
    {
        return $this->getIPAddress() . '/' . $this->getNetworkSize();
    }
}