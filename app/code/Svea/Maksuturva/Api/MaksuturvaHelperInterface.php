<?php
namespace Svea\Maksuturva\Api;
interface MaksuturvaHelperInterface{
/**
 * generate backend payment Id
 *
 * @return string
 */
public static function generatePaymentId();

/**
 * generate backend referencenumber acccording to order increment Id
 *
 * @param int
 * @return string
 */
public function getPmtReferenceNumber($number);

}