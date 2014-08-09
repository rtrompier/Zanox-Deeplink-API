<?php
    require_once("ZanoxDeepLink.php");

    try {

        $zanoxDeepLink = new ZanoxDeepLink('LOGIN', 'PASSWORD', 'ADSPACE', 'ADVERTISER');
        echo $zanoxDeepLink->getDeeplink('PRODUCT_URL');

    } catch (Exception $e) {

        echo 'Error : ',  $e->getMessage(), "\n";

    }


?>