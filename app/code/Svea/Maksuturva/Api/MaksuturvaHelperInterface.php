<?php
namespace Svea\Maksuturva\Api;
interface MaksuturvaHelperInterface{
/**
 * generate Maksuturva payment Id
 *
 * @return string
 */
public static function generatePaymentId();

/**
 * generate Maksuturva referencenumber acccording to order increment Id
 *
 * @param int
 * @return string
 */
public function getPmtReferenceNumber($number);

}