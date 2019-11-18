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
    
    public $data_file = "jonfile.json";
    
    public function importData() {
        
        if (!file_exists(dirname(__FILE__) . "/" . $this->data_file)) { die("Data File Not Found!\n"); }
        
        $data = file_get_contents(dirname(__FILE__) . "/" . $this->data_file);
        
        $orders = json_decode($data, true);
        
        $db = new \Webflow\Wpdo();
        
        $tz = $db->datetime['time_zone'];
        
        //print_r($db->tables);
        
        foreach ( $orders as $order ) {
            $sql = "INSERT INTO " . $db->tables['order_table'] . " (BuyerEmailAddress, EbayOrderStatus, AdjustmentAmount, AmountPaid, AmountSaved, PaymentStatus, AddressID, AddressOwner, PaidTime, ShippedTime, SalesChannel, OrderID, OrderLastUpdate, OrderShipByDateGMT, OrderStatus, OrderTimeGMT, PaymentType, TotalOrderAmount, timeStamp, CartID, CheckoutSource) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            
            $paidTime = $this->getLocalTime($order['OrderAttributes']['PaidTime'][0], $tz);
            
            $params = array(
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
            );
            
            $rs = $db->prepare($sql);
            
            try{
                $result = $rs->execute($params);
                try{
                    // insert items for order
                    $lineItems = $order['ShoppingCart']['LineItemSKUList'];
                    foreach ($lineItems as $item) {
                        $isql = "INSERT INTO " . $db->tables['item_table'] . " (OrderID, LineItemType, LineItemID, Quantity, Sku, Title, BuyerUserID, LineItemTotal) VALUES (?,?,?,?,?,?,?,?)";
                        $iparams = array(
                            $order['OrderID'],
                            $item['LineItemType'],
                            $item['LineItemID'],
                            $item['Quantity'],
                            $item['Sku'],
                            $item['Title'],
                            $item['BuyerUserID'],
                            $item['LineItemTotal']
                        );
                        $irs = $db->prepare($isql);
                        $iresult = $irs->execute($iparams);
                        print_r("Item ID: " . $item['LineItemID'] . "\n");
                    }
                } catch(PDOException $e) {
                    error_log("Error inserting item data " . $e->getMessage());
                }
                try{
                    // insert shipping info for order
                    $shippingInfo = $order['ShippingInfo'];
                    $ssql = "INSERT INTO " . $db->tables['shipping_table'] . " (OrderID, ShippingPaidBySeller, ShippingServiceLevel, Name, AddressLine1, AddressLine2, City, State, PostalCode, CountryCode, Phone) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
                    $sparams = array(
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
                    );
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
    
    public function getLocalTime($date, $tz) {
        $utc_date = DateTime::createFromFormat(
            'Y-m-d\TH:i:s.000\Z', 
            $date, 
            new DateTimeZone('UTC')
        );
        
        $new_date = $utc_date;
        
        $new_date->setTimeZone(new DateTimeZone($tz));

        return $new_date->format('Y-m-d H:i:s');
    }
    
    
}