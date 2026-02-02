<?php
/**
 * Park info backed by ACF fields: park_name, park_address, park_type, park_size, park_url.
 */
class ParkInfo {
  public function __construct(
    public string $name,
    public string $address,
    public string $type,
    public string $size,
    public string $url,
  ) {}
}
