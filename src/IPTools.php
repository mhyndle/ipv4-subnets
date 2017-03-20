<?php
namespace IPv4Subnets;

class IPTools {
    /**
     * Returns array of IP quads.
     *
     * @param string $ip
     * @return array
     */
    static public function getIpQuads(string $ip) : array
    {
        return explode('.', $ip);
    }

    /**
     * Returns TRUE if $ip1 is lower than $ip2. Otherwise FALSE is returned.
     *
     * @param string $ip1
     * @param string $ip2
     * @return bool
     */
    static public function ipIsLowerThan(string $ip1, string $ip2) : bool
    {
        return IPRangeToCIDRConverter::ip_less_than($ip1, $ip2);
    }

    /**
     * Get IP address as binary.
     *
     * @param string $ip
     * @return string IP address in binary.
     */
    static public function getIPAddressBinary(string $ip) : string
    {
        return self::ipAddressCalculation($ip, '%08b');
    }

    /**
     * Get IP address as hexadecimal.
     *
     * @param string $ip
     * @return string IP address in hex.
     */
    static public function getIPAddressHex(string $ip) : string
    {
        return self::ipAddressCalculation($ip, '%02X');
    }

    /**
     * Calculate IP address for formatting.
     *
     * @param string $ip
     * @param string $format sprintf format to determine if decimal, hex or binary.
     * @param string $separator implode separator for formatting quads vs hex and binary.
     * @return string formatted IP address.
     */
    static private function ipAddressCalculation($ip, $format, $separator = '') : string
    {
        return implode($separator, array_map(
            function ($x) use ($format) {
                return sprintf($format, $x);
            },
            self::getIpQuads($ip)
        ));
    }

    static public function getLowestIP(array $ips)
    {
        $lowestIP = '255.255.255.255';

        foreach ($ips as $ip) {
            if (self::ipIsLowerThan($ip, $lowestIP)) {
                $lowestIP = $ip;
            }
        }

        return $lowestIP;
    }

    static public function getHighestIP(array $ips)
    {
        $highestIP = '0.0.0.0';

        foreach ($ips as $ip) {
            if (!self::ipIsLowerThan($ip, $highestIP)) {
                $highestIP = $ip;
            }
        }

        return $highestIP;
    }
}
