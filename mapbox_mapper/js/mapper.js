var map = L.map('map').setView([56.4105, -3.2159], 11);
var gridLayer = L.mapbox.gridLayer('paulgeorgie.cog-historic-sites');

L.control.layers({
    //'Base Map': L.mapbox.tileLayer('examples.map-zgrqqx0w').addTo(map),
    'Grey Map': L.mapbox.tileLayer('paulgeorgie.map-509jxvx3')
}, {
    'Bike Stations': L.mapbox.tileLayer('paulgeorgie.cog-historic-sites'),
    'Bike Lanes': gridLayer
}).addTo(map);

map.addControl(L.mapbox.gridControl(gridLayer));