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
			zoom : 2
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
					return 'http://webgis/geoserver/wfs?service=WFS&'
							+ 'version=1.1.0&request=GetFeature&typename=' + prjId + ':' +tableName+'&'
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
	modify.on('modifyend', function(e) {
		//realizar o clear
		transactWFS('update', e.features.getArray()[0]);
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
	
	erase.getFeatures().on('change:length', function(e) {
		transactWFS('delete',e.target.item(0));
    });
	
	var seeDetailHover = new ol.interaction.Select({
		condition: ol.events.condition.pointerMove
		});
	
	var seeDetail = new ol.interaction.Select({
	    condition: ol.events.condition.click
	    });
	
	seeDetail.on('select', function(e) {
	    var collection = e.target.getFeatures(),
	        feature = collection.item(0);
	    if(feature){
	    	$("#modal-description").modal("show")
		    $("#modal-description-point").html(feature.get('description'));
	    }
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
		map.removeInteraction(seeDetail);
		map.removeInteraction(seeDetailHover);
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
	var transactWFS = function(p,feature,desc) {
		var formatWFS = new ol.format.WFS();
		var formatGML = new ol.format.GML({
			featureNS: 'http://'+prjId,
			featureType: tableName,
			srsName: 'EPSG:3857'
			});
		var error;
		switch(p) {
		case 'insert':
			feature.set('geom', feature.getGeometry());
			feature.set('description', desc);
			node = formatWFS.writeTransaction([feature],null,null,formatGML);
			removeLowerCaseGeometryNodeForInsert(node);
			error = function(){
				showAjaxErrorMessage("Ocorreu um erro ao inserir um ponto");
				vectorSource.removeFeature(feature);
			};
			break;
		case 'update':
			feature.set('geom', feature.getGeometry());
			feature.set('description', desc);
			node = formatWFS.writeTransaction(null,[feature],null,formatGML);
			removeNodeForWfsUpdate(node, "geometry");
			error = function(){
				showAjaxErrorMessage("Ocorreu um erro ao realizar mudança do ponto");
			};
			break;
		case 'delete':
			node = formatWFS.writeTransaction(null,null,[feature],formatGML);
			error = function(){
				showAjaxErrorMessage("Ocorreu um erro ao deletar o ponto");
			};
			break;
		}
		s = new XMLSerializer();
		str = s.serializeToString(node);
		$.ajax('http://webgis/geoserver/wfs', {
			type: 'POST',
			dataType: 'xml',
			processData: false,
			contentType: 'text/xml',
			data: str,
			error: error
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
		return false;
	});
	
	$('#drawPoint').click(function() {
		clearCustomInteractions();
		$(this).addClass('active');
		addInteraction("Point");
		draw.on('drawend', function(e) {
		//var feature = transformFeaturePrj(e.feature);
		  $("#modal-insert-point").modal('show');
		  document.getElementById("modal-insert-submit").onclick = function(){
			  $("#modal-insert-point").modal('hide');
			  var desc = $("#desc-point").val();
			  $("#desc-point").val("");
			  transactWFS('insert', e.feature, desc);
		  };
		  document.getElementById("modal-insert-close").onclick = function(){
			  vectorSource.removeFeature(e.feature);
		  };
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
			  transactWFS('insert', e.feature);
			  });
		return false;
	});
	
	$('#pointDetail').click(function() {
		clearCustomInteractions();
		$(this).addClass('active');
		map.addInteraction(seeDetail);
		map.addInteraction(seeDetailHover);
		return false;
	});
	
	//addInteraction("Point");
});