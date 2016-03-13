$(document).ready(function() {
	var baseLayer = new ol.layer.Tile({
		source : new ol.source.MapQuest({
			layer : 'sat'
		})
	});
	
	var map = new ol.Map({
		interactions: ol.interaction.defaults({
			mouseWheelZoom:false
			}
		),
		layers : [ baseLayer ],
		target : 'map',
		view : new ol.View({
			projection: "EPSG:3857",
			center : [ -11000000, 4600000 ],
			zoom : 4
		}),
		controls: ol.control.defaults().extend([
			new ol.control.ScaleLine(),
			new ol.control.ZoomSlider(),
			new ol.control.FullScreen(),
			new ol.control.OverviewMap({
				className: 'ol-overviewmap ol-custom-overviewmap',
				})
		])
	});
	
	var features = new ol.Collection();

	var vectorSource = new ol.source.Vector(
			{
				//projection : 'EPSG:3857',
				features : features,
				url : function(extent) {
					return 'http://localhost:8080/geoserver/wfs?service=WFS&'
							+ 'version=1.1.0&request=GetFeature&typename=' + projectName + ':playa_samplepoint&'
							+ 'outputFormat=application/json&srsname=EPSG:3857&'
							+ 'bbox=' + extent.join(',') + ',EPSG:3857';
				},
				strategy : ol.loadingstrategy.tile(ol.tilegrid
						.createXYZ({
							maxZoom : 19
						})),
				format : new ol.format.GeoJSON(),
				//wrapX : false
			});
	
	var featureOverlay = new ol.layer.Vector(
			{
				source : vectorSource,
				style : new ol.style.Style({
					fill : new ol.style.Fill({
						color : 'rgba(255, 255, 255, 0.2)'
					}),
					stroke : new ol.style.Stroke({
						color : '#ffcc33',
						width : 2
					}),
					image : new ol.style.Circle({
						radius : 7,
						fill : new ol.style.Fill({
							color : '#ffcc33'
						})
					})
				})
			});
	
	map.addLayer(featureOverlay);
	
	var modify = new ol.interaction.Modify({
		features : features,
		// the SHIFT key must be pressed to delete vertices, so
		// that new vertices can be drawn at the same position
		// of existing vertices
		deleteCondition : function(event) {
			return ol.events.condition.shiftKeyOnly(event)
					&& ol.events.condition.singleClick(event);
		}
	});
	modify.on('modifyend', function (e) {
		console.log("feature id is",e.features.getArray()[0].getId());
		//e.features.forEach(function (feature) {
			transactWFS('update', e.features.getArray()[0]);
	    //});
		
	});
	
	var draw; // global so we can remove it later
	var eraseHover = new ol.interaction.Select({
		condition: ol.events.condition.pointerMove
		});
	var erase = new ol.interaction.Select({
	    condition: ol.events.condition.click
	    });
	
	erase.on('select', function(e) {
	    var collection = e.target.getFeatures(),
	        feature = collection.item(0);
	    vectorSource.removeFeature(feature);
	    collection.clear();
	});
	
	function addInteraction(type) {
		draw = new ol.interaction.Draw({
			features : features,
			type : type
		});
		map.addInteraction(draw);
	}
	//Refatorar para um array com eventos
	function clearCustomInteractions() {
		$("#toolbar").find("div").removeClass('active');
		map.removeInteraction(draw);
		map.removeInteraction(modify);
		map.removeInteraction(erase);
		map.removeInteraction(eraseHover);
	}
	
	function removeLowerCaseGeometryNodeForInsert(node) {
        var geometryNodes = node.getElementsByTagName("geometry"), element;
	    while (geometryNode = geometryNodes[0]){
		  geometryNode.parentNode.removeChild(geometryNode);
	    }
	}
	
	function removeNodeForWfsUpdate(node, valueToRemove) {
	  var propNodes = node.getElementsByTagName("Property");
	  for (var i = 0; i < propNodes.length; i++)
	  {
		var propNode = propNodes[i];
		var propNameNode = propNode.firstElementChild;
		var propNameNodeValue = propNameNode.firstChild;
		if (propNameNodeValue.nodeValue === valueToRemove)
		{
		  propNode.parentNode.removeChild(propNode);
		  break;
		}
	  }
	}
	/*
	 * 
	 * Função que transforma as projecoes das geometrias, para isso é necessário a clonagem
	 * 
	 */
	function transformFeaturePrj(f){
		 var geom = f.getGeometry().clone().transform('EPSG:3857','EPSG:4326');
		 var feature = f.clone();
		 feature.setGeometry(geom);
		 return feature;
	}
	
	//wfs-t
	var transactWFS = function(p,f,ft) {
		var formatWFS = new ol.format.WFS();
		var formatGML = new ol.format.GML({
			featureNS: 'http://www.opengeospatial.net/cite',
			featureType: ft,
			srsName: 'EPSG:3857'
			});
		switch(p) {
		case 'insert':
			f.set('geom', f.getGeometry());
			node = formatWFS.writeTransaction([f],null,null,formatGML);
			removeLowerCaseGeometryNodeForInsert(node);
			break;
		case 'update':
			f.set('geom', f.getGeometry());
			node = formatWFS.writeTransaction(null,[f],null,formatGML);
			removeNodeForWfsUpdate(node, "geometry");
			break;
		case 'delete':
			node = formatWFS.writeTransaction(null,null,[f],formatGML);
			break;
		}
		s = new XMLSerializer();
		str = s.serializeToString(node);
		$.ajax('http://localhost:8080/geoserver/wfs',{
			type: 'POST',
			dataType: 'xml',
			processData: false,
			contentType: 'text/xml',
			data: str
			}).done();
	}
	//fim wfs-t
	
	/**
	 * Lidando com eventos.
	 */
	
	$('#pan').click(function() {
		clearCustomInteractions();
		$(this).addClass('active');
		return false;
	});
	
	$('#edit').click(function() {
		clearCustomInteractions();
		$(this).addClass('active');
		map.addInteraction(modify);
		return false;
	});
	
	$('#erase').click(function() {
		clearCustomInteractions();
		$(this).addClass('active');
		map.addInteraction(eraseHover);
		map.addInteraction(erase);
		erase.getFeatures().on('change:length', function(e) {
			var patt = new RegExp("\\w*");
		    var res = patt.exec(e.target.item(0).getId());
			transactWFS('delete',e.target.item(0),res);
	    });
		return false;
	});
	
	$('#drawPoint').click(function() {
		clearCustomInteractions();
		$(this).addClass('active');
		addInteraction("Point");
		draw.on('drawend', function(e) {
			  //var feature = transformFeaturePrj(e.feature);
			  var patt = new RegExp("\\w*");
			  console.log(e.feature);
		      var res = 'playa_samplepoint';
			  transactWFS('insert',e.feature,res);
			  });
		return false;
	});
	
	$('#drawLine').click(function() {
		clearCustomInteractions();
		$(this).addClass('active');
		addInteraction("MultiLineString");
		draw.on('drawend', function(e) {
			  //var feature = transformFeaturePrj(e.feature);
			  transactWFS('insert',e.feature);
			  });
		return false;
	});
	
	$('#drawPolygon').click(function() {
		clearCustomInteractions();
		$(this).addClass('active');
		addInteraction("MultiPolygon");
		draw.on('drawend', function(e) {
			  //var feature = transformFeaturePrj(e.feature);
			  transactWFS('insert',e.feature);
			  });
		return false;
	});
	
	//addInteraction("Point");
});