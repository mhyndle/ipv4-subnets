<?php
namespace IPv4Subnets;

class IPRangeToCIDRConverter {
    /**
     *  Convert IP address to long int, truncated to 32-bits to avoid sign extension on 64-bit platforms.
     */
    static public function ip2long32(string $ip) : string
    {
        return ( ip2long($ip) & 0xFFFFFFFF );
    }

    /**
     * Convert IP address to unsigned long int.
     */
    static public function ip2ulong(string $ip) : string
    {
        return sprintf("%u", self::ip2long32($ip));
    }

    /**
     * Convert long int to IP address, truncating to 32-bits.
     */
    static public function long2ip32(string $ip) : string
    {
        return long2ip($ip & 0xFFFFFFFF);
    }

    /**
     * Returns true if $ipaddr is a valid dotted IPv4 address
     */
    static public function is_ipaddr(string $ipaddr) : bool
    {
        if (!is_string($ipaddr))
            return false;

        $ip_long = ip2long($ipaddr);
        $ip_reverse = self::long2ip32($ip_long);

        if ($ipaddr == $ip_reverse)
            return true;
        else
            return false;
    }

    /**
     * Return true if the first IP is 'before' the second
     */
    static public function ip_less_than(string $ip1, string $ip2) : string
    {
        // Compare as unsigned long because otherwise it wouldn't work when
        // crossing over from 127.255.255.255 / 128.0.0.0 barrier
        return self::ip2ulong($ip1) < self::ip2ulong($ip2);
    }

    /**
     * Return true if the first IP is 'after' the second
     */
    static public function ip_greater_than(string $ip1, string $ip2) : string
    {
        // Compare as unsigned long because otherwise it wouldn't work
        // when crossing over from 127.255.255.255 / 128.0.0.0 barrier
        return self::ip2ulong($ip1) > self::ip2ulong($ip2);
    }

    /**
     * Return the next IP address after the given address
     */
    static public function ip_after(string $ip) : string
    {
        return self::long2ip32(ip2long($ip)+1);
    }

    /**
     * Find the smallest possible subnet mask which can contain a given number of IPs
     * e.g. 512 IPs can fit in a /23, but 513 IPs need a /22
     */
    static public function find_smallest_cidr($number)
    {
        $smallest = 1;
        for ($b=32; $b > 0; $b--) {
            $smallest = ($number <= pow(2,$b)) ? $b : $smallest;
        }
        return (32-$smallest);
    }

    /**
     * Find out how many IPs are contained within a given IP range
     * e.g. 192.168.0.0 to 192.168.0.255 returns 256
     */
    static public function ip_range_size($startip, $endip)
    {
        if (self::is_ipaddr($startip) && self::is_ipaddr($endip)) {
            // Operate as unsigned long because otherwise it wouldn't work
            // when crossing over from 127.255.255.255 / 128.0.0.0 barrier
            return abs(self::ip2ulong($startip) - self::ip2ulong($endip)) + 1;
        }
        return -1;
    }

    /**
     * Return the subnet address given a host address and a subnet bit count
     */
    static public function gen_subnet($ipaddr, $bits)
    {
        if (!self::is_ipaddr($ipaddr) || !is_numeric($bits))
            return "";

        return long2ip(ip2long($ipaddr) & self::gen_subnet_mask_long($bits));
    }

    /**
     * Returns a subnet mask (long given a bit count)
     */
    static public function gen_subnet_mask_long($bits)
    {
        $sm = 0;
        for ($i = 0; $i < $bits; $i++) {
            $sm >>= 1;
            $sm |= 0x80000000;
        }
        return $sm;
    }

    /**
     * Return the highest (broadcast) address in the subnet given a host address and a subnet bit count
     */
    static public function gen_subnet_max($ipaddr, $bits)
    {
        if (!self::is_ipaddr($ipaddr) || !is_numeric($bits))
            return "";

        return self::long2ip32(ip2long($ipaddr) | ~self::gen_subnet_mask_long($bits));
    }

    /**
     * Convert a range of IPs to an array of subnets which can contain the range.
     */
    static public function ip_range_to_subnet_array($startip, $endip)
    {

        if (!self::is_ipaddr($startip) || !self::is_ipaddr($endip)) {
            return array();
        }

        // Container for subnets within this range.
        $rangesubnets = array();

        // Figure out what the smallest subnet is that holds the number of IPs in the
        // given range.
        $cidr = self::find_smallest_cidr(self::ip_range_size($startip, $endip));

        // Loop here to reduce subnet size and retest as needed. We need to make sure
        // that the target subnet is wholly contained between $startip and $endip.
        for ($cidr; $cidr <= 32; $cidr++) {
            // Find the network and broadcast addresses for the subnet being tested.
            $targetsub_min = self::gen_subnet($startip, $cidr);
            $targetsub_max = self::gen_subnet_max($startip, $cidr);

            // Check best case where the range is exactly one subnet.
            if (($targetsub_min == $startip) && ($targetsub_max == $endip)) {
                // Hooray, the range is exactly this subnet!
                return array("{$startip}/{$cidr}");
            }

            // These remaining scenarios will find a subnet that uses the largest
            // chunk possible of the range being tested, and leave the rest to be
            // tested recursively after the loop.

            // Check if the subnet begins with $startip and ends before $endip
            if (($targetsub_min == $startip) &&
                self::ip_less_than($targetsub_max, $endip)) {
                break;
            }

            // Check if the subnet ends at $endip and starts after $startip
            if (self::ip_greater_than($targetsub_min, $startip) &&
                ($targetsub_max == $endip)) {
                break;
            }

            // Check if the subnet is between $startip and $endip
            if (self::ip_greater_than($targetsub_min, $startip) &&
                self::ip_less_than($targetsub_max, $endip)) {
                break;
            }
        }

        // Some logic that will recursivly search from $startip to the first IP before
        // the start of the subnet we just found.
        // NOTE: This may never be hit, the way the above algo turned out, but is left
        // for completeness.
        if ($startip != $targetsub_min) {
            $rangesubnets =
                array_merge($rangesubnets,
                    self::ip_range_to_subnet_array($startip,
                        ip_before($targetsub_min)));
        }

        // Add in the subnet we found before, to preserve ordering
        $rangesubnets[] = "{$targetsub_min}/{$cidr}";

        // And some more logic that will search after the subnet we found to fill in
        // to the end of the range.
        if ($endip != $targetsub_max) {
            $rangesubnets =
                array_merge($rangesubnets,
                    self::ip_range_to_subnet_array(self::ip_after($targetsub_max), $endip));
        }

        return $rangesubnets;
    }
}