<?php
require_once 'Dotdigitalgroup' . DS . 'Email' . DS . 'controllers' . DS . 'ResponseController.php';

class Dotdigitalgroup_Email_EmailController extends Dotdigitalgroup_Email_ResponseController
{
    /**
     * wishlist
     */
    public function wishlistAction()
    {
        //authenticate
        $this->authenticate();
        $this->loadLayout();
        $wishlist = $this->getLayout()->createBlock('ddg_automation/wishlist', 'connector_wishlist', array(
            'template' => 'connector/wishlist.phtml'
        ));
        $this->getLayout()->getBlock('content')->append($wishlist);
        $this->renderLayout();
        $this->checkContentNotEmpty($wishlist->toHtml(), false);
    }

    /**
     * Generate coupon for a coupon code id.
     */
    public function couponAction()
    {
        $this->authenticate();
        $this->loadLayout();
        //page root template
        if ($root = $this->getLayout()->getBlock('root')) {
            $root->setTemplate('page/blank.phtml');
        }
        //content template
        $coupon = $this->getLayout()->createBlock('ddg_automation/coupon', 'connector_coupon', array(
            'template' => 'connector/coupon.phtml'
        ));
        $this->checkContentNotEmpty($coupon->toHtml(), false);
        $this->getLayout()->getBlock('content')->append($coupon);
        $this->renderLayout();
    }

    /**
     * Basket page to display the user items with specific email.
     */
    public function basketAction()
    {
        //authenticate
        $this->authenticate();
        $this->loadLayout();
        if ($root = $this->getLayout()->getBlock('root')) {
            $root->setTemplate('page/blank.phtml');
        }
        $basket = $this->getLayout()->createBlock('ddg_automation/basket', 'connector_basket', array(
            'template' => 'connector/basket.phtml'
        ));
        $this->getLayout()->getBlock('content')->append($basket);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }

    public function reviewAction()
    {
        //authenticate
        $this->authenticate();
        $this->loadLayout();
        $review = $this->getLayout()->createBlock('ddg_automation/order', 'connector_review', array(
            'template' => 'connector/review.phtml'
        ));
        $this->getLayout()->getBlock('content')->append($review);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }


    /**
     * Callback action for the automation studio.
     */
    public function callbackAction()
    {
        $code = $this->getRequest()->getParam('code', false);
        $userId = $this->getRequest()->getParam('state');
        $adminUser = Mage::getModel('admin/user')->load($userId);
        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, true);

        //callback url
        $callback    = $baseUrl . 'connector/email/callback';

        if ($code) {
            $data = 'client_id='    . Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CLIENT_ID) .
                '&client_secret='   . Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CLIENT_SECRET_ID) .
                '&redirect_uri='    . $callback .
                '&grant_type=authorization_code' .
                '&code='            . $code;


            $url = Mage::helper('ddg/config')->getTokenUrl();
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POST, count($data));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array ('Content-Type: application/x-www-form-urlencoded'));


            $response = json_decode(curl_exec($ch));
            if ($response === false) {
                Mage::helper('ddg')->rayLog('100', 'Automaion studio number not found : ' . serialize($response));
                Mage::helper('ddg')->log("Error Number: " . curl_errno($ch));
            }

            //save the refresh token to the admin user
            $adminUser->setRefreshToken($response->refresh_token)->save();
        }
        //redirect to automation index page
        $this->_redirectReferer(Mage::helper('adminhtml')->getUrl('adminhtml/email_automation/index'));
    }
}