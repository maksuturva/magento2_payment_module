<?php
namespace Svea\Maksuturva\Model\Gateway;
class Exception extends \Exception
{
public function __construct($errors, $code = null)
{
$message = '';
foreach ($errors as $error) {
$message .= $error . ', ';
}

parent::__construct($message, $code);
}
}