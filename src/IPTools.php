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

    /**
     * Get IP address as binary.
     *
     * @param string $ip
     * @return string IP address in binary.
     */
    static public function getIPAddressBinary(string $ip)
    {
        return self::ipAddressCalculation($ip, '%08b');
    }

    /**
     * Get IP address as hexadecimal.
     *
     * @param string $ip
     * @return string IP address in hex.
     */
    static public function getIPAddressHex(string $ip)
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
    static private function ipAddressCalculation($ip, $format, $separator = '')
    {
        return implode($separator, array_map(
            function ($x) use ($format) {
                return sprintf($format, $x);
            },
            self::getIpQuads($ip)
        ));
    }
}
