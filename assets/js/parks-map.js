/**
 * Parks Map: draws the current park polygon using Google Maps Data layer
 * and shows an InfoWindow (tooltip) on click with park name, address, etc.
 * Expects window.PARK_DATA = { geometry: { type, coordinates }, info: { name, address, type, size, url } }.
 */
(function () {
  'use strict';

  var PARK_DATA = window.PARK_DATA;
  var mapEl = document.getElementById('parks-map');
  if (!PARK_DATA || !mapEl || !window.google || !window.google.maps) {
    return;
  }

  var geometry = PARK_DATA.geometry;
  if (!geometry || !geometry.coordinates || geometry.coordinates.length === 0) {
    mapEl.innerHTML = '<p>No geometry for this park.</p>';
    return;
  }

  // Build a GeoJSON Feature for the Data layer (Data.addGeoJson expects Feature or FeatureCollection)
  var geoJsonFeature = {
    type: 'Feature',
    geometry: {
      type: geometry.type || 'Polygon',
      coordinates: geometry.coordinates
    },
    properties: {}
  };

  var map = new google.maps.Map(mapEl, {
    zoom: 14,
    mapTypeId: 'satellite'
  });

  var dataLayer = new google.maps.Data();
  dataLayer.setMap(map);
  dataLayer.addGeoJson(geoJsonFeature);

  // Fit map to polygon bounds
  dataLayer.forEach(function (feature) {
    var bounds = new google.maps.LatLngBounds();
    feature.getGeometry().forEachLatLng(function (latLng) {
      bounds.extend(latLng);
    });
    map.fitBounds(bounds);
  });

  // Tooltip: InfoWindow on polygon click, content from PARK_DATA.info
  var infoWindow = new google.maps.InfoWindow();
  var info = PARK_DATA.info || {};

  function buildTooltipContent() {
    var parts = [];
    if (info.name) parts.push('<strong>' + escapeHtml(info.name) + '</strong>');
    if (info.address) parts.push(escapeHtml(info.address));
    if (info.type) parts.push('Type: ' + escapeHtml(info.type));
    if (info.size) parts.push('Size: ' + escapeHtml(info.size));
    if (info.url) parts.push('<a href="' + escapeHtml(info.url) + '" target="_blank" rel="noopener">Link</a>');
    return parts.length ? parts.join('<br>') : 'Park details';
  }

  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  dataLayer.addListener('click', function (event) {
    infoWindow.setContent(buildTooltipContent());
    infoWindow.setPosition(event.latLng);
    infoWindow.open(map);
  });
})();
