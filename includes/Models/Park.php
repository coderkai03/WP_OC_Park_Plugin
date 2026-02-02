<?php
/**
 * Park domain model. Amenities and activities are taxonomy term slugs
 * (amenities: park_parking, park_restrooms, etc.; activities: park_soccer, park_baseball, etc.).
 */
class Park {
  public string $global_id;
  public ParkInfo $info;
  public ParkGeometry $geometry;
  /** @var string[] Term slugs for Amenities taxonomy */
  public array $amenities = [];
  /** @var string[] Term slugs for Activities taxonomy */
  public array $activities = [];
}
