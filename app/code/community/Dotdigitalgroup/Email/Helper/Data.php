<?php

class Dotdigitalgroup_Email_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function isEnabled($website = 0)
    {
        $website = Mage::app()->getWebsite($website);
        return (bool)$website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED);
    }

    /**
     * @param int/object $website
     * @return mixed
     */
    public function getApiUsername($website = 0)
    {
        $website = Mage::app()->getWebsite($website);

        return $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_USERNAME);
    }

    public function getApiPassword($website = 0)
    {
        $website = Mage::app()->getWebsite($website);

        return $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_PASSWORD);
    }

    public function auth($authRequest)
    {
        if ($authRequest != Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_PASSCODE)) {
            $this->getRaygunClient()->Send('Authentication failed with code :' . $authRequest);
            //throw new Exception('Authentication failed : ' . $authRequest);
            return false;
        }
        return true;
    }

    public function getMappedCustomerId()
    {
        return Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_CUSTOMER_ID);
    }

    public function getMappedOrderId()
    {
        return Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_LAST_ORDER_ID);
    }

    public function getPasscode()
    {
        return Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_PASSCODE);
    }

    public function getLastOrderId()
    {
        return Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_LAST_ORDER_ID);

    }

    public function getLastQuoteId()
    {
        return Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_LAST_QUOTE_ID);

    }

    public function log($data, $level = Zend_Log::DEBUG, $filename = 'api.log')
    {
        if ($this->getDebugEnabled()) {
            $filename = 'connector_' . $filename;

            Mage::log($data, $level, $filename, $force = true);
        }
    }

    public function getDebugEnabled()
    {
        return (bool) Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_ADVANCED_DEBUG_ENABLED);
    }

    public function getConnectorVersion()
    {
        $modules = (array) Mage::getConfig()->getNode('modules')->children();
        if (isset($modules['Dotdigitalgroup_Email'])) {
            $moduleName = $modules['Dotdigitalgroup_Email'];
            return $moduleName->version;
        }
        return '';
    }


    public function getPageTrackingEnabled()
    {
        return (bool)Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_PAGE_TRACKING_ENABLED);
    }

    public function getRoiTrackingEnabled()
    {
        return (bool)Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_ROI_TRACKING_ENABLED);
    }

    public function getResourceAllocationEnabled()
    {
        return (bool)Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_RESOURCE_ALLOCATION);
    }

    public function getMappedStoreName($website)
    {
        $mapped = $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_CUSTOMER_STORENAME);
        $storeName = ($mapped)? $mapped : '';
        return  $storeName;
    }

    /**
     * Get the contact id for the custoemer based on website id.
     * @param $email
     * @param $websiteId
     *
     * @return bool
     */
    public function getContactId($email, $websiteId)
    {
	    $contact = Mage::getModel('ddg_automation/contact')->loadByCustomerEmail($email, $websiteId);
	    if ($contactId = $contact->getContactId()) {
		    return $contactId;
	    }

        $client = $this->getWebsiteApiClient($websiteId);
        $response = $client->postContacts($email);

        if (isset($response->message))
            return false;
	    //save contact id
		if (isset($response->id)){
			$contact->setContactId($response->id)
				->save();
		}
        return $response->id;
    }

    public function getCustomerAddressBook($website)
    {
        $website = Mage::app()->getWebsite($website);
        return $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID);
    }

    public function getSubscriberAddressBook($website)
    {
        $website = Mage::app()->getWebsite($website);
        return $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID);
    }

    public function getGuestAddressBook($website)
    {
        $website = Mage::app()->getWebsite($website);
        return $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_GUEST_ADDRESS_BOOK_ID);
    }

    /**
     * @return $this
     */
    public  function allowResourceFullExecution()
    {
        if ($this->getResourceAllocationEnabled()) {

            /* it may be needed to set maximum execution time of the script to longer,
             * like 60 minutes than usual */
            set_time_limit(7200);

            /* and memory to 512 megabytes */
            ini_set('memory_limit', '512M');
        }
        return $this;
    }
    public function convert($size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }

    /**
     * @return string
     */
    public function getStringWebsiteApiAccounts()
    {
        $accounts = array();
        foreach (Mage::app()->getWebsites() as $website) {
            $websiteId = $website->getId();
            $apiUsername = $this->getApiUsername($website);
            $accounts[$apiUsername] = $apiUsername . ', websiteId: ' . $websiteId . ' name ' . $website->getName();
        }
        return implode('</br>', $accounts);
    }

    /**
     * @param int $website
     *
     * @return array|mixed
     * @throws Mage_Core_Exception
     */
    public function getCustomAttributes($website = 0)
    {
        $website = Mage::app()->getWebsite($website);
        $attr = $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_CUSTOM_DATAFIELDS);

        if (!$attr)
            return array();

        return unserialize($attr);
    }


	/**
	 * Enterprise custom datafields attributes.
	 * @param int $website
	 *
	 * @return array
	 * @throws Mage_Core_Exception
	 */
	public function getEnterpriseAttributes( $website = 0) {
		$website = Mage::app()->getWebsite($website);
		$result = array();
		$attrs = $website->getConfig('connector_data_mapping/enterprise_data');
		//get individual mapped keys
		foreach ( $attrs as $key => $one ) {
			$config = $website->getConfig('connector_data_mapping/enterprise_data/' . $key);
			//check for the mapped field
			if ($config)
				$result[$key] = $config;
		}

		if (empty($result))
			return false;
		return $result;
	}

    /**
     * @param $path
     * @param null|string|bool|int|Mage_Core_Model_Website $websiteId
     * @return mixed
     */
    public function getWebsiteConfig($path, $websiteId = 0)
    {
        $website = Mage::app()->getWebsite($websiteId);
        return $website->getConfig($path);
    }

    /**
     * Api client by website.
     *
     * @param mixed $website
     *
     * @return bool|Dotdigitalgroup_Email_Model_Apiconnector_Client
     */
    public function getWebsiteApiClient($website = 0)
    {
        if (! $apiUsername = $this->getApiUsername($website) || ! $apiPassword = $this->getApiPassword($website))
            return false;

        $client = Mage::getModel('ddg_automation/apiconnector_client');
        $client->setApiUsername($this->getApiUsername($website))
            ->setApiPassword($this->getApiPassword($website));

        return $client;
    }

    /**
     * Retrieve authorisation code.
     */
    public function getCode()
    {
        $adminUser = Mage::getSingleton('admin/session')->getUser();
        $code = $adminUser->getEmailCode();

        return $code;
    }

    /**
     * Autorisation url for OAUTH.
     * @return string
     */
    public function getAuthoriseUrl()
    {
        $clientId = Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CLIENT_ID);

	    //callback uri if not set custom
	    $redirectUri = $this->getRedirectUri();
	    $redirectUri .= 'connector/email/callback';
	    $adminUser = Mage::getSingleton('admin/session')->getUser();
        //query params
        $params = array(
            'redirect_uri' => $redirectUri,
            'scope' => 'Account',
            'state' => $adminUser->getId(),
            'response_type' => 'code'
        );

        $authorizeBaseUrl = Mage::helper('ddg/config')->getAuthorizeLink();
        $url = $authorizeBaseUrl . http_build_query($params) . '&client_id=' . $clientId;

        return $url;
    }

	public function getRedirectUri()
	{
		$callback = Mage::helper('ddg/config')->getCallbackUrl();

		return $callback;
	}

    /**
     * order status config value
     * @param int $website
     * @return mixed order status
     */
    public function getConfigSelectedStatus($website = 0)
    {
        $status = $this->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_SYNC_ORDER_STATUS, $website);
        if($status)
            return explode(',',$status);
        else
            return false;
    }

    public function getConfigSelectedCustomOrderAttributes($website = 0)
    {
        $customAttributes = $this->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOM_ORDER_ATTRIBUTES, $website);
        if($customAttributes)
            return explode(',',$customAttributes);
        else
            return false;
    }

    public function getConfigSelectedCustomQuoteAttributes($website = 0)
    {
        $customAttributes = $this->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOM_QUOTE_ATTRIBUTES, $website);
        if($customAttributes)
            return explode(',',$customAttributes);
        else
            return false;
    }

    /**
     * check sweet tooth installed/active status
     * @return boolean
     */
    public function isSweetToothEnabled()
    {
        return (bool)Mage::getConfig()->getModuleConfig('TBT_Rewards')->is('active', 'true');
    }

    /**
     * check sweet tooth installed/active status and active status
     * @param Mage_Core_Model_Website $website
     * @return boolean
     */
    public function isSweetToothToGo($website)
    {
        $stMappingStatus = $this->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_SWEETTOOTH_ACTIVE, $website);
        if($stMappingStatus && $this->isSweetToothEnabled()) return true;
        return false;
    }

    public function setConnectorContactToReImport($customerId)
    {
        $contactModel = Mage::getModel('ddg_automation/contact');
        $contactModel
            ->loadByCustomerId($customerId)
            ->setEmailImported(Dotdigitalgroup_Email_Model_Contact::EMAIL_CONTACT_NOT_IMPORTED)
            ->save();
    }

    /**
     * Diff between to times;
     *
     * @param $time1
     * @param $time2
     * @return int
     */
    public function dateDiff($time1, $time2=NULL) {
        if (is_null($time2)) {
            $time2 = Mage::getModel('core/date')->date();
        }
        $time1 = strtotime($time1);
        $time2 = strtotime($time2);
        return $time2 - $time1;
    }


    /**
     * Disable website config when the request is made admin area only!
     * @param $path
     *
     * @throws Mage_Core_Exception
     */
    public function disableConfigForWebsite($path)
    {
        $scopeId = 0;
        if ($website = Mage::app()->getRequest()->getParam('website')) {
            $scope = 'websites';
            $scopeId = Mage::app()->getWebsite($website)->getId();
        } else {
            $scope = "default";
        }
        $config = Mage::getConfig();
        $config->saveConfig($path, 0, $scope, $scopeId);
        $config->cleanCache();
    }

    /**
     * number of customers with duplicate emails, emails as total number
     * @return Mage_Customer_Model_Resource_Customer_Collection
     */
    public function getCustomersWithDuplicateEmails( ) {
        $customers = Mage::getModel('customer/customer')->getCollection();

        //duplicate emails
        $customers->getSelect()
            ->columns(array('emails' => 'COUNT(e.entity_id)'))
            ->group('email')
            ->having('emails > ?', 1);

        return $customers;
    }

    /**
     * Create new raygun client.
     *
     * @return bool|\Raygun4php\RaygunClient
     */
    public function getRaygunClient()
    {
        $code = Mage::getstoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_RAYGUN_APPLICATION_CODE);

        if ($this->raygunEnabled()) {
            require_once Mage::getBaseDir('lib') . DS . 'Raygun4php' . DS  . 'RaygunClient.php';
            return new Raygun4php\RaygunClient($code, false, true);
        }

        return false;
    }

    /**
     * Raygun logs.
     * @param int $errno
     * @param $message
     * @param string $filename
     * @param int $line
     * @param array $tags
     *
     * @return int|null
     */
    public function rayLog($errno = 100, $message, $filename = 'helper/data.php', $line = 1, $tags = array())
    {
        $client = $this->getRaygunClient();
        if ($client) {
            //use tags to log the client baseurl
            if (empty($tags))
                $tags = array(Mage::getBaseUrl('web'));
            //send message
            $code = $client->SendError( $errno, $message, $filename, $line, $tags );

            return $code;
        }

        return false;
    }


    /**
     * check for raygun application and if enabled.
     * @param int $websiteId
     *
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function raygunEnabled($websiteId = 0)
    {
        $website = Mage::app()->getWebsite($websiteId);

        return  (bool)$website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_RAYGUN_APPLICATION_CODE);

    }

    /**
     * Generate the baseurl for the default store
     * dynamic content will be displayed
     * @return string
     * @throws Mage_Core_Exception
     */
	public function generateDynamicUrl()
	{
		$website = Mage::app()->getRequest()->getParam('website', false);

		//set website url for the default store id
		$website = ($website)? Mage::app()->getWebsite( $website ) : 0;

		$defaultGroup = Mage::app()->getWebsite($website)
		                    ->getDefaultGroup();

		if (! $defaultGroup)
			return $mage = Mage::app()->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

		//base url
		$baseUrl = Mage::app()->getStore($defaultGroup->getDefaultStore())->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);

		return $baseUrl;

	}

    /**
     *
     *
     * @param int $store
     * @return mixed
     */
    public function isNewsletterSuccessDisabled($store = 0)
    {
        return Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_DISABLE_NEWSLETTER_SUCCESS, $store);
    }

    /**
     * get sales_flat_order table description
     *
     * @return array
     */
    public function getOrderTableDescription()
    {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $salesTable = $resource->getTableName('sales/order');

        return $readConnection->describeTable($salesTable);
    }

    /**
     * get sales_flat_quote table description
     *
     * @return array
     */
    public function getQuoteTableDescription()
    {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $table = $resource->getTableName('sales/quote');
        return $readConnection->describeTable($table);
    }

    /**
     * @return bool
     */
    public function getEasyEmailCapture()
    {
        return Mage::getStoreConfigFlag(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_EMAIL_CAPTURE);
    }

    /**
     * get feefo logon config value
     *
     * @return mixed
     */
    public function getFeefoLogon()
    {
        return $this->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_REVIEWS_FEEFO_LOGON);
    }

    /**
     * get feefo reviews limit config value
     *
     * @return mixed
     */
    public function getFeefoReviewsPerProduct()
    {
        return $this->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_REVIEWS_FEEFO_REVIEWS);
    }

    /**
     * get feefo logo template config value
     *
     * @return mixed
     */
    public function getFeefoLogoTemplate()
    {
        return $this->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_REVIEWS_FEEFO_TEMPLATE);
    }

    /**
     * update data fields
     *
     * @param $email
     * @param Mage_Core_Model_Website $website
     * @param $storeName
     */
    public function updateDataFields($email, Mage_Core_Model_Website $website, $storeName)
    {
        $data = array();
        if($store_name = $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME)){
            $data[] = array(
                'Key' => $store_name,
                'Value' => $storeName
            );
        }
        if($website_name = $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CUSTOMER_WEBSITE_NAME)){
            $data[] = array(
                'Key' => $website_name,
                'Value' => $website->getName()
            );
        }
        if(!empty($data)){
            //update data fields
            $client = $this->getWebsiteApiClient($website);
            $client->updateContactDatafieldsByEmail($email, $data);
        }
    }

    /**
     * check connector SMTP installed/active status
     * @return boolean
     */
    public function isSmtpEnabled()
    {
        return (bool)Mage::getConfig()->getModuleConfig('Ddg_Transactional')->is('active', 'true');
    }

	/**
	 * Is magento enterprise.
	 * @return bool
	 */
	public function isEnterprise()
	{
		return Mage::getConfig ()->getModuleConfig ( 'Enterprise_Enterprise' ) && Mage::getConfig ()->getModuleConfig ( 'Enterprise_AdminGws' ) && Mage::getConfig ()->getModuleConfig ( 'Enterprise_Checkout' ) && Mage::getConfig ()->getModuleConfig ( 'Enterprise_Customer' );

	}

    public function getTemplateList()
    {
        $client = $this->getWebsiteApiClient(Mage::app()->getWebsite());
        if(!$client)
            return array();

        $templates = $client->getApiTemplateList();
        $fields[] = array('value' => '', 'label' => '');
        foreach ( $templates as $one ) {
            if ( isset( $one->id ) ) {
                $fields[] = array(
                    'value' => $one->id,
                    'label' => $this->__( addslashes( $one->name ) )
                );
            }
        }
        return $fields;
    }

	/**
	 * Update last quote id datafield.
	 * @param $quoteId
	 * @param $email
	 * @param $websiteId
	 */
	public function updateLastQuoteId($quoteId, $email, $websiteId)
	{
		$client = $this->getWebsiteApiClient($websiteId);
		//last quote id config data mapped
		$quoteIdField = $this->getLastQuoteId();

		$data[] = array(
			'Key' => $quoteIdField,
			'Value' => $quoteId
		);
		//update datafields for conctact
		$client->updateContactDatafieldsByEmail($email, $data);
	}

	/**
	 * Remove code and disable Raygun.
	 */
	public function disableRaygun()
	{
		$config = new Mage_Core_Model_Config();
		$config->saveConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_RAYGUN_APPLICATION_CODE, '');
		Mage::getConfig()->cleanCache();
	}

	public function enableRaygunCode()
	{
		$curl = new Varien_Http_Adapter_Curl();
		$curl->setConfig(array(
			'timeout'   => 2
		));
		$curl->write(Zend_Http_Client::GET, Dotdigitalgroup_Email_Helper_Config::RAYGUN_API_CODE_URL, '1.0');
		$data = $curl->read();

		if ($data === false) {
			return false;
		}
		$data = preg_split('/^\r?$/m', $data, 2);
		$data = trim($data[1]);
		$curl->close();

		$xml  = new SimpleXMLElement($data);
		$raygunCode = $xml->code;

		//not found
		if (!$raygunCode)
			return;

		$config = new Mage_Core_Model_Config();
		$config->saveConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_RAYGUN_APPLICATION_CODE, $raygunCode);
	}

	/**
	 * Get the config id by the automation type.
	 * @param $automationType
	 * @param int $websiteId
	 *
	 * @return mixed
	 */
	public function getAutomationIdByType($automationType, $websiteId = 0)
	{
		$path = constant('Dotdigitalgroup_Email_Helper_Config::' . $automationType);
		$automationCampaignId = $this->getWebsiteConfig($path, $websiteId);

		return $automationCampaignId;
	}

}
