var map, gridlayers = new Object();

var app = new Backbone.Marionette.Application();

app.addRegions({
    categoriesRegion: "#categories",
    datesRegion: "#datesdiv",
    issuesRegion: "#issuesdiv"
});


/******************TIMELINE*START*************************/
TimelineItem = Backbone.Model.extend({});

Timeline = Backbone.Collection.extend({
    model: TimelineItem
});

TimelineItemView =  Backbone.Marionette.ItemView.extend({
    tagName: 'li',
    template: "#dates-template",
    onRender: function(){
        this.$el.children().attr("title", this.model.get("name"));
        this.setElement(this.$el);
    },
});

IssueItemView =  Backbone.Marionette.ItemView.extend({
    tagName: 'li',
    template: "#issues-template",
    onRender: function(){
        this.$el = this.$el.children();
        this.setElement(this.$el);
    },
});

TimelineView = Backbone.Marionette.CollectionView.extend({
    tagName: 'ul',
    id: 'dates',
    itemView: TimelineItemView,
    
    appendHtml: function(collectionView, itemView, index){
        collectionView.$el.append(itemView.el);
    }
});

IssuesView = Backbone.Marionette.CollectionView.extend({
    tagName: 'ul',
    id: 'issues',
    itemView: IssueItemView,
    
    appendHtml: function(collectionView, itemView, index){
        collectionView.$el.append(itemView.el);
    }
});

/********************TIMELINE*END***********************/

Category = Backbone.Model.extend({});

Categories = Backbone.Collection.extend({
    model: Category
});

Subcategory = Backbone.Model.extend({});

Subcategories = Backbone.Collection.extend({
    model: Subcategory
});

Map = Backbone.Model.extend({
    defaults:{
        "name": "No name",
        "description": "no description",
        "publish": "basic method",
        "basemaps": [], 
        "layers": []
    }
});

MapView = Backbone.View.extend({
    initialize: function(mymap){
        if (map) {
            map.remove();
        }
        map = L.map('map').setView([55.8580, -4.2590], 11);
        this.render_layers();
        //map.on('mousemove click', function(e) {
        //    jQuery("#coords").html(e.containerPoint.toString() + ', ' + e.latlng.toString());
        //});
    },
    render_layers: function(){
        //console.log(this.model.get('basemaps'));
        var publish = this.model.get('publish');
        this.addControl(publish);
    
        this.prepare_grid_layers(publish);
        this.activateGridControl();
    },
    addControl: function(publish){
        var blayers = this.prepare_layers(this.model.get('basemaps'), "all");
        
        if (publish == 'simple') {
            jQuery("#timeline").hide();
            jQuery("#controls").html("");
            var olayers = this.prepare_layers(this.model.get('layers'), "one");
            L.control.layers(blayers, olayers).addTo(map);
        }else if (publish == 'opacity') {
            jQuery("#timeline").hide();
            var olayers = this.prepare_layers(this.model.get('layers'), "all");
            var html = new Array(olayers.length);
            var val = 100;
            for(l in olayers){
                html.push('<span class="tooltip"></span>');
                html.push(l+'<div class="map-slider" title="'+l+'">'+val+'</div>');
            }
            jQuery("#controls").html('<div id="sliders">'+html.join("")+'</div>');
            
            var tooltip = jQuery('.tooltip');
            tooltip.hide();
            jQuery(".map-slider").each(function(){
                var value = parseInt( jQuery( this ).text(), 10 );
                jQuery(this).empty().slider({
                    range: "min",
                    min: 1,
                    value: value,
                    start: function(event,ui) {
                        tooltip.fadeIn('fast');
                    },
                    slide: function( event, ui ) {
                        var title = jQuery(this).attr("title");
                        tooltip.css('left', ui.value).text(ui.value);  //Adjust the tooltip accordingly
                        olayers[title].setOpacity(ui.value/100);
                        if (ui.value <= 1) {
                            map.removeLayer(gridlayers[title]);
                        }else{
                            var gridlayer = gridlayers[title];
                            map.addLayer(gridlayer);
                            map.addControl(L.mapbox.gridControl(gridlayer, {follow: true}));
                        }
                    },
                    stop: function(event,ui) {
                        tooltip.fadeOut('fast');
                    }
                });
            });
        }else if (publish == 'timeslider') {
            jQuery("#controls").html("");
            var olayers = this.prepare_layers(this.model.get('layers'), "one");
            var timeline = new Timeline(this.model.get('layers'));
            var timelineview = new TimelineView({
                collection: timeline
            });
            app.datesRegion.show(timelineview);
            
            var issuesview = new IssuesView({
                collection: timeline
            });
            app.issuesRegion.show(issuesview);
            jQuery("#timeline").show();
            
            jQuery().timelinr({
                arrowKeys: 'true'
            });
            
            jQuery("#dates a").click(function(){
                var name = jQuery(this).attr("title");
                for(l in olayers){
                    if (l != name){
                        map.removeLayer(olayers[l]);
                    }else{
                        map.addLayer(olayers[l]);
                    }
                }
                for(l in gridlayers){
                    map.removeLayer(gridlayers[l]);
                }
                
                var gridlayer = gridlayers[name];
                map.addLayer(gridlayer);
                map.addControl(L.mapbox.gridControl(gridlayer, {follow: true}));
            });
        }
    },
    prepare_layers: function(mylayers, which) {
        var layers = new Object();
        var i = 0;
        for (layer in mylayers) {
            if (which == "all") {
                if (mylayers[layer]["mapbox_id"]=="openstreetmap") {
                    console.log("osm")
                    var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
                    var osmAttrib='Map data © OpenStreetMap contributors';
                    var osm = new L.TileLayer(osmUrl, {attribution: osmAttrib});
                    map.addLayer(osm);
                    layers["Openstreetmap"] = osm;
                }else{
                    layers[mylayers[layer]["name"]] = L.mapbox.tileLayer(mylayers[layer]["mapbox_id"]).addTo(map);
                }
            }else{
                if (i==0) {
                    layers[mylayers[layer]["name"]] = L.mapbox.tileLayer(mylayers[layer]["mapbox_id"]).addTo(map);
                }else{
                    layers[mylayers[layer]["name"]] = L.mapbox.tileLayer(mylayers[layer]["mapbox_id"]);
                }
                i++;
            }
        }
        return layers;
    },
    prepare_grid_layers: function(which){
        var i = 0;
        for (layer in this.model.get('layers')){
            var mpbx_id = this.model.get('layers')[layer]["mapbox_id"];
            var name = this.model.get('layers')[layer]["name"];
            var gridlayer = L.mapbox.gridLayer(mpbx_id);
            gridlayers[name] = gridlayer;
            if (which == "opacity") {
                //code
                map.addLayer(gridlayer);
                map.addControl(L.mapbox.gridControl(gridlayer, {follow: true}));
            }else{
                if (i==0) {
                    map.addLayer(gridlayer);
                    map.addControl(L.mapbox.gridControl(gridlayer, {follow: true}));
                }
                i++;
            }
        }
    },
    activateGridControl: function(){
        var layers = this.model.get('layers');
        jQuery('.leaflet-control-layers-selector').click(function(){
            if (jQuery(this).is(":checked") == true) {
                var gridlayer = gridlayers[jQuery.trim(jQuery(this).next().text())];
                map.addLayer(gridlayer);
                map.addControl(L.mapbox.gridControl(gridlayer, {follow: true}));
            }else{
                map.removeLayer(gridlayers[jQuery.trim(jQuery(this).next().text())]);
            }
        })
    },
    delete_map: function(){
        this.model.destroy();
    }
});

