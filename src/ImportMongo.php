<?php

/*
 * 
 *	Import Mondo Data Class
 *
 */

namespace Webflow;

use \DateTime;
use \DateTimeZone;
use \PDOException;

class ImportMongo {
    
    public $data_file;
    
    public function __construct($data_file = "jonfile.json") {
        $this->data_file = $data_file;
    }
    
    public function importData() {
        
        if (!file_exists(dirname(__FILE__) . "/" . $this->data_file)) { die("Data File Not Found!\n"); }
        
        $data = file_get_contents(dirname(__FILE__) . "/" . $this->data_file);
        
        $orders = json_decode($data, true);
        //print_r($orders);
        
        $db = new \Webflow\Wpdo();
        
        $tz = $db->datetime['time_zone'];
        
        //print_r($db->tables);
        
        foreach ( $orders as $order ) {
            $sql = "INSERT INTO " . $db->tables['order_table'] . " (BuyerEmailAddress, EbayOrderStatus, AdjustmentAmount, AmountPaid, AmountSaved, PaymentStatus, AddressID, AddressOwner, PaidTime, ShippedTime, SalesChannel, ExternalAddressID, OrderType, ShippedByAmazonTFM, IsBusinessOrder, LatestDeliveryDate, EarliestDeliveryDate, IsPremiumOrder, EarliestShipDate, FulfillmentChannel, IsPrime, ShipmentServiceLevelCategory, SalesTaxAmount, SellingManagerSalesRecordNumber, GetItFast, WC_Status, subtotal, total_tax, total_shipping, SalesTaxPercent, OrderID, OrderLastUpdate, OrderShipByDateGMT, OrderStatus, OrderTimeGMT, PaymentType, MerchantReferenceNumber, CreditCard, PaypalID, PaymentTransactionID, TotalOrderAmount, OrderCurrency, ResellerID, TransactionNotes, SellerOrderID, SellerOrderStatus, CartID, CheckoutSource, timeStamp, ItemCount, BuyerIpAddress) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            
            /*$params = array(
                $order['BuyerEmailAddress'],
                $order['OrderAttributes']['EbayOrderStatus'],
                $order['OrderAttributes']['AdjustmentAmount']['Amount'],
                $order['OrderAttributes']['AmountPaid']['Amount'],
                $order['OrderAttributes']['AmountSaved']['Amount'],
                $order['OrderAttributes']['PaymentStatus'],
                $order['OrderAttributes']['AddressID'],
                $order['OrderAttributes']['AddressOwner'],
                $this->getLocalTime($order['OrderAttributes']['PaidTime'][0], $tz),
                $this->getLocalTime($order['OrderAttributes']['ShippedTime'][0], $tz),
                $order['OrderAttributes']['SalesChannel'],
                $order['OrderID'],
                $this->getLocalTime($order['OrderLastUpdate'], $tz),
                $this->getLocalTime($order['OrderShipByDateGMT'][0], $tz),
                $order['OrderStatus'],
                $order['OrderTimeGMT']['sec'],
                $order['PaymentInfo']['PaymentType'],
                $order['TotalOrderAmount'],
                $order['timeStamp']['sec'],
                $order['ShoppingCart']['CartID'],
                $order['ShoppingCart']['CheckoutSource'],
            );*/
            
            $params = $this->buildOrderParams($order, $tz);
            //print_r($order); echo "\n";
            //print_r($params); echo "\n"; echo count($params) . "\n";
            $rs = $db->prepare($sql);
            
            try{
                $result = $rs->execute($params);
                
                try{
                    // insert items for order
                    $lineItems = $order['ShoppingCart']['LineItemSKUList'];
                    foreach ($lineItems as $item) {
                        $isql = "INSERT INTO " . $db->tables['item_table'] . " (OrderID, LineItemType, LineItemID, Quantity, Sku, Title, BuyerUserID, LineItemTotal, ItemSaleSource, SaleSourceID, UnitPrice, FulfillmentType, TaxCost, ShippingCost, ShippingTaxCost, TransactionID) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                        /*$iparams = array(
                            $order['OrderID'],
                            $item['LineItemType'],
                            $item['LineItemID'],
                            $item['Quantity'],
                            $item['Sku'],
                            $item['Title'],
                            $item['BuyerUserID'],
                            $item['LineItemTotal'],
                            $item['ItemSaleSource'],
                            $item['SaleSourceID'],
                            $item['UnitPrice'],
                            $item['FulfillmentType'],
                            $item['TaxCost'],
                            $item['ShippingCost'],
                            $item['ShippingTaxCost'],
                            $item['TransactionID'],
                        );*/
                        $iparams = $this->buildIparams($order['OrderID'], $item);
                        $irs = $db->prepare($isql);
                        $iresult = $irs->execute($iparams);
                        print_r("Item ID: " . $item['LineItemID'] . "\n");
                    }
                } catch(PDOException $e) {
                    error_log("Error inserting item data " . $e->getMessage());
                }
                try{
                    // insert shipping info for order
                    $shippingInfo = isset($order['ShippingInfo']) ? $order['ShippingInfo'] : array();
                    $shipmentList = isset($order['ShipmentList']) ? $order['ShipmentList'] : array();
                    $ssql = "INSERT INTO " . $db->tables['shipping_table'] . " (OrderID, ShippingPaidBySeller, ShippingServiceLevel, Name, AddressLine1, AddressLine2, City, State, PostalCode, CountryCode, Phone, Company, ShipmentIdentifier, ShippingCarrier, ShippingClass, TrackingNumber) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                    /*$sparams = array(
                        $order['OrderID'],
                        $shippingInfo['ShippingPaidBySeller'],
                        $shippingInfo['ShippingServiceLevel'],
                        $shippingInfo['ShippingAddress']['Name'],
                        $shippingInfo['ShippingAddress']['AddressLine1'],
                        $shippingInfo['ShippingAddress']['AddressLine2'],
                        $shippingInfo['ShippingAddress']['City'],
                        $shippingInfo['ShippingAddress']['State'],
                        $shippingInfo['ShippingAddress']['PostalCode'],
                        $shippingInfo['ShippingAddress']['CountryCode'],
                        $shippingInfo['ShippingAddress']['Phone']
                    );*/
                    $sparams = $this->buildSparams($order_id, $shippingInfo, $shipmentList);
                    $srs = $db->prepare($ssql);
                    $sresult = $srs->execute($sparams);
                    print_r("Shipping for Order ID: " . $order['OrderID'] . " inserted\n");
                } catch(PDOException $e) {
                    error_log("Error inserting shipping data " . $e->getMessage());
                }
                print("Order ID: " . $order['OrderID'] . "\n");
            } catch(PDOException $e) {
                error_log('Error inserting data ' . $e->getMessage());
            }
        }
        
    }
    
    public function buildOrderParams($order, $tz) {
        print_r($order['OrderAttributes']['SalesTaxAmount']['Amount']); echo "\n";
        $params = array(
            $this->setOrderParam($order['BuyerEmailAddress']),
            $this->setOrderParam($order['OrderAttributes']['EbayOrderStatus']),
            $this->setOrderParam($order['OrderAttributes']['AdjustmentAmount']['Amount']),
            $this->setOrderParam($order['OrderAttributes']['AmountPaid']['Amount']),
            $this->setOrderParam($order['OrderAttributes']['AmountSaved']['Amount']),
            $this->setOrderParam($order['OrderAttributes']['PaymentStatus']),
            $this->setOrderParam($order['OrderAttributes']['AddressID']),
            $this->setOrderParam($order['OrderAttributes']['AddressOwner']),
            isset($order['OrderAttributes']['PaidTime'][0]) ? $this->getLocalTime($order['OrderAttributes']['PaidTime'][0], $tz) : "00-00-00 00:00:00",
            isset($order['OrderAttributes']['ShippedTime'][0]) ? $this->getLocalTime($order['OrderAttributes']['ShippedTime'][0], $tz) : "00-00-00 00:00:00",
            $this->setOrderParam($order['OrderAttributes']['SalesChannel']),
            $this->setOrderParam($order['OrderAttributes']['ExternalAddressID']),
            $this->setOrderParam($order['OrderAttributes']['OrderType']),
            $this->setOrderParam($order['OrderAttributes']['ShippedByAmazonTFM']),
            $this->setOrderParam($order['OrderAttributes']['IsBusinessOrder']),
            isset($order['OrderAttributes']['LatestDelivery']) ? $this->getLocalTime($order['OrderAttributes']['LatestDelivery'], $tz) : "00-00-00 00:00:00",
            isset($order['OrderAttributes']['EarliestDeliveryDate']) ? $this->getLocalTime($order['OrderAttributes']['EarliestDeliveryDate'], $tz) : "00-00-00 00:00:00",
            $this->setOrderParam($order['OrderAttributes']['IsPremiumOrder']),
            isset($order['OrderAttributes']['EarliestShipDate']) ? $this->getLocalTime($order['OrderAttributes']['EarliestShipDate'], $tz) : "00-00-00 00:00:00",
            $this->setOrderParam($order['OrderAttributes']['FulfillmentChannel']),
            $this->setOrderParam($order['OrderAttributes']['IsPrime']),
            $this->setOrderParam($order['OrderAttributes']['ShipmentServiceLevelCategory']),
            $this->setOrderParam($order['OrderAttributes']['SalesTaxAmount']['Amount']),
            $this->setOrderParam($order['OrderAttributes']['SellingManagerSalesRecordNumber']),
            $this->setOrderParam($order['OrderAttributes']['GetItFast']),
            $this->setOrderParam($order['OrderAttributes']['WC Status']),
            $this->setOrderParam($order['OrderAttributes']['subtotal']),
            $this->setOrderParam($order['OrderAttributes']['total_tax']),
            $this->setOrderParam($order['OrderAttributes']['total_shipping']),
            $this->setOrderParam($order['OrderAttributes']['SalesTaxPercent']),
            $this->setOrderParam($order['OrderID']),
            isset($order['OrderLastUpdate']) ? $this->getLocalTime($order['OrderLastUpdate'], $tz) : "00-00-00 00:00:00",
            isset($order['OrderShipByDateGMT'][0]) ? $this->getLocalTime($order['OrderShipByDateGMT'][0], $tz) : "00-00-00 00:00:00",
            $this->setOrderParam($order['OrderStatus']),
            $this->setOrderParam($order['OrderTimeGMT']['sec']),
            $this->setOrderParam($order['PaymentInfo']['PaymentType']),
            $this->setOrderParam($order['PaymentInfo']['MerchantReferenceNumber']),
            $this->setOrderParam($order['PaymentInfo']['CreditCard']),
            $this->setOrderParam($order['PaymentInfo']['PaypalID']),
            $this->setOrderParam($order['PaymentInfo']['PaymentTransactionID']),
            $this->setOrderParam($order['TotalOrderAmount']),
            $this->setOrderParam($order['OrderCurrency']),
            $this->setOrderParam($order['ResellerID']),
            $this->setOrderParam($order['OrderCurrency']),
            $this->setOrderParam(json_encode($order['TransactionNotes'])),
            $this->setOrderParam($order['SellerInfo']['SellerOrderID']),
            $this->setOrderParam($order['ShoppingCart']['CartID']),
            $this->setOrderParam($order['ShoppingCart']['CheckoutSource']),
            $this->setOrderParam($order['timeStamp']['sec']),
            $this->setOrderParam($order['ItemCount']),
            $this->setOrderParam($order['BuyerIpAddress'])
        );
        return $params;
    }
    
    public function buildIparams($order_id, $item) {
        $param = array(
            $order_id,
            $this->setOrderParam($item['LineItemType']),
            $this->setOrderParam($item['LineItemID']),
            $this->setOrderParam($item['Quantity']),
            $this->setOrderParam($item['Sku']),
            $this->setOrderParam($item['Title']),
            $this->setOrderParam($item['BuyerUserID']),
            $this->setOrderParam($item['LineItemTotal']),
            $this->setOrderParam($item['ItemSaleSource']),
            $this->setOrderParam($item['SaleSourceID']),
            $this->setOrderParam($item['UnitPrice']),
            $this->setOrderParam($item['FulfillmentType']),
            $this->setOrderParam($item['TaxCost']),
            $this->setOrderParam($item['ShippingCost']),
            $this->setOrderParam($item['ShippingTaxCost']),
            $this->setOrderParam($item['TransactionID'])
        );
        return $param;
    }
    
    public function buildSparams($order_id, $shippingInfo, $shipmentList) {
        $sparams = array(
            $order_id,
            $this->setOrderParam($shippingInfo['ShippingPaidBySeller']),
            $this->setOrderParam($shippingInfo['ShippingServiceLevel']),
            $this->setOrderParam($shippingInfo['ShippingAddress']['Name']),
            $this->setOrderParam($shippingInfo['ShippingAddress']['AddressLine1']),
            $this->setOrderParam($shippingInfo['ShippingAddress']['AddressLine2']),
            $this->setOrderParam($shippingInfo['ShippingAddress']['City']),
            $this->setOrderParam($shippingInfo['ShippingAddress']['State']),
            $this->setOrderParam($shippingInfo['ShippingAddress']['PostalCode']),
            $this->setOrderParam($shippingInfo['ShippingAddress']['CountryCode']),
            $this->setOrderParam($shippingInfo['ShippingAddress']['Phone']),
            $this->setOrderParam($shippingInfo['ShippingAddress']['Compny']),
            $this->setOrderParam($shipmentList['ShipmentIdentifier']),
            $this->setOrderParam($shipmentList['ShippingCarrier']),
            $this->setOrderParam($shipmentList['ShippingClass']),
            $this->setOrderParam($shipmentList['TrackingNumber'])
        );
        return $sparams;
    }
    
    public function setOrderParam($data) {
        $param = "null";
        if (isset($data) && !empty($data)) { $param = $data; }
        return $param;
    }
    
    public function getLocalTime($date, $tz) {
        //if (empty($date)) { return "00-00-00 00:00:00"; }
        $new_date = DateTime::createFromFormat(
            'Y-m-d\TH:i:s.000\Z', 
            $date, 
            new DateTimeZone('UTC')
        );
        
        if (!$new_date) { return "00-00-00 00:00:00"; }
        
        $new_date->setTimeZone(new DateTimeZone($tz));

        return $new_date->format('Y-m-d H:i:s');
    }
    
    
}