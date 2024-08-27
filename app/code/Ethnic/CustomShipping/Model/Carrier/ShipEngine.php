<?php

declare(strict_types=1);

namespace Ethnic\CustomShipping\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateResult\Error;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Psr\Log\LoggerInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Ethnic\CustomShipping\Model\Shipping;
use Magento\Checkout\Model\Session;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Shipping\Model\Rate\Result;
use Magento\Framework\App\RequestInterface;

class ShipEngine extends AbstractCarrier implements CarrierInterface
{
    protected const CODE = 'shipengine';

    /**
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateFactory
     * @param MethodFactory $rateMethodFactory
     * @param Shipping $shipping
     * @param Session $checkoutSession
     * @param Curl $curl
     * @param RequestInterface $request
     * @param array $data
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected ErrorFactory $rateErrorFactory,
        protected LoggerInterface $logger,
        protected ResultFactory $rateFactory,
        protected MethodFactory $rateMethodFactory,
        protected Shipping $shipping,
        public Session $checkoutSession,
        public Curl $curl,
        protected RequestInterface $request,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods(): array
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    /**
     * Collect and get rates
     *
     * @param RateRequest $request
     * @return Result|bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function collectRates(RateRequest $request): Result|bool
    {
        $result = $this->rateFactory->create();
        $method = $this->rateMethodFactory->create();
        $country = $this->checkoutSession->getQuote()->getShippingAddress()->getCountryId();

        if ($country == $this->shipping->getCountryId() && $this->shipping->getEnable()) {
            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));
            /* Set method name */
            $method->setMethod($this->_code);
            $method->setMethodTitle($this->getConfigData('name'));
            $data= $this->getDistancePriceAddress();
            $street_address = $request->getDestStreet();
            $address_line = explode(' ', $street_address);
            $address1 = !empty($address_line[0]) ? $address_line[0] : $street_address;
            $address2 = !empty($address_line[1]) ? $address_line[1] : '';
            $zip = $request->getDestPostcode();
            $city = $request->getDestCity();
            $country_code = $request->getDestCountryId();
            $state = $request->getDestRegionCode();
            $delivery_Address = [
                'state' => $state,
                'zip' => $zip,
                'city' => $city,
                'address_1' => $address1,
                'address_2' => $address2,
                'country_code' => $country_code
            ];

            $deliveryAddress = implode(",", $delivery_Address);
            $pickupAddress =  $this->shipping->getState() . ' ' .
                $this->shipping->getPostCode() . ' ' .
                $this->shipping->getCity() . ',' .
                $this->shipping->getStreet();

            $curlRequest = $this->getApiRates();
            // if ((bool) $curlRequest['price'] > 0) {
            //     $method->setCost($curlRequest['price']);
            //     $method->setPrice($curlRequest['price']);
            //     $result->append($method);
            // }
        }
        return $result;
    }

    /**
     * Processing additional validation to check is carrier applicable.
     *
     * @param \Magento\Framework\DataObject $request
     * @return $this|bool|\Magento\Framework\DataObject
     */
    public function proccessAdditionalValidation(\Magento\Framework\DataObject $request): bool
    {
        return true;
    }

    /**
     * Get Curl Request
     *
     * @param PickupAddress $pickupAddress
     * @param DeliveryAddress $deliveryAddress
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return array
     */
    public function getApiRates($request): array
    {
        $customrelog = $this->customLogger();
        $customrelog->info('----------getApiRates-----------');

        try {
                $baseUrl =$this->shipping->getBaseUrl();
                $URL = $baseUrl . "/v1/addresses/validate";
                $api_key = $this->shipping->getAccessKey();
                $customrelog->info('----------$this->request->getPost();-----------');
                $customrelog->info(print_r($this->request->getBodyParams(), true));

                // $params = [
                //     'email' => $order->getCustomerEmail(),
                //     'firstname' => $order->getCustomerFirstname(),
                //     'lastname' => $order->getCustomerLastname(),
                //     'telephone' => $order->getShippingAddress()->getTelephone(),
                //     'shipping_address_1' => $address1,
                //     'shipping_address_2' => $address2,
                //     'shipping_zone' => $order->getShippingAddress()->getRegion(),
                //     'shipping_city' => $order->getShippingAddress()->getCity(),
                //     'shipping_country' => $order->getShippingAddress()->getCountryId(),
                //     'shipping_postcode' => $order->getShippingAddress()->getPostcode(),
                //     'order_id' => $order->getId(),
                // ];
            //set curl options
                $this->curl->setOption(CURLOPT_TIMEOUT, 120);
                $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'POST');
                $this->curl->addHeader('api_key', $api_key);
            //set curl header
                $this->curl->addHeader("Content-Type:", "application/json");
            //get request with url
                $this->curl->get($URL);
                $response = $this->curl->getBody();
                $customrelog->info('----------response-----------');
                $customrelog->info(print_r($response, true));

                $shippingData =json_decode($response, true);

                return $shippingData['data'];
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            return false;
        }
    }
    public function customLogger()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        return $logger;
    }
}
