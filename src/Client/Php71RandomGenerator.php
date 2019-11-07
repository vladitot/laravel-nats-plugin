<?php
namespace Vladitot\Nats;
/**
 * Class Php71RandomGenerator
 *
 * @package Nats
 */
class Php71RandomGenerator
{
    /**
     * A simple wrapper on random_bytes.
     *
     * @param integer $len Length of the string.
     *
     * @return string Random string.
     * @throws \Exception
     */
    public function generateString($len)
    {
        return bin2hex(random_bytes($len));
    }
}