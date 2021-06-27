<?php
namespace Svea\Maksuturva\Model\Gateway;

use Magento\Framework\Convert\Xml;
use Magento\Payment\Model\InfoInterface;
use Svea\Maksuturva\Model\Config\Config;

abstract class Base extends \Magento\Framework\Model\AbstractModel
{
    const STATUS_QUERY_NOT_FOUND = "00";
    const STATUS_QUERY_FAILED = "01";
    const STATUS_QUERY_WAITING = "10";
    const STATUS_QUERY_UNPAID = "11";
    const STATUS_QUERY_UNPAID_DELIVERY = "15";
    const STATUS_QUERY_PAID = "20";
    const STATUS_QUERY_PAID_DELIVERY = "30";
    const STATUS_QUERY_COMPENSATED = "40";
    const STATUS_QUERY_PAYER_CANCELLED = "91";
    const STATUS_QUERY_PAYER_CANCELLED_PARTIAL = "92";
    const STATUS_QUERY_PAYER_CANCELLED_PARTIAL_RETURN = "93";
    const STATUS_QUERY_PAYER_RECLAMATION = "95";
    const STATUS_QUERY_CANCELLED = "99";

    const EXCEPTION_CODE_ALGORITHMS_NOT_SUPORTED = '00';
    const EXCEPTION_CODE_URL_GENERATION_ERRORS = '01';
    const EXCEPTION_CODE_FIELD_ARRAY_GENERATION_ERRORS = '02';
    const EXCEPTION_CODE_REFERENCE_NUMBER_UNDER_100 = '03';
    const EXCEPTION_CODE_FIELD_MISSING = '04';
    const EXCEPTION_CODE_INVALID_ITEM = '05';
    const EXCEPTION_CODE_PHP_CURL_NOT_INSTALLED = '06';
    const EXCEPTION_CODE_HASHES_DONT_MATCH = '07';

    const PAYMENT_SERVICE_URN = 'NewPaymentExtended.pmt';
    const PAYMENT_METHOD_URN = 'GetPaymentMethods.pmt';
    const PAYMENT_STATUS_QUERY_URN = 'PaymentStatusQuery.pmt';
    const PAYMENT_ADD_DELIVERYINFO_URN = 'addDeliveryInfo.pmt';

    const PAYMENT_CANCEL_OK = "00";
    const PAYMENT_CANCEL_NOT_FOUND = "20";
    const PAYMENT_CANCEL_ALREADY_SETTLED = "30";
    const PAYMENT_CANCEL_MISMATCH = "31";
    const PAYMENT_CANCEL_ERROR = "90";
    const PAYMENT_CANCEL_FAILED = "99";

    protected $_hashAlgoDefined = null;
    protected $_pmt_hashversion = null;
    protected $_statusQueryBaseUrl;

    protected $_baseUrl = null;

    protected $_charset = 'UTF-8';

    protected $_charsethttp = 'UTF-8';

    private $_errors = array();

    /**
     * @var Xml
     */
    private $xmlConvert;

    /**
     * @var Config
     */
    protected $config;

    protected $_loggerHandler;

    /**
     * Base constructor.
     * @param Xml $xmlConvert
     * @param Config $config
     * @throws Exception
     */
    public function __construct(
        Xml $xmlConvert,
        Config $config
    ) {
        $this->xmlConvert = $xmlConvert;
        $this->config = $config;
        if (!function_exists("curl_init")) {
            throw new \Svea\Maksuturva\Model\Gateway\Exception(array("cURL is needed in order to communicate with the maksuturva's server. Check your PHP installation."), self::EXCEPTION_CODE_PHP_CURL_NOT_INSTALLED);
        }

        $hashAlgos = hash_algos();

        if (in_array("sha512", $hashAlgos)) {
            $this->_pmt_hashversion = 'SHA-512';
            $this->_hashAlgoDefined = "sha512";
        } else if (in_array("sha256", $hashAlgos)) {
            $this->_pmt_hashversion = 'SHA-256';
            $this->_hashAlgoDefined = "sha256";
        } else if (in_array("sha1", $hashAlgos)) {
            $this->_pmt_hashversion = 'SHA-1';
            $this->_hashAlgoDefined = "sha1";
        } else if (in_array("md5", $hashAlgos)) {
            $this->_pmt_hashversion = 'MD5';
            $this->_hashAlgoDefined = "md5";
        } else {
            throw new \Svea\Maksuturva\Model\Gateway\Exception(array('the hash algorithms SHA-512, SHA-256, SHA-1 and MD5 are not supported!'), self::EXCEPTION_CODE_ALGORITHMS_NOT_SUPORTED);
        }
    }

    public function sveaLoggerInfo($info)
    {
        $this->getSveaLoggerHandler()->info(json_encode($info));
    }

    public function sveaLoggerError($error)
    {
        $this->getSveaLoggerHandler()->err(json_encode($error));
    }

    protected function getSveaLoggerHandler()
    {
        if(!$this->_loggerHandler) {
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/svea-payment-module.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $this->_loggerHandler = $logger;
        }
        return $this->_loggerHandler;
    }

    public function generateReturnHash($hashData)
    {
        $hashString = '';
        foreach ($hashData as $key => $data) {
            if ($key != 'pmt_hash') {
                $hashString .= $data . '&';
            }
        }
        $hashString .= $this->secretKey . '&';

        return strtoupper(hash($this->_hashAlgoDefined, $hashString));
    }

