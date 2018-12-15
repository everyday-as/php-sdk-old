<?php

namespace kanalumaddela\GmodStoreAPI;

use DateTime;
use DateTimeZone;
use Exception;

class Coupon extends Model
{
    /**
     * {@inheritdoc}
     */
    public static $endpoint = 'addons';
    
    /**
     * {@inheritdoc}
     */
    protected static $modelRelations = ['Addon'];

    public function expired()
    {
        return (new DateTime($this->expires_at))->setTimezone(new DateTimeZone('UTC'))->getTimestamp() < \time();
    }

    public function delete($addonId = null)
    {
        if (!isset($this->addon) && \is_null($addonId)) {
            throw new Exception('Addon relation not loaded. Required before being able to edit a coupon');
        }

        $addonId = isset($this->addon->id) ? $this->addon->id : $addonId;

        $result = $this->newRequest()->delete();

        dump('deleting coupon...');
        dump($result);
        die();
    }

    /**
     * {@inheritdoc}
     */
    protected function newRequest()
    {
        if (!isset($this->addon->id) && !isset($this->addon_id)) {
            throw new Exception('Addon ID not provided, cannot retrieve.');
        }
        
        $addonId = isset($this->addon->id) ? $this->addon->id : $this->addon_id;
        
        return parent::newRequest()->{static::$endpoint}($addonId)->coupons(isset($this->id) ? $this->id : null);
    }
}