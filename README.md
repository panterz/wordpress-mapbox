wordpress-mapbox
================

bash: n: command not found
A wordpress plugin for publishing mapbox maps

### Software Requirements ###
<p>For deploying the plugin through a command line you need the software below</p>
<pre><code>$ sudo apt-get install python-pip python-dev build-essential 
$ sudo pip install --upgrade pip 
$ pip install Fabric
</code></pre>

<p>Now, for deploying it you need to run</p>
<pre><code>fab deploy
</code></pre>

<p>You will be asked for the username of the container and for the path of wordpress. So if for example the path of wordpress installation is ~/publichtml/wordpress/ then what path you need to enter is publichtml/wordpress</p>

### What the plugin offers ###

<p>The plugin uses a specific data structure:
<ul><li>Category
</li> 
<li>Subcategory</li>
<li>Layer</li>
<li>Map</li>
</ul>

A Map belongs to a Subcategory that this subcategory belongs to a Category. Moreover, a Map has one or many layers. There are also 3 ways of changing between layers on a Map, the default control of Leaflet, an opacity control and a timeline slider. 
</p>

<p>The plugin offers an admin panel where the administrator can add, update and delete categories, subcategories, layers and maps. The admin can also configure the size of the map. The plugin is published through a page if the admin writes 
<pre><code>[mapbox_mapper/]</pre></code> in a new page.</p>

### What libraries are used ###

<ul>
<li>jQuery</li>
<li>jQuery-ui</li>
<li>Backbone</li>
<li>Backbone.Marionette</li>
<li>underscore</li>
<li>Mapbox.js</li>
<li>jquery-timelinr</li>
</ul>

### Further deployment ###
<ol>
<li>Search functionality and filtering of the maps/layers on the admin panel</li>
</li>Automating deployment of wordpress plugin for testing it fast</li>
<li>Thoughts about extra visualizations.</li>
</ol>