    public function getErrors()
    {
        return $this->_errors;
    }

    protected function getPostResponse($url, $data, $timeout = 120)
    {
        $request = curl_init($url);
        curl_setopt($request, CURLOPT_HEADER, 0);
        curl_setopt($request, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($request, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($request, CURLOPT_POST, 1);
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($request, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($request, CURLOPT_POSTFIELDS, $data);

        $res = curl_exec($request);
        if ($res === false) {
            $errmsg = "Failed to communicate with Svea Payments API. Please check the network connection and settings. URL: " . $url . " ERROR MESSAGE: " . curl_error($request);
            $this->sveaLoggerError($errmsg);
            throw new \Svea\Maksuturva\Model\Gateway\Exception(array($errmsg));
        }
        curl_close($request);
        return $res;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    protected function _verifyStatusQueryResponse($data)
    {
        $requiredFields = array(
            "pmtq_action",
            "pmtq_version",
            "pmtq_sellerid",
            "pmtq_id",
            "pmtq_amount",
            "pmtq_returncode",
            "pmtq_returntext",
            "pmtq_sellercosts",
            "pmtq_paymentmethod",
            "pmtq_escrow",
            "pmtq_certification",
            "pmtq_paymentdate"
        );
        $optionalFields = array(
            "pmtq_sellercosts",
            "pmtq_paymentmethod",
            "pmtq_escrow",
            "pmtq_certification",
            "pmtq_paymentdate"
        );

        foreach ($requiredFields as $requiredField) {
            if (!isset($data[$requiredField]) && !in_array($requiredField, $optionalFields)) {
                return false;
            } elseif (!isset($data[$requiredField])) {
                continue;
            }
        }

        return true;
    }

    /**
     * @param $response
     * @return array
     * @throws Exception
     */
    protected function processCancelPaymentResponse($response)
    {
        $hashFields = array(
            'pmtc_action',
            'pmtc_version',
            'pmtc_sellerid',
            'pmtc_id',
            'pmtc_returntext',
            'pmtc_returncode'
        );

        $parsedResponse = $this->parseResponse($response);

        /** If response was ok, check hash. */
        if ($parsedResponse['pmtc_returncode']=== self::PAYMENT_CANCEL_OK) {

            $calcHash = $this->calculateHash($parsedResponse, $hashFields);

            if ($calcHash !== $parsedResponse['pmtc_hash']) {
                throw new Exception(
                    array("The authenticity of the answer could't be verified. Hashes didn't match.
                    Verify cancel in Maksuturva account and make offline refund, if needed."
                    ),
                    self::EXCEPTION_CODE_HASHES_DONT_MATCH
                );
            }
        }

        $this->sveaLoggerInfo($parsedResponse['pmtc_action'] . " result for transaction " . $parsedResponse['pmtc_id'] . " is '" . $parsedResponse['pmtc_returntext'] . "'" );

        switch($parsedResponse['pmtc_returncode']) {
            case self::PAYMENT_CANCEL_OK:
                $error = false;
                break;
            case self::PAYMENT_CANCEL_NOT_FOUND:
                $error = true;
                $msg = "Payment not found";
                break;
            case self::PAYMENT_CANCEL_ALREADY_SETTLED:
                if ($this->config->canCancelSettled()){
                    $error = false;
                } else {
                    $error = true;
                    $msg = "Payment already settled and cannot be cancelled.";
                }
                break;
            case self::PAYMENT_CANCEL_MISMATCH:
                $error = true;
                $msg = "Cancel parameters from seller and payer do not match";
                break;
            case self::PAYMENT_CANCEL_ERROR:
                $error = true;
                $msg = "Errors in input data";
                if (isset($parsedResponse['errors'])) {
                    $msg .= PHP_EOL;
                    $msg .= implode(PHP_EOL, $parsedResponse['errors']);
                }
                break;
            case self::PAYMENT_CANCEL_FAILED:
                $error = true;
                $msg = "Payment cancellation failed.";
                if (isset($parsedResponse['pmtc_returntext'])) {
                    $msg .= PHP_EOL . $parsedResponse['pmtc_returntext'];
                }
                break;
            default:
                $error = true;
                $msg = "Refund failed";
                break;
        }

        /** If canceling failed, throw error */
        if ($error) {
            $this->sveaLoggerError("Cancelling payment failed for transaction " .  $parsedResponse['pmtc_id'] . ", reason " . $msg);
            throw new Exception(
                [$msg],
                $parsedResponse['pmtc_returncode']
            );
        }

        return $parsedResponse;
    }

    /**
     * @param $response
     * @return array
     */
    private function parseResponse($response)
    {
        $xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOERROR|LIBXML_NOWARNING);
        return $this->xmlConvert->xmlToAssoc($xml);
    }

    /**
     * @param array $fields
     * @param array $hashFields
     * @return string
     */
    public function calculateHash($fields, $hashFields)
    {
        /** Generate hash */
        $hashString = '';
        foreach ($hashFields as $hashField) {
            if (isset($fields[$hashField]) && !empty($fields[$hashField])) {
                $hashString .= $fields[$hashField] . '&';
            }
        }
        $hashString .= $this->secretKey . '&';

        return strtoupper(hash($this->_hashAlgoDefined, $hashString));
    }

    /**
     * @param InfoInterface $payment
     * @return mixed
     */
    public function getTransactionId($payment)
    {
        return $payment->getParentTransactionId() ? $payment->getParentTransactionId() : $payment->getLastTransId();
    }
}