Maps = Backbone.Collection.extend({
    model: Map
});

MapsView = Backbone.Marionette.ItemView.extend({
    template: "#subcategory-template",
    tagName: "li",
    
    events: {
        'click': 'logInfoUrl'
    },
  
    logInfoUrl: function(){
        var myMap = this.model;
        myMap.view = new MapView({
            model: myMap
        });
        //create_map(myMap.get('basemaps'), myMap.get('layers'));
    }
});

SubcategoryView = Backbone.Marionette.CompositeView.extend({
    template: "#ul-list",
    
    className: "submenu_list",
  
    itemView: MapsView,
  
    itemViewContainer: "ul",
  
    events: {
        'click a': 'logInfoUrl'
    },
  
    initialize: function(){
        this.collection = this.model.get('maps');
    },
  
    logInfoUrl: function(){
        //console.log(this.model.get('description'));
    }
});


CategoryView = Backbone.Marionette.CompositeView.extend({
    template: "#accordion-group-template",
    
    className: "accordion-group",
  
    itemView: SubcategoryView,
  
    itemViewContainer: "ul",
  
    events: {
        'click a': 'logInfoUrl'
    },
  
    initialize: function(){
        this.collection = this.model.get('subcategories');
    },
  
    logInfoUrl: function(){
        //console.log(this.model.get('description'));
    }
});

AccordionView = Backbone.Marionette.CollectionView.extend({
    id: "categoryList",
    
    className: "accordion",
    
    itemView: CategoryView,
    
    appendHtml: function(collectionView, itemView, index){
        console.log(itemView.el)
        collectionView.$el.append(itemView.el);
    }
});

function get_categories() {
    jQuery.getJSON(window.location.pathname+"/wp-admin/admin-ajax.php?action=mapbox_get_maps", function(data) {
        
        var categories = new Categories(data);
        // we initialize them here
        categories.each(function(category){
            var subcategories = category.get('subcategories');
            var subcategoryCollection = new Subcategories(subcategories);
            category.set('subcategories', subcategoryCollection);
            subcategoryCollection.each(function(subcategory){
                var maps = subcategory.get('maps');
                var mapCollection = new Maps(maps);
                subcategory.set('maps', mapCollection);
            });
        });
        
        var accordionView = new AccordionView({
            collection: categories
        });
        app.categoriesRegion.show(accordionView);
        
        jQuery('.submenu_list ul').hide();
        jQuery('.subc').hide();
        jQuery('.accordion-group h3').click(function() {
            jQuery(this).next().slideToggle('normal');
        });
        jQuery('.submenu_list h4').click(function() {
            jQuery(this).next().slideToggle('normal');
        });
        
        jQuery("#timeline").hide();
    });
}
