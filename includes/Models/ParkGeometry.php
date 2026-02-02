<?php
/**
 * GeoJSON-style geometry (type + coordinates) for map rendering and storage.
 */
class ParkGeometry {
  public function __construct(
    public string $type,
    public array $coordinates,
  ) {}
}
